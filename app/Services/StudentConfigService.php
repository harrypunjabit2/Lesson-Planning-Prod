<?php

// app/Services/StudentConfigService.php

namespace App\Services;

use App\Models\StudentConfig;
use App\Models\LessonPlan;
use App\Models\NewConcept;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentConfigService
{
    public function setupStudentData()
    {
        DB::beginTransaction();
        try {
            $studentsConfig = StudentConfig::all();
            
            if ($studentsConfig->isEmpty()) {
                return ['success' => false, 'error' => 'No students found in Config. Please add configurations first.'];
            }

            // Get existing entries with more specific querying
            $existingEntries = LessonPlan::select('month', 'date', 'student_first_name', 'student_last_name', 'subject', 'year')
                ->get()
                ->keyBy(function ($plan) {
                    $fullName = trim($plan->student_first_name . ' ' . $plan->student_last_name);
                    // Create a normalized key
                    $key = implode('-', [
                        trim($plan->month),
                        trim($plan->date), 
                        trim($fullName),
                        trim($plan->subject),
                        trim($plan->year)
                    ]);
                    return strtolower($key);
                });

            Log::info('Total existing lesson plans: ' . $existingEntries->count());
            
            $newEntries = [];
            $skippedCount = 0;

            foreach ($studentsConfig as $studentConfig) {
                $fullStudentName = trim($studentConfig->student_first_name . ' ' . $studentConfig->student_last_name);

                // Get new concepts for this level and subject
                $newConceptsWorksheets = NewConcept::where('level', $studentConfig->level)
                    ->where('subject', $studentConfig->subject)
                    ->where('is_new_concept', 'Y')
                    ->pluck('worksheet')
                    ->map(function ($worksheet) {
                        return LessonPlan::wrapWorksheetNumber($worksheet);
                    })
                    ->toArray();

                // Get all days in the month
                $allDaysInMonth = $this->getAllDaysInMonth($studentConfig->month, $studentConfig->year);

                // Get class dates for highlighting
                $classDates = $this->getClassDatesForMonth(
                    $studentConfig->month,
                    $studentConfig->year,
                    $studentConfig->class_day_1,
                    $studentConfig->class_day_2
                );
                $classDatesSet = array_flip($classDates);

                if (empty($allDaysInMonth)) continue;

                foreach ($allDaysInMonth as $date) {
                    // Create the same normalized key format
                    $entryKey = implode('-', [
                        trim($studentConfig->month),
                        trim($date),
                        trim($fullStudentName),
                        trim($studentConfig->subject),
                        trim($studentConfig->year)
                    ]);
                    $entryKey = strtolower($entryKey);
                    
                    // Check if this entry already exists
                    if ($existingEntries->has($entryKey)) {
                        $skippedCount++;
                        continue;
                    }

                    $isClassDay = isset($classDatesSet[$date]) ? 'Y' : 'N';

                    $newEntries[] = [
                        'month' => $studentConfig->month,
                        'date' => $date,
                        'subject' => $studentConfig->subject,
                        'level' => $studentConfig->level,
                        'worksheet' => 0,
                        'student_first_name' => $studentConfig->student_first_name,
                        'student_last_name' => $studentConfig->student_last_name,
                        'new_concept' => 'N',
                        'is_class_day' => $isClassDay,
                        'year' => $studentConfig->year,
                        'last_completed_page' => 0,
                        'repeats' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            
            Log::info("Skipped {$skippedCount} existing entries");
            Log::info("Found " . count($newEntries) . " new entries to add");
            
            if (!empty($newEntries)) {
                // Insert in batches to avoid memory issues
                $chunks = array_chunk($newEntries, 1000);
                foreach ($chunks as $chunk) {
                    LessonPlan::insert($chunk);
                }
                
                DB::commit();
                return [
                    'success' => true, 
                    'message' => "Successfully added " . count($newEntries) . " new lesson plan entries. Skipped {$skippedCount} existing entries."
                ];
            } else {
                DB::commit();
                return [
                    'success' => true, 
                    'message' => "No new entries to add - all students already have lesson plans. Skipped {$skippedCount} existing entries."
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error setting up student data: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return ['success' => false, 'error' => 'Error setting up student data: ' . $e->getMessage()];
        }
    }

    private function getAllDaysInMonth(string $month, int $year)
    {
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $monthNum = array_search($month, $monthNames);
        if ($monthNum === false) return [];
        
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum + 1, $year);
        
        return range(1, $daysInMonth);
    }

    private function getClassDatesForMonth(string $month, int $year, ?string $classDay1, ?string $classDay2)
    {
        $dates = [];
        
        if (!$classDay1 && !$classDay2) return $dates;

        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $monthNum = array_search($month, $monthNames);
        if ($monthNum === false) return $dates;

        $dayNames = [
            'Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
            'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6
        ];

        $targetDays = [];
        if ($classDay1 && isset($dayNames[$classDay1])) {
            $targetDays[] = $dayNames[$classDay1];
        }
        if ($classDay2 && isset($dayNames[$classDay2]) && $classDay1 !== $classDay2) {
            $targetDays[] = $dayNames[$classDay2];
        }

        if (empty($targetDays)) return $dates;

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum + 1, $year);

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = mktime(0, 0, 0, $monthNum + 1, $day, $year);
            $dayOfWeek = date('w', $date);
            
            if (in_array($dayOfWeek, $targetDays)) {
                $dates[] = $day;
            }
        }

        return $dates;
    }

    /**
     * Get student configurations
     */
    public function getStudentConfigurations(array $filters = [])
    {
        $query = StudentConfig::query();

        if (isset($filters['student_name'])) {
            $nameParts = explode(' ', trim($filters['student_name']), 2);
            $query->where('student_first_name', $nameParts[0]);
            if (isset($nameParts[1])) {
                $query->where('student_last_name', $nameParts[1]);
            }
        }

        if (isset($filters['subject'])) {
            $query->where('subject', $filters['subject']);
        }

        if (isset($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (isset($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        return $query->get();
    }

    /**
     * Create or update student configuration
     */
    public function createOrUpdateStudentConfig(array $data)
    {
        try {
            $config = StudentConfig::updateOrCreate(
                [
                    'student_first_name' => $data['student_first_name'],
                    'student_last_name' => $data['student_last_name'],
                    'subject' => $data['subject'],
                    'month' => $data['month'],
                    'year' => $data['year']
                ],
                $data
            );

            return [
                'success' => true,
                'message' => 'Student configuration saved successfully',
                'data' => $config
            ];

        } catch (\Exception $e) {
            Log::error('Error creating/updating student config: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to save student configuration: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete student configuration
     */
    public function deleteStudentConfig($id)
    {
        try {
            $config = StudentConfig::find($id);
            
            if (!$config) {
                return [
                    'success' => false,
                    'error' => 'Student configuration not found'
                ];
            }

            $config->delete();

            return [
                'success' => true,
                'message' => 'Student configuration deleted successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error deleting student config: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete student configuration: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate student configuration data
     */
    public function validateStudentConfigData(array $data)
    {
        $requiredFields = [
            'student_first_name',
            'student_last_name', 
            'subject',
            'level',
            'month',
            'year',
            'pattern'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return [
                'valid' => false,
                'errors' => ['Missing required fields: ' . implode(', ', $missingFields)]
            ];
        }

        // Additional validation
        $errors = [];

        if (!is_numeric($data['year']) || $data['year'] < 2020 || $data['year'] > 2030) {
            $errors[] = 'Year must be a valid year between 2020 and 2030';
        }

        if (!in_array($data['month'], [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ])) {
            $errors[] = 'Month must be a valid month name';
        }

        if (!preg_match('/^\d+:\d+:\d+$/', $data['pattern'])) {
            $errors[] = 'Pattern must be in format "number:number:number" (e.g., "2:3:2")';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}