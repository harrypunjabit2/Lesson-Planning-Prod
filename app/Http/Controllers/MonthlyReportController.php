<?php

// app/Http/Controllers/MonthlyReportController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Models\StudentConfig;
use App\Models\SubjectLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthlyReportController extends Controller
{
    public function index()
    {
        return view('monthly-report.index');
    }

    /**
     * Generate monthly report for all students
     */
    public function generateReport(Request $request)
    {
        try {
            $request->validate([
                'month' => 'required|string',
                'year' => 'required|integer|min:2020|max:2030'
            ]);

            $month = $request->input('month');
            $year = $request->input('year');

            Log::info("Generating monthly report for {$month} {$year}");

            // Get all unique student-subject combinations for this month
            $studentSubjects = LessonPlan::where('month', $month)
                ->where('year', $year)
                ->select('student_first_name', 'student_last_name', 'subject')
                ->distinct()
                ->get();

            if ($studentSubjects->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => "No data found for {$month} {$year}"
                ]);
            }

            $reportData = [];

            foreach ($studentSubjects as $studentSubject) {
                $firstName = $studentSubject->student_first_name;
                $lastName = $studentSubject->student_last_name;
                $subject = $studentSubject->subject;

                // Calculate report metrics
                $metrics = $this->calculateStudentMetrics($firstName, $lastName, $subject, $month, $year);

                if ($metrics) {
                    $reportData[] = [
                        'student_name' => trim($firstName . ' ' . $lastName),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'subject' => $subject,
                        'highest_level' => $metrics['highest_level'],
                        'highest_worksheet' => $metrics['highest_worksheet'],
                        'pages_completed' => $metrics['pages_completed']
                    ];
                }
            }

            // Sort by student name, then by subject (Math before Reading)
            usort($reportData, function($a, $b) {
                // First, compare student names
                $nameComparison = strcmp($a['student_name'], $b['student_name']);
                
                if ($nameComparison !== 0) {
                    return $nameComparison;
                }
                
                // If same student, sort by subject (Math before Reading)
                // Define subject priority
                $subjectOrder = ['Math' => 1, 'Reading' => 2];
                
                $aOrder = $subjectOrder[$a['subject']] ?? 999;
                $bOrder = $subjectOrder[$b['subject']] ?? 999;
                
                return $aOrder - $bOrder;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'report' => $reportData,
                    'total_students' => count($reportData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating monthly report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate metrics for a single student-subject combination
     */
    private function calculateStudentMetrics($firstName, $lastName, $subject, $month, $year)
    {
        try {
            // Get all lesson plans for this student-subject-month
            $lessonPlans = LessonPlan::where('student_first_name', $firstName)
                ->where('student_last_name', $lastName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year)
                ->orderBy('date')
                ->get();

            if ($lessonPlans->isEmpty()) {
                return null;
            }

            // 1. Get highest level reached
            $allLevels = $lessonPlans->pluck('level')->unique()->toArray();
            $highestLevel = $this->getHighestLevel($subject, $allLevels);

            // 2. Get highest worksheet number - from the day when highest level was reached
            // Find the worksheet number when the student first reached the highest level
            $highestLevelPlan = $lessonPlans->where('level', $highestLevel)->first();
            $highestWorksheet = $highestLevelPlan ? $highestLevelPlan->worksheet : $lessonPlans->max('worksheet');

            // Skip this student-subject if highest worksheet is 0
            if ($highestWorksheet == 0) {
                return null;
            }

            // 3. Calculate pages completed
            // Get pattern from student config
            $studentConfig = StudentConfig::where('student_first_name', $firstName)
                ->where('student_last_name', $lastName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if (!$studentConfig) {
                Log::warning("No student config found for {$firstName} {$lastName} - {$subject}");
                $pagesCompleted = 0;
            } else {
                $pagesCompleted = $this->calculatePagesCompleted($studentConfig, $month, $year);
            }

            return [
                'highest_level' => $highestLevel,
                'highest_worksheet' => $highestWorksheet,
                'pages_completed' => $pagesCompleted
            ];

        } catch (\Exception $e) {
            Log::error("Error calculating metrics for {$firstName} {$lastName} - {$subject}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the highest level from a list of levels based on SubjectLevel ordering
     * Higher ID in subject_levels = Higher level (7A is lowest at id=21, H II is highest at id=42)
     */
    private function getHighestLevel($subject, $levels)
    {
        if (empty($levels)) {
            return 'N/A';
        }

        Log::info("Finding highest level for subject: {$subject}, levels: " . implode(', ', $levels));

        // Get the level with the HIGHEST id (which is the highest difficulty level)
        // For example: H II (id=42) is higher than BI (id=29)
        $highestLevelRecord = SubjectLevel::where('subject', $subject)
            ->whereIn('level', $levels)
            ->orderBy('id', 'desc') // Highest ID = Highest level
            ->first();

        if ($highestLevelRecord) {
            Log::info("Found highest level: {$highestLevelRecord->level} (ID: {$highestLevelRecord->id})");
            return $highestLevelRecord->level;
        }

        // If no match found in subject_levels, get all levels for this subject and log them
        $allSubjectLevels = SubjectLevel::where('subject', $subject)->pluck('level', 'id')->toArray();
        Log::warning("No matching level found in subject_levels for {$subject}. Available levels: " . json_encode($allSubjectLevels));
        Log::warning("Requested levels were: " . implode(', ', $levels));

        // Fallback: return the last level encountered (most recent)
        return end($levels);
    }

    /**
     * Calculate total pages completed based on pattern and days in month
     */
/**
 * Calculate total pages completed based on pattern and days in month
 */
private function calculatePagesCompleted($studentConfig, $month, $year)
{
    try {
        // Parse the pattern (e.g., "4:3:3")
        $pattern = $studentConfig->pattern;
        $patternValues = array_map('intval', explode(':', $pattern));

        if (empty($patternValues)) {
            return 0;
        }

        // Get number of days in the month
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $monthNum = array_search($month, $monthNames);
        if ($monthNum === false) {
            return 0;
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum + 1, $year);

        // Calculate total pages: sum of pattern values * days in month
       // $pagesPerCycle = array_sum($patternValues);
       $pagesPerCycle=$patternValues[0];
        $totalPages = $pagesPerCycle * $daysInMonth;

        Log::info("Pattern: {$pattern}, Pages per cycle: {$pagesPerCycle}, Days: {$daysInMonth}, Total pages: {$totalPages}");

        return $totalPages;

    } catch (\Exception $e) {
        Log::error('Error calculating pages completed: ' . $e->getMessage());
        return 0;
    }
}

    /**
     * Export monthly report to CSV
     */
    public function exportReport(Request $request)
    {
        try {
            $request->validate([
                'month' => 'required|string',
                'year' => 'required|integer|min:2020|max:2030'
            ]);

            $month = $request->input('month');
            $year = $request->input('year');

            // Generate the report data
            $reportResponse = $this->generateReport($request);
            $responseData = json_decode($reportResponse->getContent(), true);

            if (!$responseData['success']) {
                throw new \Exception('Failed to generate report data');
            }

            $reportData = $responseData['data']['report'];

            // Prepare CSV data
            $csvData = [];
            
            // Headers
            $csvData[] = [
                'First Name + Last Name',
                'Subject',
                'Highest Level',
                'Highest Worksheet Level',
                'Number of Pages Completed'
            ];

            // Data rows
            foreach ($reportData as $row) {
                $csvData[] = [
                    $row['student_name'],
                    $row['subject'],
                    $row['highest_level'],
                    $row['highest_worksheet'],
                    $row['pages_completed']
                ];
            }

            // Generate filename
            $filename = "monthly_report_{$month}_{$year}_" . date('Y-m-d') . ".csv";

            // Create CSV
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting monthly report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available months and years for reports
     */
    public function getAvailableMonths()
    {
        try {
            $monthsYears = LessonPlan::select('month', 'year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->orderByRaw("FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') DESC")
                ->get()
                ->map(function($item) {
                    return [
                        'month' => $item->month,
                        'year' => $item->year,
                        'display' => "{$item->month} {$item->year}"
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $monthsYears
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available months: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load available months'
            ], 500);
        }
    }
}