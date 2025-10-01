<?php

// app/Services/LessonPlanService.php - Updated with Level Progression Logic

namespace App\Services;

use App\Models\LessonPlan;
use App\Models\StudentConfig;
use App\Models\NewConcept;
use App\Models\SubjectLevel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonPlanService
{
    /**
     * Check if worksheet wrapping has occurred and handle level progression
     * 
     * @param int $previousWorksheet
     * @param int $currentWorksheet
     * @param string $studentFirstName
     * @param string $studentLastName
     * @param string $subject
     * @param string $month
     * @param int $year
     * @param int $date
     */
    private function handleLevelProgression(
        int $previousWorksheet, 
        int $currentWorksheet, 
        string $studentFirstName, 
        string $studentLastName, 
        string $subject, 
        string $month, 
        int $year,
        int $date
    ) {
        // Check if wrapping occurred (from 200 to 1-10 range)
        if ($previousWorksheet >= 190 && $currentWorksheet <= 10) {
            Log::info("Worksheet wrapping detected: {$previousWorksheet} -> {$currentWorksheet} for {$studentFirstName} {$studentLastName}");
            
            // Get current student configuration to find current level
            $studentConfig = StudentConfig::where('student_first_name', $studentFirstName)
                ->where('student_last_name', $studentLastName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year)
                ->first();
            
            if (!$studentConfig) {
                Log::warning("Student configuration not found for level progression");
                return;
            }
            
            $currentLevel = $studentConfig->level;
            $nextLevel = SubjectLevel::getNextLevel($subject, $currentLevel);
            
            if (!$nextLevel) {
                Log::info("No next level found for {$subject} after {$currentLevel} - student is at maximum level");
                return;
            }
            
            Log::info("Progressing level from {$currentLevel} to {$nextLevel}");
            
            // Update the student's level from the wrapping date onwards
            $this->progressStudentLevel(
                $studentFirstName,
                $studentLastName,
                $subject,
                $month,
                $year,
                $date,
                $nextLevel
            );
        }
    }
    
    /**
     * Progress student to next level from a specific date onwards
     */
    private function progressStudentLevel(
        string $studentFirstName,
        string $studentLastName,
        string $subject,
        string $month,
        int $year,
        int $fromDate,
        string $newLevel
    ) {
        DB::beginTransaction();
        try {
            // Update StudentConfig
            StudentConfig::where('student_first_name', $studentFirstName)
                ->where('student_last_name', $studentLastName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year)
                ->update(['level' => $newLevel]);
            
            // Get new concepts for the new level
            $newConceptsWorksheets = NewConcept::where('level', $newLevel)
                ->where('subject', $subject)
                ->where('is_new_concept', 'Y')
                ->pluck('worksheet')
                ->map(function ($worksheet) {
                    return LessonPlan::wrapWorksheetNumber($worksheet);
                })
                ->toArray();
            
            // Update all lesson plans from the wrapping date onwards in current month
            $currentMonthPlans = LessonPlan::where('student_first_name', $studentFirstName)
                ->where('student_last_name', $studentLastName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year)
                ->where('date', '>=', $fromDate)
                ->get();
            
            foreach ($currentMonthPlans as $plan) {
                $isNewConcept = in_array($plan->worksheet, $newConceptsWorksheets) ? 'Y' : 'N';
                $plan->update([
                    'level' => $newLevel,
                    'new_concept' => $isNewConcept
                ]);
            }
            
            // Update future months as well
            $this->updateFutureMonthsLevel($studentFirstName, $studentLastName, $subject, $month, $year, $newLevel);
            
            DB::commit();
            Log::info("Successfully progressed student to level {$newLevel}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error progressing student level: " . $e->getMessage());
        }
    }
    
    /**
     * Update future months with new level
     */
    private function updateFutureMonthsLevel(
        string $studentFirstName,
        string $studentLastName,
        string $subject,
        string $currentMonth,
        int $currentYear,
        string $newLevel
    ) {
        // Get all future student configurations and lesson plans
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $currentMonthIndex = array_search($currentMonth, $monthNames);
        if ($currentMonthIndex === false) return;
        
        // Update remaining months in current year
        for ($i = $currentMonthIndex + 1; $i < 12; $i++) {
            $this->updateMonthLevel($studentFirstName, $studentLastName, $subject, $monthNames[$i], $currentYear, $newLevel);
        }
        
        // Update next year configurations if they exist
        $this->updateMonthLevel($studentFirstName, $studentLastName, $subject, null, $currentYear + 1, $newLevel, true);
    }
    
    /**
     * Update specific month's level or all months in a year
     */
    private function updateMonthLevel(
        string $studentFirstName,
        string $studentLastName,
        string $subject,
        ?string $month,
        int $year,
        string $newLevel,
        bool $allMonthsInYear = false
    ) {
        // Update StudentConfig
        $configQuery = StudentConfig::where('student_first_name', $studentFirstName)
            ->where('student_last_name', $studentLastName)
            ->where('subject', $subject)
            ->where('year', $year);
        
        if (!$allMonthsInYear && $month) {
            $configQuery->where('month', $month);
        }
        
        $configQuery->update(['level' => $newLevel]);
        
        // Update LessonPlan entries
        $planQuery = LessonPlan::where('student_first_name', $studentFirstName)
            ->where('student_last_name', $studentLastName)
            ->where('subject', $subject)
            ->where('year', $year);
        
        if (!$allMonthsInYear && $month) {
            $planQuery->where('month', $month);
        }
        
        $plans = $planQuery->get();
        
        // Get new concepts for the new level
        $newConceptsWorksheets = NewConcept::where('level', $newLevel)
            ->where('subject', $subject)
            ->where('is_new_concept', 'Y')
            ->pluck('worksheet')
            ->map(function ($worksheet) {
                return LessonPlan::wrapWorksheetNumber($worksheet);
            })
            ->toArray();
        
        foreach ($plans as $plan) {
            $isNewConcept = in_array($plan->worksheet, $newConceptsWorksheets) ? 'Y' : 'N';
            $plan->update([
                'level' => $newLevel,
                'new_concept' => $isNewConcept
            ]);
        }
    }

    // Keep all existing methods and add the level progression logic to the worksheet calculation methods
    
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

private function generateLessonPlansForStudent($config, $month, $year, $startingWorksheet)
{
    // Get all days in the month
    $allDaysInMonth = $this->getAllDaysInMonth($month, $year);
    
    // Get class dates for highlighting
    $classDates = $this->getClassDatesForMonth(
        $month,
        $year,
        $config->class_day_1,
        $config->class_day_2
    );
    $classDatesSet = array_flip($classDates);
    
    // Get new concepts for this level and subject
    $newConceptsWorksheets = NewConcept::where('level', $config->level)
        ->where('subject', $config->subject)
        ->where('is_new_concept', 'Y')
        ->pluck('worksheet')
        ->map(function ($worksheet) {
            return LessonPlan::wrapWorksheetNumber($worksheet);
        })
        ->toArray();
    
    // Parse the pattern (e.g., "2:3:2")
    $patternValues = array_map('intval', explode(':', $config->pattern));
    
    $lessonPlans = [];
    $currentWorksheet = $startingWorksheet;
    
    foreach ($allDaysInMonth as $index => $date) {
        $isClassDay = isset($classDatesSet[$date]) ? 'Y' : 'N';
        
        // For the first day, use the starting worksheet (already calculated correctly)
        if ($index === 0) {
            $worksheetForDay = $currentWorksheet;
        } else {
            // Calculate worksheet based on pattern for subsequent days
            $patternIndex = ($index - 1) % count($patternValues);
            $increment = $patternValues[$patternIndex];
            $previousWorksheet = $currentWorksheet;
            $currentWorksheet += $increment;
            $worksheetForDay = LessonPlan::wrapWorksheetNumber($currentWorksheet);
            
            // Check for level progression when wrapping occurs
            if ($previousWorksheet >= 190 && $worksheetForDay <= 10) {
                $this->handleLevelProgression(
                    $previousWorksheet,
                    $worksheetForDay,
                    $config->student_first_name,
                    $config->student_last_name,
                    $config->subject,
                    $month,
                    $year,
                    $date
                );
            }
            
            $currentWorksheet = $worksheetForDay; // Update for next iteration
        }
        
        // Check if this worksheet is a new concept
        $isNewConcept = in_array($worksheetForDay, $newConceptsWorksheets) ? 'Y' : 'N';
        
        $lessonPlans[] = [
            'month' => $month,
            'date' => $date,
            'subject' => $config->subject,
            'level' => $config->level,
            'worksheet' => $worksheetForDay,
            'student_first_name' => $config->student_first_name,
            'student_last_name' => $config->student_last_name,
            'new_concept' => $isNewConcept,
            'is_class_day' => $isClassDay,
            'year' => $year,
            'last_completed_page' => 0,
            'repeats' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
    
    // Insert all lesson plans for this student
    if (!empty($lessonPlans)) {
        LessonPlan::insert($lessonPlans);
    }
    
    return count($lessonPlans);
}


// Helper method to determine what the next pattern increment should be
private function getNextPatternIncrement($firstName, $lastName, $subject, $previousMonth, $previousYear, $patternValues)
{
    try {
        // Get all lesson plans from previous month in chronological order
        $previousMonthPlans = LessonPlan::where('student_first_name', $firstName)
            ->where('student_last_name', $lastName)
            ->where('subject', $subject)
            ->where('month', $previousMonth)
            ->where('year', $previousYear)
            ->orderBy('date')
            ->get();
        
        if ($previousMonthPlans->count() < 2) {
            // If less than 2 plans, start with first pattern value
            return $patternValues[0];
        }
        
        // Calculate increments used in previous month
        $incrementsUsed = [];
        for ($i = 1; $i < $previousMonthPlans->count(); $i++) {
            $previous = $previousMonthPlans[$i - 1]->worksheet;
            $current = $previousMonthPlans[$i]->worksheet;
            
            // Handle worksheet wrapping
            $increment = $current - $previous;
            if ($increment <= 0) {
                $increment = ($current + 200) - $previous; // Handle wrapping
            }
            
            $incrementsUsed[] = $increment;
        }
        
        // Determine the next pattern index based on the sequence
        $totalIncrementsUsed = count($incrementsUsed);
        $nextPatternIndex = $totalIncrementsUsed % count($patternValues);
        
        return $patternValues[$nextPatternIndex];
        
    } catch (\Exception $e) {
        Log::error("Error getting next pattern increment: " . $e->getMessage());
        return $patternValues[0]; // Fallback to first pattern value
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

    // Updated calculateWorksheetsWithRepeats to include level progression logic
    private function calculateWorksheetsWithRepeats(array $allEntries, int $startingEntryIndex, int $startingPage, array $patternValues, array $newConceptsWorksheets)
    {
        Log::info($startingEntryIndex);
        
        $calculatedValues = [];
        $REPEAT_PATTERN_LENGTH = 3;

        // Step 1: Calculate the normal sequence (without repeats)
        $normalSequence = [];
        
        for ($i = 0; $i < count($allEntries); $i++) {
            if ($i < $startingEntryIndex) {
                $normalSequence[] = 0; // Before starting point
            } elseif ($i === $startingEntryIndex) {
                $normalSequence[] = $startingPage; // Starting point
            } else {
                // Calculate based on previous worksheet + pattern increment
                $previousWorksheet = $normalSequence[$i - 1];
                $relativePosition = $i - $startingEntryIndex; // 1, 2, 3, 4, ...
                $patternIndex = ($relativePosition - 1) % count($patternValues);
                $increment = $patternValues[$patternIndex];
                
                $newWorksheet = LessonPlan::wrapWorksheetNumber($previousWorksheet + $increment);
                
                // Check for level progression
                if ($previousWorksheet >= 190 && $newWorksheet <= 10) {
                    $this->handleLevelProgression(
                        $previousWorksheet,
                        $newWorksheet,
                        $allEntries[$i]['student_first_name'],
                        $allEntries[$i]['student_last_name'],
                        $allEntries[$i]['subject'],
                        $allEntries[$i]['month'],
                        $allEntries[$i]['year'],
                        $allEntries[$i]['date']
                    );
                }
                
                $normalSequence[] = $newWorksheet;
            }
        }

        // Rest of the method remains the same...
        // Step 2: Build the final sequence by processing each position
        $finalSequence = [];
        $skipUntilIndex = -1;
        
        for ($i = 0; $i < count($allEntries); $i++) {
            // Check if we should skip this position
            if ($i <= $skipUntilIndex) {
                continue;
            }
            
            $entry = $allEntries[$i];
            $repeats = intval($entry['repeats'] ?? 0);
            
            if ($repeats > 0 && $i >= $startingEntryIndex) {
                // Get the 3-value pattern from normal sequence starting at this position
                $patternToRepeat = [];
                for ($p = 0; $p < $REPEAT_PATTERN_LENGTH && ($i + $p) < count($normalSequence); $p++) {
                    $patternToRepeat[] = $normalSequence[$i + $p];
                }
                
                // Add the original 3 values
                foreach ($patternToRepeat as $value) {
                    $finalSequence[] = $value;
                }
                
                // Add the repeated patterns
                for ($r = 0; $r < $repeats; $r++) {
                    foreach ($patternToRepeat as $value) {
                        $finalSequence[] = $value;
                    }
                }
                
                // Skip the next positions that were part of the original pattern
                $skipUntilIndex = $i + $REPEAT_PATTERN_LENGTH - 1;
                
            } else {
                // Normal case - just add the normal sequence value
                $finalSequence[] = $normalSequence[$i];
            }
        }
        
        // Step 3: Create the calculated values array
        for ($i = 0; $i < count($allEntries); $i++) {
            $entry = $allEntries[$i];
            $worksheetValue = ($i < count($finalSequence)) ? intval($finalSequence[$i]) : 0;
            
            $isNewConcept = 'N';
            if ($worksheetValue > 0 && in_array($worksheetValue, $newConceptsWorksheets)) {
                $isNewConcept = 'Y';
            }
            
            $calculatedValues[] = [
                'worksheetValue' => $worksheetValue,
                'lastCompletedValue' => ($i === $startingEntryIndex) ? $startingPage : 0,
                'newConcept' => $isNewConcept,
                'repeats' => intval($entry['repeats'] ?? 0)
            ];
        }
        
        return $calculatedValues;
    }

    // Keep all other existing methods unchanged...
public function generateCurrentMonthLessonPlans($month, $year)
{
    try {
        $currentMonth = $month;
        $currentYear = $year;
        
        Log::info("Generating lesson plans for {$currentMonth} {$currentYear}");
        
        // AUTO-CREATE STUDENT CONFIGURATIONS IF NOT PRESENT
        $this->ensureStudentConfigsExist($currentMonth, $currentYear);
        
        // Get all student configurations for the current month
        $currentMonthConfigs = StudentConfig::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->get();
        
        if ($currentMonthConfigs->isEmpty()) {
            return [
                'success' => false,
                'error' => "No student configurations found for {$currentMonth} {$currentYear}. Please add configurations first."
            ];
        }
        
        $processedStudents = [];
        $newEntriesCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        foreach ($currentMonthConfigs as $config) {
            try {
                $studentKey = $config->student_first_name . '|' . $config->student_last_name . '|' . $config->subject;
                
                // Skip if already processed this student-subject combination
                if (in_array($studentKey, $processedStudents)) {
                    continue;
                }
                $processedStudents[] = $studentKey;
                
                Log::info("Processing: {$config->student_first_name} {$config->student_last_name} - {$config->subject}");
                
                // Check if lesson plans already exist for this student/subject in current month
                $existingPlans = LessonPlan::where('student_first_name', $config->student_first_name)
                    ->where('student_last_name', $config->student_last_name)
                    ->where('subject', $config->subject)
                    ->where('month', $currentMonth)
                    ->where('year', $currentYear)
                    ->count();
                
                if ($existingPlans > 0) {
                    Log::info("Skipping - lesson plans already exist for this student/subject");
                    $skippedCount++;
                    continue;
                }
                
                // FIXED: Use the new method to get the starting worksheet based on pattern
                $startingWorksheet = $this->getNextWorksheetForNewMonth(
                    $config->student_first_name,
                    $config->student_last_name,
                    $config->subject,
                    $currentMonth,
                    $currentYear
                );
                
                Log::info("Starting worksheet calculated based on pattern: {$startingWorksheet}");
                
                // Generate lesson plans for this student/subject
                $generatedCount = $this->generateLessonPlansForStudent(
                    $config,
                    $currentMonth,
                    $currentYear,
                    $startingWorksheet
                );
                
                $newEntriesCount += $generatedCount;
                Log::info("Generated {$generatedCount} lesson plans");
                
            } catch (\Exception $e) {
                $errors[] = "Error processing {$config->student_first_name} {$config->student_last_name} - {$config->subject}: " . $e->getMessage();
                Log::error("Error processing student config: " . $e->getMessage());
                continue;
            }
        }
        
        $message = "Successfully generated {$newEntriesCount} lesson plan entries for {$currentMonth} {$currentYear}.";
        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} student-subject combinations that already have lesson plans.";
        }
        if (!empty($errors)) {
            $message .= " Encountered " . count($errors) . " errors during processing.";
            Log::warning("Errors during generation: " . implode('; ', $errors));
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
        
    } catch (\Exception $e) {
        Log::error('Error generating current month lesson plans: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error generating lesson plans: ' . $e->getMessage()
        ];
    }
}

/**
 * Ensure student configurations exist for the target month/year
 * If not, replicate from the previous month
 */
private function ensureStudentConfigsExist($targetMonth, $targetYear)
{
    try {
        // Check if configurations already exist for target month/year
        $existingConfigs = StudentConfig::where('month', $targetMonth)
            ->where('year', $targetYear)
            ->count();
        
            /*
        if ($existingConfigs > 0) {
            Log::info("Student configurations already exist for {$targetMonth} {$targetYear}");
            return;
        }*/
        
        Log::info("No configurations found for {$targetMonth} {$targetYear}, attempting to replicate from previous month");
        
        // Get previous month and year
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $targetMonthIndex = array_search($targetMonth, $monthNames);
        if ($targetMonthIndex === false) {
            Log::error("Invalid target month: {$targetMonth}");
            return;
        }
        
        $previousMonthIndex = $targetMonthIndex - 1;
        $previousYear = $targetYear;
        
        // Handle year transition
        if ($previousMonthIndex < 0) {
            $previousMonthIndex = 11; // December
            $previousYear = $targetYear - 1;
        }
        
        $previousMonth = $monthNames[$previousMonthIndex];
        
        Log::info("Looking for configurations from previous month: {$previousMonth} {$previousYear}");
        
        // Get configurations from previous month
        $previousConfigs = StudentConfig::where('month', $previousMonth)
            ->where('year', $previousYear)
            ->get();
        
        if ($previousConfigs->isEmpty()) {
            Log::warning("No configurations found in previous month {$previousMonth} {$previousYear} to replicate");
            return;
        }
        
        $replicatedCount = 0;
        $configsToInsert = [];
        
        // Prepare data for batch insert using your specified format
        foreach ($previousConfigs as $config) {
            $cleanData = [
                'student_first_name' => trim($config->student_first_name ?? ''),
                'student_last_name' => trim($config->student_last_name ?? ''),
                'subject' => trim($config->subject ?? ''),
                'class_day_1' => trim($config->class_day_1 ?? ''),
                'class_day_2' => trim($config->class_day_2 ?? ''),
                'pattern' => trim($config->pattern ?? ''),
                'level' => trim($config->level ?? ''),
                'month' => trim($targetMonth),
                'year' => (int)$targetYear,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $configsToInsert[] = $cleanData;
            $replicatedCount++;
            
            Log::info("Prepared config for {$cleanData['student_first_name']} {$cleanData['student_last_name']} - {$cleanData['subject']}");
        }
        
        // Batch insert all configurations
        if (!empty($configsToInsert)) {
            StudentConfig::insert($configsToInsert);
        }
        
        Log::info("Successfully replicated {$replicatedCount} student configurations from {$previousMonth} {$previousYear} to {$targetMonth} {$targetYear}");
        
    } catch (\Exception $e) {
        Log::error("Error ensuring student configs exist: " . $e->getMessage());
        // Don't throw the exception, just log it and continue
    }
}
    
    /**
     * Get the last worksheet number from the previous month for a specific student/subject
     */
    private function getLastWorksheetFromPreviousMonth($firstName, $lastName, $subject, $currentMonth, $currentYear)
    {
        // Get previous month
        $currentMonthNum = array_search($currentMonth, [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ]);
        
        if ($currentMonthNum === false) {
            Log::warning("Invalid current month: {$currentMonth}");
            return 1; // Default to worksheet 1
        }
        
        $previousMonthNum = $currentMonthNum - 1;
        $previousYear = $currentYear;
        
        // Handle year transition
        if ($previousMonthNum < 0) {
            $previousMonthNum = 11; // December
            $previousYear = $currentYear - 1;
        }
        
        $previousMonth = ['January', 'February', 'March', 'April', 'May', 'June',
                         'July', 'August', 'September', 'October', 'November', 'December'][$previousMonthNum];
        
        Log::info("Looking for previous month data: {$previousMonth} {$previousYear}");
        
        // Find the last lesson plan entry from previous month
        $lastEntry = LessonPlan::where('student_first_name', $firstName)
            ->where('student_last_name', $lastName)
            ->where('subject', $subject)
            ->where('month', $previousMonth)
            ->where('year', $previousYear)
            ->orderBy('date', 'desc')
            ->first();
        
        if ($lastEntry && $lastEntry->worksheet > 0) {
            Log::info("Found last worksheet from previous month: {$lastEntry->worksheet}");
            return $lastEntry->worksheet;
        }
        
        Log::info("No previous month data found, starting with worksheet 1");
        return 1; // Default starting point
    }
    
    public function getLessonPlanDataMultipleMonths(array $months, int $year, string $studentName = '', string $subject = '')
    {
        if($studentName==null)
        {
            $studentName="";
        }
        if (count($months) === 1) {
            return $this->getLessonPlanDataFromDatabase($months[0], $year, $studentName, $subject);
        }

        $allData = [];
        foreach ($months as $month) {
            $monthData = $this->getLessonPlanDataFromDatabase($month, $year, $studentName, $subject);
            $allData = array_merge($allData, $monthData);
        }

        return $allData;
    }

    public function getLessonPlanDataFromDatabase(string $month, int $year, string $studentName = '', string $subject = '')
    {
        $query = LessonPlan::where('month', $month)
            ->where('year', $year)
            ->orderBy('date');

        if ($studentName) {
            $nameParts = explode(' ', trim($studentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $query->where('student_first_name', $firstName);
            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }
        }

        if ($subject) {
            $query->where('subject', $subject);
        }

        return $query->get()->map(function ($row) {
            return [
                'month' => $row->month,
                'date' => $row->date,
                'subject' => $row->subject,
                'level' => $row->level,
                'worksheet' => $row->worksheet,
                'studentName' => $row->student_first_name,
                'studentLastName' => $row->student_last_name,
                'lastCompletedPage' => $row->last_completed_page,
                'repeats' => $row->repeats,
                'repeatPages'=>$row->repeat_pages,
                'newConcept' => $row->new_concept,
                'isClassDay' => $row->is_class_day,
                'year' => $row->year
            ];
        })->toArray();
    }

    public function getLessonPlanDataByDateRange(string $startDate, string $endDate, string $studentName = '', string $subject = '')
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        if ($start->gt($end)) {
            throw new \InvalidArgumentException('Start date must be before or equal to end date');
        }

        $query = LessonPlan::orderBy('year')
            ->orderBy('month')
            ->orderBy('date');

        if ($studentName) {
            $nameParts = explode(' ', trim($studentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $query->where('student_first_name', $firstName);
            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }
        }

        if ($subject) {
            $query->where('subject', $subject);
        }

        $allData = $query->get();

        // Filter by date range
        $filteredData = $allData->filter(function ($row) use ($start, $end) {
            $rowDate = $this->createDateFromRow($row);
            return $rowDate && $rowDate->between($start, $end);
        });

        return $filteredData->map(function ($row) {
            return [
                'month' => $row->month,
                'date' => $row->date,
                'subject' => $row->subject,
                'level' => $row->level,
                'worksheet' => $row->worksheet,
                'studentName' => $row->student_first_name,
                'studentLastName' => $row->student_last_name,
                'lastCompletedPage' => $row->last_completed_page,
                'repeats' => $row->repeats,
                'newConcept' => $row->new_concept,
                'isClassDay' => $row->is_class_day,
                'year' => $row->year
            ];
        })->values()->toArray();
    }

    private function createDateFromRow($row)
    {
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $monthIndex = array_search($row->month, $monthNames);
        if ($monthIndex === false) {
            return null;
        }

        return Carbon::create($row->year, $monthIndex + 1, $row->date);
    }

    public function updateLastCompletedPage(string $studentName, string $subject, string $month, int $date, int $lastCompletedPage)
{
    DB::beginTransaction();
    try {
        // Check if month is in the past
        if ($this->isMonthInPast($month, now()->year)) {
            return [
                'success' => false,
                'error' => "Cannot edit data for {$month} - this month has already passed and is read-only"
            ];
        }

        $nameParts = explode(' ', trim($studentName), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Find the specific entry to update
        $query = LessonPlan::where('student_first_name', $firstName)
            ->where('subject', $subject)
            ->where('month', $month)
            ->where('date', $date);

        if ($lastName) {
            $query->where('student_last_name', $lastName);
        }

        $entry = $query->first();

        if (!$entry) {
            return ['success' => false, 'error' => 'Matching lesson plan entry not found'];
        }

        $previousWorksheet = $entry->worksheet;
        $year = $entry->year;

        // Apply 200-page wrapping rule
        $wrappedPage = LessonPlan::wrapWorksheetNumber($lastCompletedPage);

        // Check if wrapping occurred and handle level progression
        if ($previousWorksheet >= 190 && $wrappedPage <= 10) {
            $this->handleLevelProgression(
                $previousWorksheet,
                $wrappedPage,
                $firstName,
                $lastName,
                $subject,
                $month,
                $year,
                $date
            );
        }

        // Update the specific entry
        $entry->update([
            'last_completed_page' => $wrappedPage,
            'worksheet' => $wrappedPage
        ]);

        // FIXED: Recalculate ALL future dates (not just current month)
        $this->recalculateAllFutureDates($firstName, $lastName, $subject, $month, $year, $date, $wrappedPage);

        DB::commit();

        return [
            'success' => true,
            'message' => 'Last completed page updated and all future dates recalculated'
        ];

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating last completed page: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error updating last completed page: ' . $e->getMessage()];
    }
}

private function recalculateAllFutureDates(
    string $firstName, 
    string $lastName, 
    string $subject, 
    string $startMonth, 
    int $startYear, 
    int $startDate, 
    int $startingWorksheet
) {
    try {
        // Create a date object for comparison
        $startDateTime = Carbon::createFromFormat('Y-F-j', "{$startYear}-{$startMonth}-{$startDate}");
        
        // Get ALL lesson plans for this student/subject from the starting date onwards
        $allFuturePlans = LessonPlan::where('student_first_name', $firstName)
            ->where('student_last_name', $lastName)
            ->where('subject', $subject)
            ->get()
            ->filter(function ($plan) use ($startDateTime) {
                // Create date for each plan and compare
                try {
                    $planDateTime = Carbon::createFromFormat('Y-F-j', "{$plan->year}-{$plan->month}-{$plan->date}");
                    return $planDateTime->greaterThanOrEqualTo($startDateTime);
                } catch (\Exception $e) {
                    Log::warning("Invalid date format in lesson plan: {$plan->year}-{$plan->month}-{$plan->date}");
                    return false;
                }
            })
            ->sortBy(function ($plan) {
                // Sort by actual date
                return Carbon::createFromFormat('Y-F-j', "{$plan->year}-{$plan->month}-{$plan->date}");
            })
            ->values(); // Reset array keys

        if ($allFuturePlans->isEmpty()) {
            Log::info("No future lesson plans found for recalculation");
            return;
        }

        // Get student configuration for pattern
        $studentConfig = StudentConfig::where('student_first_name', $firstName)
            ->where('student_last_name', $lastName)
            ->where('subject', $subject)
            ->where('month', $startMonth)
            ->where('year', $startYear)
            ->first();

        if (!$studentConfig) {
            Log::warning("Student config not found for recalculation");
            return;
        }

        $patternValues = array_map('intval', explode(':', $studentConfig->pattern));
        $currentWorksheet = $startingWorksheet;
        $patternIndex = 0; // Start from beginning of pattern
        
        Log::info("Starting recalculation with worksheet: {$currentWorksheet}, Pattern: " . implode(':', $patternValues));

        foreach ($allFuturePlans as $index => $plan) {
            if ($index === 0) {
                // First entry is the starting point - just update it with the new worksheet
                $plan->worksheet = $currentWorksheet;
                $plan->last_completed_page = $currentWorksheet;
                Log::info("Updated starting point: Date {$plan->date} {$plan->month} {$plan->year} - Worksheet: {$currentWorksheet}");
            } else {
                // For all subsequent entries, calculate based on pattern
                $increment = $patternValues[$patternIndex];
                $previousWorksheet = $currentWorksheet;
                $currentWorksheet += $increment;
                $currentWorksheet = LessonPlan::wrapWorksheetNumber($currentWorksheet);
                
                Log::info("Pattern step {$patternIndex}: +{$increment} = {$currentWorksheet} (Date: {$plan->date} {$plan->month} {$plan->year})");
                
                // Check for level progression
                if ($previousWorksheet >= 190 && $currentWorksheet <= 10) {
                    $this->handleLevelProgression(
                        $previousWorksheet,
                        $currentWorksheet,
                        $firstName,
                        $lastName,
                        $subject,
                        $plan->month,
                        $plan->year,
                        $plan->date
                    );
                }
                
                $plan->worksheet = $currentWorksheet;
                $plan->last_completed_page = 0; // Reset for future dates
                
                // Move to next pattern step
                $patternIndex = ($patternIndex + 1) % count($patternValues);
            }

            // Check if this is a new concept
            $newConceptsWorksheets = NewConcept::where('level', $plan->level)
                ->where('subject', $subject)
                ->where('is_new_concept', 'Y')
                ->pluck('worksheet')
                ->map(function ($worksheet) {
                    return LessonPlan::wrapWorksheetNumber($worksheet);
                })
                ->toArray();

            $plan->new_concept = in_array($currentWorksheet, $newConceptsWorksheets) ? 'Y' : 'N';
            $plan->save();
        }

        Log::info("Successfully recalculated {$allFuturePlans->count()} future lesson plans from {$startDate} {$startMonth} {$startYear}");

    } catch (\Exception $e) {
        Log::error("Error recalculating future dates: " . $e->getMessage());
        Log::error("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

    public function updateLevel(string $studentName, string $subject, string $month, int $date, string $newLevel)
    {
        DB::beginTransaction();
        try {
            $currentYear = now()->year;
            
            if ($this->isMonthInPast($month, $currentYear)) {
                return [
                    'success' => false,
                    'error' => "Cannot edit data for {$month} - this month has already passed and is read-only"
                ];
            }

            $nameParts = explode(' ', trim($studentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Get all entries from target date onwards
            $query = LessonPlan::where('student_first_name', $firstName)
            ->where('student_last_name',$lastName)
                ->where('subject', $subject)
                ->orderBy('year')
                ->orderBy('month')
                ->orderBy('date');

                StudentConfig::where('student_first_name', $firstName)
                ->where('student_last_name',$lastName)
                ->where('subject', $subject)
                ->where('year',$currentYear)
                ->where('month',$month)
                ->update(["level"=>$newLevel]);
                

            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }

            $allEntries = $query->get();

            // Filter entries from target date onwards and not in past
            $targetDate = $this->createDateFromRow((object)[
                'month' => $month,
                'date' => $date,
                'year' => $currentYear
            ]);

            $entriesToUpdate = $allEntries->filter(function ($entry) use ($targetDate) {
                $entryDate = $this->createDateFromRow($entry);
                return $entryDate && $entryDate->gte($targetDate) && !$this->isMonthInPast($entry->month, $entry->year);
            });

            if ($entriesToUpdate->isEmpty()) {
                return ['success' => false, 'error' => 'No matching entries found to update'];
            }

            // Get new concepts for the new level
            $newConceptsWorksheets = NewConcept::where('level', $newLevel)
                ->where('subject', $subject)
                ->where('is_new_concept', 'Y')
                ->pluck('worksheet')
                ->map(function ($worksheet) {
                    return LessonPlan::wrapWorksheetNumber($worksheet);
                })
                ->toArray();

            // Update all entries
            foreach ($entriesToUpdate as $entry) {
                $wrappedWorksheet = LessonPlan::wrapWorksheetNumber($entry->worksheet);
                $isNewConcept = in_array($wrappedWorksheet, $newConceptsWorksheets) ? 'Y' : 'N';

                $entry->update([
                    'level' => $newLevel,
                    'worksheet' => $wrappedWorksheet,
                    'new_concept' => $isNewConcept
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully updated level to {$newLevel} for {$entriesToUpdate->count()} entries from {$month} {$date} onwards"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating level: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error updating level: ' . $e->getMessage()];
        }
    }



    public function deleteStudentData(string $studentName, string $subject, string $month, int $year)
    {
        DB::beginTransaction();
        try {
            $nameParts = explode(' ', trim($studentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $query = LessonPlan::where('student_first_name', $firstName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year);

            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }

            $deletedCount = $query->count();
            $query->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} entries for {$studentName} - {$subject} in {$month} {$year}"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting student data: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error deleting student data: ' . $e->getMessage()];
        }
    }

    private function isMonthInPast(string $month, int $year)
    {
        $now = Carbon::now();
        $currentMonth = $now->month - 1; // 0-based
        $currentYear = $now->year;

        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $targetMonthNum = array_search($month, $monthNames);
        if ($targetMonthNum === false) {
            return false;
        }

        if ($year < $currentYear) {
            return true;
        }

        if ($year === $currentYear && $targetMonthNum < $currentMonth) {
            return true;
        }

        return false;
    }

    private function resetAndRecalculateMonthWithRepeats(string $fullStudentName, string $subject, string $month, int $year, int $startDate, int $startingPage)
    {
        try {
            $studentConfig = StudentConfig::where('student_first_name', explode(' ', $fullStudentName)[0])
                ->where('student_last_name', explode(' ', $fullStudentName)[1])
                ->where('subject', $subject)
                ->where('month', $month)
                ->first();

            if (!$studentConfig) {
                Log::error('Student configuration not found');
                return;
            }

            $nameParts = explode(' ', trim($fullStudentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Get ALL entries for this month
            $query = LessonPlan::where('student_first_name', $firstName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year)
                ->where('date','>=',$startDate)
                ->orderBy('date');

            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }

            $allEntries = $query->get();

            if ($allEntries->isEmpty()) {
                return;
            }

            $patternValues = explode(':', $studentConfig->pattern);
            $patternValues = array_map('intval', $patternValues);

            // Get new concepts
            $newConceptsWorksheets = NewConcept::where('level', $studentConfig->level)
                ->where('subject', $studentConfig->subject)
                ->where('is_new_concept', 'Y')
                ->pluck('worksheet')
                ->map(function ($worksheet) {
                    return LessonPlan::wrapWorksheetNumber($worksheet);
                })
                ->toArray();

            // Find starting entry
            $startingEntryIndex = $allEntries->search(function ($entry) use ($startDate) {
                return $entry->date == $startDate;
            });

            if ($startingEntryIndex === false) {
                Log::error('Starting entry not found');
                return;
            }
            
            Log::info($patternValues);

            // Calculate worksheets with repeats logic
            $calculatedValues = $this->calculateWorksheetsWithRepeats(
                $allEntries->toArray(),
                $startingEntryIndex,
                $startingPage,
                $patternValues,
                $newConceptsWorksheets
            );

            // Update all entries with calculated values
            foreach ($calculatedValues as $i => $calc) {
                $entry = $allEntries[$i];
                $entry->update([
                    'worksheet' => $calc['worksheetValue'],
                    'last_completed_page' => ($i === $startingEntryIndex) ? $startingPage : $calc['lastCompletedValue'],
                    'new_concept' => $calc['newConcept']
                ]);
            }

            // Update future months to continue from this month's final worksheet
            $finalWorksheet = end($calculatedValues)['worksheetValue'];
            if ($finalWorksheet > 0) {
                $this->updateFutureMonthsWorksheets($fullStudentName, $subject, $month, $year, $finalWorksheet);
            }

        } catch (\Exception $e) {
            Log::error('Error resetting month with repeats: ' . $e->getMessage());
        }
    }

    private function recalculateWithSimpleRepeats(string $fullStudentName, string $subject, string $month, int $year)
    {
        try {
            $nameParts = explode(' ', trim($fullStudentName), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Get all entries for this student/subject/month
            $query = LessonPlan::where('student_first_name', $firstName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->where('year', $year)
                ->orderBy('date');

            if ($lastName) {
                $query->where('student_last_name', $lastName);
            }

            $allEntries = $query->get();

            if ($allEntries->isEmpty()) {
                return ['success' => false, 'error' => 'No entries found'];
            }

            // Get student config for pattern
            $studentConfig = StudentConfig::where('student_first_name', $firstName)
                ->where('subject', $subject)
                ->where('month', $month)
                ->first();

            if (!$studentConfig) {
                return ['success' => false, 'error' => 'Student config not found'];
            }

            $patternValues = array_map('intval', explode(':', $studentConfig->pattern));

            // Find starting point logic
            $startingIndex = -1;
            $startingPage = 0;

            // Look for entry with last_completed_page > 0
            $startingEntry = $allEntries->first(function ($entry) {
                return $entry->last_completed_page > 0;
            });

            if ($startingEntry) {
                $startingIndex = $allEntries->search(function ($entry) use ($startingEntry) {
                    return $entry->id === $startingEntry->id;
                });
                $startingPage = $startingEntry->last_completed_page;
            } else {
                // Fallback: start from beginning with worksheet 1
                $startingIndex = 0;
                $startingPage = 1;
            }

            // Calculate new worksheets with repeats
            $calculationResult = $this->calculateSimpleRepeats($allEntries->toArray(), $startingIndex, $startingPage, $patternValues);
            $newWorksheets = $calculationResult['sequence'];

            // Update all entries
            foreach ($allEntries as $i => $entry) {
                $newWorksheet = $newWorksheets[$i] ?? 0;
                $entry->update(['worksheet' => $newWorksheet]);
            }

            return ['success' => true, 'message' => 'Recalculation completed with repeats logic'];

        } catch (\Exception $e) {
            Log::error('Error in recalculateWithSimpleRepeats: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function calculateSimpleRepeats(array $allEntries, int $startingIndex, int $startingPage, array $patternValues)
    {
        // Step 1: Build normal sequence
        $normalSeq = array_fill(0, count($allEntries), 0);
        $currentWorksheet = intval($startingPage);
        
        for ($i = $startingIndex; $i < count($allEntries); $i++) {
            if ($i === $startingIndex) {
                $normalSeq[$i] = $currentWorksheet;
            } else {
                $patternIndex = ($i - $startingIndex - 1) % count($patternValues);
                $previousWorksheet = $currentWorksheet;
                $currentWorksheet += $patternValues[$patternIndex];
                $currentWorksheet = LessonPlan::wrapWorksheetNumber($currentWorksheet);
                
                // Check for level progression during calculation
                if ($previousWorksheet >= 190 && $currentWorksheet <= 10) {
                    $this->handleLevelProgression(
                        $previousWorksheet,
                        $currentWorksheet,
                        $allEntries[$i]['student_first_name'],
                        $allEntries[$i]['student_last_name'],
                        $allEntries[$i]['subject'],
                        $allEntries[$i]['month'],
                        $allEntries[$i]['year'],
                        $allEntries[$i]['date']
                    );
                }
                
                $normalSeq[$i] = $currentWorksheet;
            }
        }

        // Step 2: Find repeat settings
        $firstRepeatIndex = -1;
        $repeatValue = 0;
        $stopRepeatIndex = -1;
        
        for ($i = 0; $i < count($allEntries); $i++) {
            $entryRepeats = intval($allEntries[$i]['repeats'] ?? 0);
            
            if ($entryRepeats > 0 && $firstRepeatIndex === -1) {
                $firstRepeatIndex = $i;
                $repeatValue = $entryRepeats;
            }
            
            if ($entryRepeats === -1 && $firstRepeatIndex !== -1 && $stopRepeatIndex === -1) {
                $stopRepeatIndex = $i;
            }
        }

        if ($firstRepeatIndex === -1) {
            return ['sequence' => $normalSeq, 'repeatValue' => 0];
        }

        // Step 3: Apply repeats
        $result = $normalSeq;
        
        if ($stopRepeatIndex !== -1) {
            // Process with stop signal
            $continueWorksheet = $result[$stopRepeatIndex - 1] ?? $normalSeq[$stopRepeatIndex - 1];
            
            for ($i = $stopRepeatIndex; $i < count($allEntries); $i++) {
                $patternIndex = ($i - $startingIndex - 1) % count($patternValues);
                $previousWorksheet = $continueWorksheet;
                $continueWorksheet += $patternValues[$patternIndex];
                $continueWorksheet = LessonPlan::wrapWorksheetNumber($continueWorksheet);
                
                // Check for level progression
                if ($previousWorksheet >= 190 && $continueWorksheet <= 10) {
                    $this->handleLevelProgression(
                        $previousWorksheet,
                        $continueWorksheet,
                        $allEntries[$i]['student_first_name'],
                        $allEntries[$i]['student_last_name'],
                        $allEntries[$i]['subject'],
                        $allEntries[$i]['month'],
                        $allEntries[$i]['year'],
                        $allEntries[$i]['date']
                    );
                }
                
                $result[$i] = $continueWorksheet;
            }
            
            return ['sequence' => $result, 'repeatValue' => 0];
        } else {
            // Normal repeat processing
            $repeatSectionNormal = array_slice($normalSeq, $firstRepeatIndex);
            
            $resultIndex = $firstRepeatIndex;
            $sectionIndex = 0;
            
            while ($sectionIndex < count($repeatSectionNormal) && $resultIndex < count($allEntries)) {
                // Get 3-block
                $block = array_slice($repeatSectionNormal, $sectionIndex, count($patternValues));
                
                if (empty($block)) break;
                
                // Add original block
                foreach ($block as $value) {
                    if ($resultIndex < count($allEntries)) {
                        $result[$resultIndex] = $value;
                        $resultIndex++;
                    }
                }
                
                // Add repeats
                for ($r = 0; $r < $repeatValue && $resultIndex < count($allEntries); $r++) {
                    foreach ($block as $value) {
                        if ($resultIndex < count($allEntries)) {
                            $result[$resultIndex] = $value;
                            $resultIndex++;
                        }
                    }
                }
                
                $sectionIndex += count($patternValues);
            }
            
            return ['sequence' => $result, 'repeatValue' => $repeatValue];
        }
    }

    private function updateFutureMonthsWorksheets(string $fullStudentName, string $subject, string $currentMonth, int $currentYear, int $finalWorksheet)
    {
        // Implementation for updating future months would go here
        // This is a simplified version - in a full implementation you'd need to handle
        // the complex logic for continuing patterns across months
    }

    private function getNextWorksheetForNewMonth($firstName, $lastName, $subject, $currentMonth, $currentYear)
{
    // Get previous month
    $currentMonthNum = array_search($currentMonth, [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ]);
    
    if ($currentMonthNum === false) {
        Log::warning("Invalid current month: {$currentMonth}");
        return 1; // Default to worksheet 1
    }
    
    $previousMonthNum = $currentMonthNum - 1;
    $previousYear = $currentYear;
    
    // Handle year transition
    if ($previousMonthNum < 0) {
        $previousMonthNum = 11; // December
        $previousYear = $currentYear - 1;
    }
    
    $previousMonth = ['January', 'February', 'March', 'April', 'May', 'June',
                     'July', 'August', 'September', 'October', 'November', 'December'][$previousMonthNum];
    
    Log::info("Looking for previous month data: {$previousMonth} {$previousYear}");
    
    // Find the last lesson plan entry from previous month
    $lastEntry = LessonPlan::where('student_first_name', $firstName)
        ->where('student_last_name', $lastName)
        ->where('subject', $subject)
        ->where('month', $previousMonth)
        ->where('year', $previousYear)
        ->orderBy('date', 'desc')
        ->first();
    
    if (!$lastEntry || $lastEntry->worksheet <= 0) {
        Log::info("No previous month data found, starting with worksheet 1");
        return 1; // Default starting point
    }

    // Get the student's pattern to calculate the next worksheet
    $studentConfig = StudentConfig::where('student_first_name', $firstName)
        ->where('student_last_name', $lastName)
        ->where('subject', $subject)
        ->where('month', $currentMonth)
        ->where('year', $currentYear)
        ->first();

    if (!$studentConfig) {
        Log::warning("No student config found for pattern calculation");
        return $lastEntry->worksheet + 1; // Simple increment as fallback
    }

    // Parse the pattern
    $patternValues = array_map('intval', explode(':', $studentConfig->pattern));
    
    // Count the number of days in the previous month to determine pattern position
    $daysInPrevMonth = cal_days_in_month(CAL_GREGORIAN, $previousMonthNum + 1, $previousYear);
    
    // The pattern index for the first day of new month
    // We need to continue the pattern from where it left off
    $patternIndex = ($daysInPrevMonth - 1) % count($patternValues);
    $increment = $patternValues[$patternIndex];
    
    // Calculate the next worksheet
    $nextWorksheet = LessonPlan::wrapWorksheetNumber($lastEntry->worksheet + $increment);
    
    Log::info("Previous month ended at worksheet {$lastEntry->worksheet}, pattern increment is {$increment}, starting new month with worksheet {$nextWorksheet}");
    
    return $nextWorksheet;
}

private function getMonthOrder($month)
{
    $monthNames = [
        'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4, 
        'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8, 
        'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
    ];
    
    return $monthNames[$month] ?? 0;
}

// Updated Service function - Fixed to handle proper pattern retrieval and date-specific logic
public function updateRepeats(string $studentName, string $subject, string $month, int $date, int $pages, int $repeats)
{
    DB::beginTransaction();
    try {
        $currentYear = now()->year;
        
        if ($this->isMonthInPast($month, $currentYear)) {
            return [
                'success' => false,
                'error' => "Cannot edit data for {$month} - this month has already passed and is read-only"
            ];
        }

        $nameParts = explode(' ', trim($studentName), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Find and update the specific entry
        $query = LessonPlan::where('student_first_name', $firstName)
            ->where('subject', $subject)
            ->where('month', $month)
            ->where('date', $date)
            ->where('year', $currentYear);

        if ($lastName) {
            $query->where('student_last_name', $lastName);
        }

        $entry = $query->first();

        if (!$entry) {
            return ['success' => false, 'error' => 'Matching lesson plan entry not found'];
        }

        // Update the pages and repeats values
        $entry->update([
            'repeat_pages' => $pages,
            'repeats' => $repeats
        ]);

        // Recalculate from this date forward (not the whole month)
        $this->recalculateFromDate($studentName, $subject, $month, $date, $currentYear);

        DB::commit();

        return [
            'success' => true,
            'message' => 'Pages and repeats updated and future dates recalculated'
        ];

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating repeats: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error updating repeats: ' . $e->getMessage()];
    }
}

// New method to recalculate only from the specified date forward
private function recalculateFromDate(string $studentName, string $subject, string $month, int $startDate, int $year)
{
    $nameParts = explode(' ', trim($studentName), 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? '';

    // Get all entries from the start date forward for this student/subject
    $query = LessonPlan::where('student_first_name', $firstName)
        ->where('subject', $subject)
        ->where('year', $year)
        ->where(function($q) use ($month, $startDate) {
            $q->where(function($subQ) use ($month, $startDate) {
                // Same month, date >= startDate
                $subQ->where('month', $month)->where('date', '>=', $startDate);
            })->orWhere(function($subQ) use ($month) {
                // Future months
                $monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
                $currentMonthIndex = array_search($month, $monthNames);
                $futureMonths = array_slice($monthNames, $currentMonthIndex + 1);
                if (!empty($futureMonths)) {
                    $subQ->whereIn('month', $futureMonths);
                }
            });
        })
        ->orderBy(DB::raw("
            CASE month 
                WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
            END
        "))
        ->orderBy('date');

    if ($lastName) {
        $query->where('student_last_name', $lastName);
    }

    $entries = $query->get();
    
    if ($entries->isEmpty()) {
        return;
    }

    // Get the student's pattern from StudentConfig
    $patternValues = $this->getStudentPattern($firstName, $lastName, $subject, $month, $year);
    
    if (empty($patternValues)) {
        Log::warning("No pattern found for student {$firstName} {$lastName}, subject {$subject}");
        return;
    }

    // Convert to array format for calculation
    $entriesArray = $entries->map(function ($entry) {
        return [
            'student_first_name' => $entry->student_first_name,
            'student_last_name' => $entry->student_last_name,
            'subject' => $entry->subject,
            'month' => $entry->month,
            'year' => $entry->year,
            'date' => $entry->date,
            'worksheet' => $entry->worksheet,
            'repeat_pages' => $entry->repeat_pages ?? 0,
            'repeats' => $entry->repeats ?? 0
        ];
    })->toArray();

    // Get starting worksheet from the first entry (the date where repeat was set)
    $startingWorksheet = $entries->first()->worksheet;
    
    // Calculate new sequence starting from index 0 (since we're only processing from startDate forward)
    $result = $this->calculateNewRepeats($entriesArray, 0, $startingWorksheet, $patternValues);
    
    // Update all entries with new worksheet numbers
    foreach ($entries as $index => $entry) {
        if (isset($result['sequence'][$index])) {
            $entry->update(['worksheet' => $result['sequence'][$index]]);
        }
    }
}

// Helper method to get student's pattern from StudentConfig
private function getStudentPattern(string $firstName, string $lastName, string $subject, string $month, int $year): array
{
    $query = StudentConfig::where('student_first_name', $firstName)
        ->where('subject', $subject)
        ->where('month', $month)
        ->where('year', $year);

    if ($lastName) {
        $query->where('student_last_name', $lastName);
    }

    $config = $query->first();
    
    if (!$config || !$config->pattern) {
        // Fallback: try to get pattern from any month for this student/subject/year
        $fallbackQuery = StudentConfig::where('student_first_name', $firstName)
            ->where('subject', $subject)
            ->where('year', $year);
            
        if ($lastName) {
            $fallbackQuery->where('student_last_name', $lastName);
        }
        
        $config = $fallbackQuery->first();
    }
    
    if (!$config || !$config->pattern) {
        Log::warning("No pattern configuration found for {$firstName} {$lastName} - {$subject}");
        return [];
    }
    
    // Parse pattern string (e.g., "4,3,3" or "4:3:3")
    $patternStr = str_replace([':', ';'], ',', $config->pattern);
    $pattern = array_map('intval', explode(',', $patternStr));
    
    return array_filter($pattern); // Remove any zero or invalid values
}

// Updated calculation method with proper pattern handling
private function calculateNewRepeats(array $allEntries, int $startingIndex, int $startingPage, array $patternValues)
{
    if (empty($patternValues)) {
        Log::warning("Empty pattern values provided to calculateNewRepeats");
        return ['sequence' => array_fill(0, count($allEntries), $startingPage), 'repeatPages' => 0, 'repeatCount' => 0];
    }

    // Step 1: Build normal sequence
    $normalSeq = array_fill(0, count($allEntries), 0);
    $currentWorksheet = intval($startingPage);
    
    for ($i = $startingIndex; $i < count($allEntries); $i++) {
        if ($i === $startingIndex) {
            $normalSeq[$i] = $currentWorksheet;
        } else {
            $patternIndex = ($i - $startingIndex - 1) % count($patternValues);
            $previousWorksheet = $currentWorksheet;
            $currentWorksheet += $patternValues[$patternIndex];
            $currentWorksheet = LessonPlan::wrapWorksheetNumber($currentWorksheet);
            
            // Check for level progression during calculation
            if ($previousWorksheet >= 190 && $currentWorksheet <= 10) {
                $this->handleLevelProgression(
                    $previousWorksheet,
                    $currentWorksheet,
                    $allEntries[$i]['student_first_name'],
                    $allEntries[$i]['student_last_name'],
                    $allEntries[$i]['subject'],
                    $allEntries[$i]['month'],
                    $allEntries[$i]['year'],
                    $allEntries[$i]['date']
                );
            }
            
            $normalSeq[$i] = $currentWorksheet;
        }
    }

    // Step 2: Find repeat settings (only look at the first entry since we're starting from repeat date)
    $repeatEntryIndex = -1;
    $repeatPages = 0;
    $repeatCount = 0;
    
    // Check if the starting entry has repeat settings
    if (isset($allEntries[0])) {
        $entryRepeatPages = intval($allEntries[0]['repeat_pages'] ?? 0);
        $entryRepeats = intval($allEntries[0]['repeats'] ?? 0);
        
        if ($entryRepeatPages > 0 && $entryRepeats > 0) {
            $repeatEntryIndex = 0;
            $repeatPages = $entryRepeatPages;
            $repeatCount = $entryRepeats;
        }
    }

    if ($repeatEntryIndex === -1) {
        return ['sequence' => $normalSeq, 'repeatPages' => 0, 'repeatCount' => 0];
    }

    // Step 3: Apply new repeat logic
    $result = $normalSeq;
    
    // Find all worksheets within the repeat pages range from the repeat entry
    $repeatStartWorksheet = $normalSeq[$repeatEntryIndex];
    $repeatWorksheets = [];
    
    // Collect worksheets that fall within the repeat pages range
    for ($i = $repeatEntryIndex; $i < count($normalSeq); $i++) {
        $currentWs = $normalSeq[$i];
        
        // Check if this worksheet is within the repeat pages range
        if ($this->isWithinRepeatRange($repeatStartWorksheet, $currentWs, $repeatPages)) {
            $repeatWorksheets[] = $currentWs;
        } else {
            // Once we've gone beyond the repeat range, stop collecting
            break;
        }
    }
    
    if (empty($repeatWorksheets)) {
        return ['sequence' => $normalSeq, 'repeatPages' => 0, 'repeatCount' => 0];
    }
    
    // Now rebuild the sequence starting from the repeat entry
    $resultIndex = $repeatEntryIndex;
    
    // Add the original sequence of worksheets within range
    foreach ($repeatWorksheets as $worksheet) {
        if ($resultIndex < count($result)) {
            $result[$resultIndex] = $worksheet;
            $resultIndex++;
        }
    }
    
    // Add the repeated cycles
    for ($cycle = 0; $cycle < $repeatCount && $resultIndex < count($result); $cycle++) {
        foreach ($repeatWorksheets as $worksheet) {
            if ($resultIndex < count($result)) {
                $result[$resultIndex] = $worksheet;
                $resultIndex++;
            }
        }
    }
    
    // Continue with normal progression after repeats
    if ($resultIndex < count($result)) {
        $lastRepeatWorksheet = end($repeatWorksheets);
        $continueWorksheet = $lastRepeatWorksheet;
        
        for ($i = $resultIndex; $i < count($result); $i++) {
            // Calculate the next worksheet in normal progression
            $patternIndex = ($i - $startingIndex - 1) % count($patternValues);
            $previousWorksheet = $continueWorksheet;
            $continueWorksheet += $patternValues[$patternIndex];
            $continueWorksheet = LessonPlan::wrapWorksheetNumber($continueWorksheet);
            
            // Check for level progression
            if ($previousWorksheet >= 190 && $continueWorksheet <= 10) {
                $this->handleLevelProgression(
                    $previousWorksheet,
                    $continueWorksheet,
                    $allEntries[$i]['student_first_name'],
                    $allEntries[$i]['student_last_name'],
                    $allEntries[$i]['subject'],
                    $allEntries[$i]['month'],
                    $allEntries[$i]['year'],
                    $allEntries[$i]['date']
                );
            }
            
            $result[$i] = $continueWorksheet;
        }
    }
    
    return ['sequence' => $result, 'repeatPages' => $repeatPages, 'repeatCount' => $repeatCount];
}

// Helper method to check if a worksheet is within the repeat range (unchanged)
private function isWithinRepeatRange(int $startWorksheet, int $currentWorksheet, int $repeatPages): bool
{
    // Handle worksheet wrapping (1-200 range)
    if ($startWorksheet <= $currentWorksheet) {
        // No wrapping case
        return ($currentWorksheet - $startWorksheet + 1) <= $repeatPages;
    } else {
        // Wrapping case (e.g., start at 195, current at 5)
        $distance = (200 - $startWorksheet + 1) + $currentWorksheet;
        return $distance <= $repeatPages;
    }
}
}