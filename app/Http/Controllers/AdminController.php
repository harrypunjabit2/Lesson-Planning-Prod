<?php

namespace App\Http\Controllers;

use App\Models\StudentConfig;
use App\Models\NewConcept;
use App\Models\LessonPlan;
use App\Traits\LogsUserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use LogsUserActivity;
    public function setupPage()
    {
        return view('admin.setup');
    }

    public function deletePage()  
    {
        return view('admin.delete');
    }

    public function configIndex()
    {
        $configs = StudentConfig::orderBy('student_first_name')
            ->orderBy('subject')
            ->get();

        return view('admin.config', compact('configs'));
    }


    public function uploadConfigCsv(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:2048'
            ]);

            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            
            // Get headers from first row
            $headers = array_shift($csvData);
            
            // Clean headers (remove BOM and trim whitespace)
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);

            $addedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Create associative array
                    $data = array_combine($headers, $row);
                    
                    // Clean and validate data
                    $cleanData = [
                        'student_first_name' => trim($data['student_first_name'] ?? ''),
                        'student_last_name' => trim($data['student_last_name'] ?? ''),
                        'subject' => trim($data['subject'] ?? ''),
                        'class_day_1' => trim($data['class_day_1'] ?? ''),
                        'class_day_2' => trim($data['class_day_2'] ?? ''),
                        'pattern' => trim($data['pattern'] ?? ''),
                        'month' => trim($data['month'] ?? ''),
                        'year' => (int)($data['year'] ?? date('Y'))
                    ];

                    // Validate required fields
                    if (empty($cleanData['student_first_name']) || empty($cleanData['subject']) || 
                        empty($cleanData['pattern'])  || empty($cleanData['month'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields";
                        $errorCount++;
                        continue;
                    }

                    // Check if record exists (based on student name, subject, month, year)
                    $existingConfig = StudentConfig::where('student_first_name', $cleanData['student_first_name'])
                        ->where('student_last_name', $cleanData['student_last_name'])
                        ->where('subject', $cleanData['subject'])
                        ->where('month', $cleanData['month'])
                        ->where('year', $cleanData['year'])
                        ->first();

                    if ($existingConfig) {
                        // Update existing record
                        $existingConfig->update($cleanData);
                        $updatedCount++;
                    } else {
                        // Create new record
                        StudentConfig::create($cleanData);
                        $addedCount++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            DB::commit();

            $message = "CSV processed successfully! Added: $addedCount, Updated: $updatedCount";
            if ($errorCount > 0) {
                $message .= ", Errors: $errorCount";
            }

            return redirect()->route('admin.config')
                ->with('success', $message)
                ->with('csv_errors', $errors);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error uploading config CSV: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to upload CSV: ' . $e->getMessage());
        }
    }

public function updateConfig(Request $request, StudentConfig $config)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'student_first_name' => 'required|string|max:255',
                'student_last_name' => 'nullable|string|max:255',
                'subject' => 'required|string|max:255',
                'class_day_1' => 'nullable|string|max:255',
                'class_day_2' => 'nullable|string|max:255',
                'pattern' => 'required|string|max:255',
                'month' => 'required|string|max:255',
                'year' => 'required|integer|min:2020|max:2030'
            ]);

            // Store old values for logging
            $oldValues = [
                'student_first_name' => $config->student_first_name,
                'student_last_name' => $config->student_last_name,
                'subject' => $config->subject,
                'class_day_1' => $config->class_day_1,
                'class_day_2' => $config->class_day_2,
                'pattern' => $config->pattern,
                'month' => $config->month,
                'year' => $config->year
            ];

            $newValues = $request->only([
                'student_first_name',
                'student_last_name',
                'subject',
                'class_day_1',
                'class_day_2',
                'pattern',
                'month',
                'year'
            ]);

            // Check if class days changed
            $classDaysChanged = (
                $oldValues['class_day_1'] !== $newValues['class_day_1'] ||
                $oldValues['class_day_2'] !== $newValues['class_day_2']
            );

            // Update the configuration
            $config->update($newValues);

            // If class days changed, update lesson plans
            if ($classDaysChanged) {
                $this->updateLessonPlansClassDays(
                    $config->student_first_name,
                    $config->student_last_name,
                    $config->subject,
                    $config->month,
                    $config->year,
                    $newValues['class_day_1'] ?? null,
                    $newValues['class_day_2'] ?? null
                );
            }

            // Log the activity
            $this->logActivity(
                'update_student_config',
                'student_config',
                $config->id,
                $oldValues,
                $newValues,
                "Updated configuration for {$config->student_first_name} {$config->student_last_name} - {$config->subject} ({$config->month} {$config->year})"
            );

            DB::commit();

            $message = 'Student configuration updated successfully';
            if ($classDaysChanged) {
                $message .= ' and lesson plan class days updated';
            }

            return redirect()->route('admin.config')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating config: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update configuration: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update lesson plans with new class days when configuration changes
     */
    private function updateLessonPlansClassDays(
        string $firstName,
        ?string $lastName,
        string $subject,
        string $month,
        int $year,
        ?string $classDay1,
        ?string $classDay2
    ) {
        try {
            Log::info("Updating lesson plan class days for {$firstName} {$lastName} - {$subject} in {$month} {$year}");

            // Get all class dates for the month based on new class days
            $classDates = $this->getClassDatesForMonth($month, $year, $classDay1, $classDay2);
            $classDatesSet = array_flip($classDates);

            // Find all lesson plans for this student/subject/month/year
            $query = LessonPlan::where('student_first_name', $firstName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year);

            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }

            $lessonPlans = $query->get();

            if ($lessonPlans->isEmpty()) {
                Log::info("No lesson plans found to update");
                return;
            }

            // Update each lesson plan's is_class_day field
            $updatedCount = 0;
            foreach ($lessonPlans as $plan) {
                $isClassDay = isset($classDatesSet[$plan->date]) ? 'Y' : 'N';
                
                if ($plan->is_class_day !== $isClassDay) {
                    $plan->update(['is_class_day' => $isClassDay]);
                    $updatedCount++;
                }
            }

            Log::info("Updated {$updatedCount} lesson plan entries with new class day settings");

        } catch (\Exception $e) {
            Log::error("Error updating lesson plan class days: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get class dates for a specific month based on class days
     */
    private function getClassDatesForMonth(string $month, int $year, ?string $classDay1, ?string $classDay2)
    {
        $dates = [];
        
        if (!$classDay1 && !$classDay2) {
            return $dates;
        }

        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $monthNum = array_search($month, $monthNames);
        if ($monthNum === false) {
            return $dates;
        }

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

        if (empty($targetDays)) {
            return $dates;
        }

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

    // Add logging to other admin methods as well

    public function storeConfig(Request $request)
    {
        try {
            $request->validate([
                'student_first_name' => 'required|string|max:255',
                'student_last_name' => 'nullable|string|max:255',
                'subject' => 'required|string|max:255',
                'class_day_1' => 'nullable|string|max:255',
                'class_day_2' => 'nullable|string|max:255',
                'pattern' => 'required|string|max:255',
                'month' => 'required|string|max:255',
                'year' => 'required|integer|min:2020|max:2030'
            ]);

            $config = StudentConfig::create($request->all());

            // Log the activity
            $this->logActivity(
                'create_student_config',
                'student_config',
                $config->id,
                [],
                $request->all(),
                "Created new configuration for {$config->student_first_name} {$config->student_last_name} - {$config->subject} ({$config->month} {$config->year})"
            );

            return redirect()->route('admin.config')
                ->with('success', 'Student configuration created successfully');

        } catch (\Exception $e) {
            Log::error('Error creating config: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create configuration: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroyConfig(StudentConfig $config)
    {
        try {
            $configData = [
                'student_first_name' => $config->student_first_name,
                'student_last_name' => $config->student_last_name,
                'subject' => $config->subject,
                'month' => $config->month,
                'year' => $config->year
            ];

            $config->delete();

            // Log the activity
            $this->logActivity(
                'delete_student_config',
                'student_config',
                null,
                $configData,
                [],
                "Deleted configuration for {$configData['student_first_name']} {$configData['student_last_name']} - {$configData['subject']} ({$configData['month']} {$configData['year']})"
            );

            return redirect()->route('admin.config')
                ->with('success', 'Student configuration deleted successfully');

        } catch (\Exception $e) {
            Log::error('Error deleting config: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete configuration: ' . $e->getMessage());
        }
    }


    public function conceptsIndex()
    {
        $concepts = NewConcept::orderBy('level')
            ->orderBy('subject')
            ->orderBy('worksheet')
            ->get();

        return view('admin.concepts', compact('concepts'));
    }

    public function storeConcept(Request $request)
    {
        try {
            $request->validate([
                'level' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'worksheet' => 'required|integer|min:1',
                'is_new_concept' => 'required|in:Y,N'
            ]);

            NewConcept::create($request->all());

            return redirect()->route('admin.concepts')
                ->with('success', 'New concept added successfully');

        } catch (\Exception $e) {
            Log::error('Error storing concept: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add concept: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function uploadConceptCsv(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:2048'
            ]);

            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            
            // Get headers from first row
            $headers = array_shift($csvData);
            
            // Clean headers (remove BOM and trim whitespace)
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);

            $addedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Create associative array
                    $data = array_combine($headers, $row);
                    
                    // Clean and validate data
                    $cleanData = [
                        'level' => trim($data['level'] ?? ''),
                        'subject' => trim($data['subject'] ?? ''),
                        'worksheet' => trim($data['worksheet'] ?? ''),
                        'is_new_concept' => strtoupper(isset($data['is_new_concept']) && trim($data['is_new_concept']) !== '' ? trim($data['is_new_concept']) : 'N'),
                        'comments' => trim($data['comments'] ?? '')
                    ];

                    // Validate required fields
                    if (empty($cleanData['level']) || empty($cleanData['subject']) || 
                         !in_array($cleanData['is_new_concept'], ['Y', 'N'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing or invalid required fields";
                        $errorCount++;
                        continue;
                    }

                    // Check if record exists (based on level, subject, worksheet)
                    $existingConcept = NewConcept::where('level', $cleanData['level'])
                        ->where('subject', $cleanData['subject'])
                        ->where('worksheet', $cleanData['worksheet'])
                        ->first();

                    if ($existingConcept) {
                        // Update existing record
                        $existingConcept->update($cleanData);
                        $updatedCount++;
                    } else {
                        // Create new record
                        NewConcept::create($cleanData);
                        $addedCount++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            DB::commit();

            $message = "CSV processed successfully! Added: $addedCount, Updated: $updatedCount";
            if ($errorCount > 0) {
                $message .= ", Errors: $errorCount";
            }

            return redirect()->route('admin.concepts')
                ->with('success', $message)
                ->with('csv_errors', $errors);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error uploading concept CSV: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to upload CSV: ' . $e->getMessage());
        }
    }

    public function updateConcept(Request $request, NewConcept $concept)
    {
        try {
            $request->validate([
                'level' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'worksheet' => 'required|integer|min:1',
                'is_new_concept' => 'required|in:Y,N'
            ]);
            Log::info($request->all());
            
            $concept->update($request->all());

            return redirect()->route('admin.concepts')
                ->with('success', 'New concept updated successfully');

        } catch (\Exception $e) {
            Log::error('Error updating concept: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update concept: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroyConcept(NewConcept $concept)
    {
        try {
            $concept->delete();

            return redirect()->route('admin.concepts')
                ->with('success', 'New concept deleted successfully');

        } catch (\Exception $e) {
            Log::error('Error deleting concept: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete concept: ' . $e->getMessage());
        }
    }

    
}