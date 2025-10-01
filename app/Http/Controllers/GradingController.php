<?php

// app/Http/Controllers/GradingController.php - With Activity Logging

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Grading;
use App\Models\PageOverride;
use App\Models\LessonPlan;
use App\Models\StudentConfig;
use App\Traits\LogsUserActivity; // Add this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GradingController extends Controller
{
    use LogsUserActivity; // Add this trait

    public function index()
    {
        return view('grading.index');
    }

    /**
     * Get all students for dropdown
     */
    public function getStudents()
    {
        try {
            $students = LessonPlan::select('student_first_name', 'student_last_name')
                ->distinct()
                ->get()
                ->map(function ($student) {
                    return trim($student->student_first_name . ' ' . $student->student_last_name);
                })
                ->unique()
                ->sort()
                ->values();

            return response()->json($students);
        } catch (\Exception $e) {
            Log::error('Error getting students for grading: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load students'], 500);
        }
    }

    /**
     * Get subjects for a specific student
     */
    public function getSubjectsForStudent(Request $request)
    {
        try {
            $request->validate([
                'student_name' => 'required|string'
            ]);

            $studentName = $request->input('student_name');
            $nameParts = explode(' ', trim($studentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $query = LessonPlan::where('student_first_name', $firstName);
            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }

            $subjects = $query->distinct('subject')
                ->pluck('subject')
                ->sort()
                ->values();

            return response()->json($subjects);
        } catch (\Exception $e) {
            Log::error('Error getting subjects for student: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load subjects'], 500);
        }
    }

    /**
     * Get multi-day grading data with page overrides
     */
    public function getMultiDayGradingData(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'student_name' => 'required|string',
                'subject' => 'required|string'
            ]);

            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $studentName = $request->input('student_name');
            $subject = $request->input('subject');

            // Parse student name
            $nameParts = explode(' ', trim($studentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Generate date range
            $dates = [];
            $lessonPlans = [];

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateInfo = [
                    'date' => $date->format('Y-m-d'),
                    'formatted' => $date->format('Y-m-d'),
                    'short_date' => $date->format('M j'),
                    'day_of_week' => $date->format('D'),
                    'month' => $date->format('F'),
                    'year' => $date->year,
                    'day' => $date->day
                ];
                
                $dates[] = $dateInfo;

                // Find lesson plan for this date
                $query = LessonPlan::where('student_first_name', $firstName)
                    ->where('subject', $subject)
                    ->where('month', $dateInfo['month'])
                    ->where('year', $dateInfo['year'])
                    ->where('date', $dateInfo['day']);

                if ($lastName) {
                    $query->where('student_last_name', $lastName);
                }

                $lessonPlan = $query->first();
                
                if ($lessonPlan) {
                    // Get expected pages (calculated based on pattern)
                    $expectedPages = $this->calculateExpectedPages($lessonPlan);

                    // Get page overrides for this lesson plan
                    $pageOverrides = PageOverride::where('lesson_plan_id', $lessonPlan->id)
                        ->get()
                        ->keyBy('page_position');

                    // Build page grades array combining overrides and existing grades
                    $pageGrades = [];
                    for ($i = 1; $i <= count($expectedPages); $i++) {
                        $override = $pageOverrides->get($i);
                        
                        if ($override) {
                            $pageGrades[$i] = [
                                'id' => $override->id,
                                'grade' => $override->grade,
                                'time' => null,
                                'actual_page' => $override->custom_page,
                                'expected_page' => $expectedPages[$i - 1],
                                'has_override' => true
                            ];
                        } else {
                            $pageGrades[$i] = [
                                'id' => null,
                                'grade' => null,
                                'time' => null,
                                'actual_page' => $expectedPages[$i - 1],
                                'expected_page' => $expectedPages[$i - 1],
                                'has_override' => false
                            ];
                        }
                    }

                    $lessonPlans[$dateInfo['formatted']] = [
                        'id' => $lessonPlan->id,
                        'student_name' => trim($lessonPlan->student_first_name . ' ' . $lessonPlan->student_last_name),
                        'subject' => $lessonPlan->subject,
                        'level' => $lessonPlan->level,
                        'worksheet' => $lessonPlan->worksheet,
                        'is_class_day' => $lessonPlan->is_class_day,
                        'new_concept' => $lessonPlan->new_concept,
                        'time' => $lessonPlan->time ?? 10,
                        'hw_completed' => $lessonPlan->hw_completed ?? 'N',
                        'expected_pages' => $expectedPages,
                        'page_grades' => $pageGrades,
                        'page_overrides' => $pageOverrides->mapWithKeys(function ($override, $position) {
                            return [$position => [
                                'original_page' => $override->original_page,
                                'custom_page' => $override->custom_page,
                                'grade' => $override->grade
                            ]];
                        })->toArray()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $studentName,
                    'subject' => $subject,
                    'date_range' => $startDate->format('M j, Y') . ' to ' . $endDate->format('M j, Y'),
                    'dates' => $dates,
                    'lesson_plans' => $lessonPlans
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting multi-day grading data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load multi-day grading data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate expected pages for a lesson plan
     */
    private function calculateExpectedPages($lessonPlan)
    {
        try {
            $studentConfig = StudentConfig::where('student_first_name', $lessonPlan->student_first_name)
                ->where('student_last_name', $lessonPlan->student_last_name)
                ->where('subject', $lessonPlan->subject)
                ->where('month', $lessonPlan->month)
                ->where('year', $lessonPlan->year)
                ->first();

            if (!$studentConfig) {
                $startingPage = $lessonPlan->worksheet;
                return [$startingPage, $startingPage + 1, $startingPage + 2];
            }

            $patternValues = explode(':', $studentConfig->pattern);
            $patternValues = array_map('intval', array_filter($patternValues));
            
            if (empty($patternValues)) {
                $maxPagesAllowed = 3;
            } else {
                $maxPagesAllowed = max($patternValues);
            }

            $nextLessonPlan = $this->findNextLessonPlan($lessonPlan, $studentConfig);
            $actualPagesCount = 3;

            if ($nextLessonPlan) {
                $currentWorksheet = $lessonPlan->worksheet;
                $nextWorksheet = $nextLessonPlan->worksheet;
                
                if ($nextWorksheet < $currentWorksheet) {
                    $difference = (200 - $currentWorksheet) + $nextWorksheet;
                } else {
                    $difference = $nextWorksheet - $currentWorksheet;
                }

                $actualPagesCount = $difference;
            } else {
                $actualPagesCount = $patternValues[0] ?? 3;
            }

            $pagesCount = min($actualPagesCount, $maxPagesAllowed);
            $pages = [];
            $startingPage = $lessonPlan->worksheet;
            
            for ($i = 0; $i < $pagesCount; $i++) {
                $pageNumber = $startingPage + $i;
                if ($pageNumber > 200) {
                    $pageNumber = $pageNumber - 200;
                }
                $pages[] = $pageNumber;
            }

            return $pages;

        } catch (\Exception $e) {
            Log::error('Error calculating expected pages: ' . $e->getMessage());
            $startingPage = $lessonPlan->worksheet;
            return [$startingPage, $startingPage + 1, $startingPage + 2];
        }
    }

    /**
     * Find the next lesson plan
     */
    private function findNextLessonPlan($currentLessonPlan, $studentConfig)
    {
        try {
            $nextDay = LessonPlan::where('student_first_name', $currentLessonPlan->student_first_name)
                ->where('student_last_name', $currentLessonPlan->student_last_name)
                ->where('subject', $currentLessonPlan->subject)
                ->where('month', $currentLessonPlan->month)
                ->where('year', $currentLessonPlan->year)
                ->where('date', '>', $currentLessonPlan->date)
                ->orderBy('date', 'asc')
                ->first();

            if ($nextDay) {
                return $nextDay;
            }

            $currentMonthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            $currentMonthIndex = array_search($currentLessonPlan->month, $currentMonthNames);
            $nextMonthIndex = ($currentMonthIndex + 1) % 12;
            $nextYear = $currentLessonPlan->year;
            
            if ($nextMonthIndex === 0) {
                $nextYear++;
            }

            $nextMonth = $currentMonthNames[$nextMonthIndex];

            $nextMonthPlan = LessonPlan::where('student_first_name', $currentLessonPlan->student_first_name)
                ->where('student_last_name', $currentLessonPlan->student_last_name)
                ->where('subject', $currentLessonPlan->subject)
                ->where('month', $nextMonth)
                ->where('year', $nextYear)
                ->orderBy('date', 'asc')
                ->first();

            return $nextMonthPlan;

        } catch (\Exception $e) {
            Log::error('Error finding next lesson plan: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save page number override - WITH LOGGING
     */
    public function savePageOverride(Request $request)
    {
        try {
            $request->validate([
                'lesson_plan_id' => 'required|integer|exists:lesson_plans,id',
                'page_position' => 'required|integer|min:1',
                'custom_page' => 'required|integer|min:1|max:200',
                'original_page' => 'required|integer|min:1|max:200'
            ]);

            DB::beginTransaction();

            $lessonPlanId = $request->input('lesson_plan_id');
            $pagePosition = $request->input('page_position');
            $customPage = $request->input('custom_page');
            $originalPage = $request->input('original_page');

            // Get lesson plan info for logging
            $lessonPlan = LessonPlan::findOrFail($lessonPlanId);
            
            // Check if override exists
            $existingOverride = PageOverride::where('lesson_plan_id', $lessonPlanId)
                ->where('page_position', $pagePosition)
                ->first();

            $oldValues = $existingOverride ? [
                'custom_page' => $existingOverride->custom_page,
                'original_page' => $existingOverride->original_page
            ] : [];

            // Find or create page override
            $pageOverride = PageOverride::updateOrCreate(
                [
                    'lesson_plan_id' => $lessonPlanId,
                    'page_position' => $pagePosition
                ],
                [
                    'original_page' => $originalPage,
                    'custom_page' => $customPage
                ]
            );

            // Log the activity
            $this->logGradingActivity(
                'save_page_override',
                $lessonPlan->student_first_name . ' ' . $lessonPlan->student_last_name,
                $lessonPlan->subject,
                $lessonPlan->month,
                $lessonPlan->date,
                $oldValues,
                [
                    'page_position' => $pagePosition,
                    'custom_page' => $customPage,
                    'original_page' => $originalPage
                ],
                "Set page override for position {$pagePosition}: page {$originalPage} → page {$customPage}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Page override saved successfully',
                'data' => [
                    'id' => $pageOverride->id,
                    'custom_page' => $pageOverride->custom_page,
                    'original_page' => $pageOverride->original_page
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving page override: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to save page override: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove page override - WITH LOGGING
     */
    public function removePageOverride(Request $request)
    {
        try {
            $request->validate([
                'lesson_plan_id' => 'required|integer|exists:lesson_plans,id',
                'page_position' => 'required|integer|min:1'
            ]);

            DB::beginTransaction();

            $lessonPlanId = $request->input('lesson_plan_id');
            $pagePosition = $request->input('page_position');

            // Get lesson plan info for logging
            $lessonPlan = LessonPlan::findOrFail($lessonPlanId);
            
            // Get existing override for logging
            $existingOverride = PageOverride::where('lesson_plan_id', $lessonPlanId)
                ->where('page_position', $pagePosition)
                ->first();

            $deleted = PageOverride::where('lesson_plan_id', $lessonPlanId)
                ->where('page_position', $pagePosition)
                ->delete();

            // Log the activity if something was deleted
            if ($deleted && $existingOverride) {
                $this->logGradingActivity(
                    'remove_page_override',
                    $lessonPlan->student_first_name . ' ' . $lessonPlan->student_last_name,
                    $lessonPlan->subject,
                    $lessonPlan->month,
                    $lessonPlan->date,
                    [
                        'page_position' => $pagePosition,
                        'custom_page' => $existingOverride->custom_page,
                        'original_page' => $existingOverride->original_page
                    ],
                    [],
                    "Removed page override for position {$pagePosition} (was page {$existingOverride->custom_page})"
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $deleted ? 'Page override removed successfully' : 'No override found to remove'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing page override: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to remove page override: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save grade to page_overrides table - WITH LOGGING
     */
    public function saveGradeToPageOverride(Request $request)
    {
        try {
            $request->validate([
                'lesson_plan_id' => 'required|integer|exists:lesson_plans,id',
                'page_position' => 'required|integer|min:1',
                'grade' => 'nullable|numeric|min:0|max:10'
            ]);

            DB::beginTransaction();

            $lessonPlanId = $request->input('lesson_plan_id');
            $pagePosition = $request->input('page_position');
            $grade = $request->input('grade');

            // Get lesson plan info for logging
            $lessonPlan = LessonPlan::findOrFail($lessonPlanId);

            // Find existing page override
            $pageOverride = PageOverride::where('lesson_plan_id', $lessonPlanId)
                ->where('page_position', $pagePosition)
                ->first();

            $oldGrade = $pageOverride ? $pageOverride->grade : null;

            if ($pageOverride) {
                $pageOverride->update(['grade' => $grade]);
            } else {
                $expectedPages = $this->calculateExpectedPages($lessonPlan);
                $expectedPage = $expectedPages[$pagePosition - 1] ?? $pagePosition;

                PageOverride::create([
                    'lesson_plan_id' => $lessonPlanId,
                    'page_position' => $pagePosition,
                    'original_page' => $expectedPage,
                    'custom_page' => $expectedPage,
                    'grade' => $grade
                ]);
            }

            // Log the activity
            $this->logGradingActivity(
                'save_grade',
                $lessonPlan->student_first_name . ' ' . $lessonPlan->student_last_name,
                $lessonPlan->subject,
                $lessonPlan->month,
                $lessonPlan->date,
                ['grade' => $oldGrade, 'page_position' => $pagePosition],
                ['grade' => $grade, 'page_position' => $pagePosition],
                "Saved grade {$grade} for page position {$pagePosition}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Grade saved successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving grade to page override: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to save grade: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk save lesson plan changes - WITH LOGGING
     */
    public function bulkSaveLessonPlanChanges(Request $request)
    {
        try {
            $request->validate([
                'changes' => 'required|array',
                'changes.*.lesson_plan_id' => 'required|integer|exists:lesson_plans,id',
                'changes.*.field' => 'required|string|in:time,hw_completed,is_class_day',
                'changes.*.value' => 'required'
            ]);

            DB::beginTransaction();

            $savedCount = 0;
            $errors = [];
            $changesSummary = [];

            foreach ($request->input('changes') as $change) {
                try {
                    $lessonPlan = LessonPlan::findOrFail($change['lesson_plan_id']);
                    
                    $field = $change['field'];
                    $value = $change['value'];
                    $oldValue = $lessonPlan->$field;

                    // Validate value based on field
                    if ($field === 'time') {
                        $value = (int) $value;
                        if ($value < 0 || $value > 180) {
                            throw new \Exception("Invalid time value: {$value}");
                        }
                    } elseif ($field === 'hw_completed' || $field === 'is_class_day') {
                        if (!in_array($value, ['Y', 'N'])) {
                            throw new \Exception("Invalid value for {$field}: {$value}");
                        }
                    }

                    $lessonPlan->update([$field => $value]);
                    $savedCount++;

                    // Track changes for logging
                    $studentKey = $lessonPlan->student_first_name . ' ' . $lessonPlan->student_last_name;
                    if (!isset($changesSummary[$studentKey])) {
                        $changesSummary[$studentKey] = [
                            'student' => $studentKey,
                            'subject' => $lessonPlan->subject,
                            'changes' => []
                        ];
                    }
                    $changesSummary[$studentKey]['changes'][] = [
                        'month' => $lessonPlan->month,
                        'date' => $lessonPlan->date,
                        'field' => $field,
                        'old' => $oldValue,
                        'new' => $value
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Error updating lesson plan {$change['lesson_plan_id']}: " . $e->getMessage();
                    Log::error("Error in bulk save: " . $e->getMessage());
                }
            }

            // Log the bulk changes
            foreach ($changesSummary as $summary) {
                $changesCount = count($summary['changes']);
                $this->logActivity(
                    'bulk_save_lesson_plan_changes',
                    'lesson_plan',
                    null,
                    [],
                    ['changes_count' => $changesCount, 'changes' => $summary['changes']],
                    "Bulk updated {$changesCount} lesson plan fields for {$summary['student']} ({$summary['subject']})"
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'saved_count' => $savedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk save lesson plan changes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to save changes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export multi-day grades to CSV
     */
    public function exportMultiDayGrades(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'student_name' => 'required|string',
                'subject' => 'required|string'
            ]);

            $dataResponse = $this->getMultiDayGradingData($request);
            $responseData = json_decode($dataResponse->getContent(), true);

            if (!$responseData['success']) {
                throw new \Exception('Failed to load data for export');
            }

            $data = $responseData['data'];
            
            $csvData = [];
            $headers = ['Date', 'Day', 'CW/HW', 'Time', 'HW Completed'];
            
            $maxPages = 0;
            foreach ($data['lesson_plans'] as $lessonPlan) {
                if (isset($lessonPlan['expected_pages'])) {
                    $maxPages = max($maxPages, count($lessonPlan['expected_pages']));
                }
            }
            
            for ($i = 1; $i <= $maxPages; $i++) {
                $headers[] = "Page {$i}";
                $headers[] = "Grade {$i}";
            }
            
            $csvData[] = $headers;

            foreach ($data['dates'] as $dateInfo) {
                $lessonPlan = $data['lesson_plans'][$dateInfo['formatted']] ?? null;
                
                $row = [
                    $dateInfo['short_date'],
                    $dateInfo['day_of_week'],
                    $lessonPlan ? ($lessonPlan['is_class_day'] === 'Y' ? 'CW' : 'HW') : '-',
                    $lessonPlan ? $lessonPlan['time'] : '-',
                    $lessonPlan ? $lessonPlan['hw_completed'] : '-'
                ];

                for ($i = 1; $i <= $maxPages; $i++) {
                    if ($lessonPlan && isset($lessonPlan['page_grades'][$i])) {
                        $pageData = $lessonPlan['page_grades'][$i];
                        $row[] = $pageData['actual_page'];
                        $row[] = $pageData['grade'] ?? '';
                    } else {
                        $row[] = '';
                        $row[] = '';
                    }
                }

                $csvData[] = $row;
            }

            // Log the export
            $this->logActivity(
                'export_grades',
                'grading',
                null,
                [],
                [
                    'student' => $data['student'],
                    'subject' => $data['subject'],
                    'date_range' => $data['date_range']
                ],
                "Exported grades for {$data['student']} ({$data['subject']}) - {$data['date_range']}"
            );

            $filename = "{$data['student']}_{$data['subject']}_grades_" . date('Y-m-d') . ".csv";
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\""
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting multi-day grades: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to export grades: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkSaveGrades(Request $request)
    {
        return $this->bulkSaveLessonPlanChanges($request);
    }
}