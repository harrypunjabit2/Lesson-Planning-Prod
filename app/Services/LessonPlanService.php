<?php

// app/Services/LessonPlanService.php
namespace App\Services;

use App\Models\LessonPlan;
use App\Models\StudentConfig;
use App\Models\NewConcept;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonPlanService
{
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

            // Apply 200-page wrapping rule
            $wrappedPage = LessonPlan::wrapWorksheetNumber($lastCompletedPage);

            // Update the specific entry
            $entry->update([
                'last_completed_page' => $wrappedPage,
                'worksheet' => $wrappedPage
            ]);

            // Reset and recalculate month with repeats
            $this->resetAndRecalculateMonthWithRepeats($studentName, $subject, $month, $entry->year, $date, $wrappedPage);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Last completed page updated and month recalculated with repeats logic'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating last completed page: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error updating last completed page: ' . $e->getMessage()];
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
                ->where('subject', $subject)
                ->orderBy('year')
                ->orderBy('month')
                ->orderBy('date');

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

    public function updateRepeats(string $studentName, string $subject, string $month, int $date, int $repeats)
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

            // Update the repeats value
            $entry->update(['repeats' => $repeats]);

            // Recalculate entire month with new repeats logic
            $this->recalculateWithSimpleRepeats($studentName, $subject, $month, $currentYear);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Repeats updated and month recalculated'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating repeats: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Error updating repeats: ' . $e->getMessage()];
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

    private function calculateWorksheetsWithRepeats(array $allEntries, int $startingEntryIndex, int $startingPage, array $patternValues, array $newConceptsWorksheets)
    {
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
                $normalSequence[] = $newWorksheet;
            }
        }

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
        //this is test j
        $normalSeq = array_fill(0, count($allEntries), 0);
        $currentWorksheet = intval($startingPage);
        
        for ($i = $startingIndex; $i < count($allEntries); $i++) {
            if ($i === $startingIndex) {
                $normalSeq[$i] = $currentWorksheet;
            } else {
                $patternIndex = ($i - $startingIndex - 1) % count($patternValues);
                $currentWorksheet += $patternValues[$patternIndex];
                $currentWorksheet = LessonPlan::wrapWorksheetNumber($currentWorksheet);
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
                $continueWorksheet += $patternValues[$patternIndex];
                $continueWorksheet = LessonPlan::wrapWorksheetNumber($continueWorksheet);
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
}

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

            $existingEntries = LessonPlan::select('month', 'date', 'student_first_name', 'student_last_name', 'subject', 'year')
                ->get()
                ->map(function ($plan) {
                    $fullName = trim($plan->student_first_name . ' ' . $plan->student_last_name);
                    return "{$plan->month}-{$plan->date}-{$fullName}-{$plan->subject}-{$plan->year}";
                })
                ->toArray();

            $existingSet = array_flip($existingEntries);
            $newEntries = [];

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
                    $entryKey = "{$studentConfig->month}-{$date}-{$fullStudentName}-{$studentConfig->subject}-{$studentConfig->year}";
                    
                    if (isset($existingSet[$entryKey])) {
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

            if (!empty($newEntries)) {
                // Insert in batches to avoid memory issues
                $chunks = array_chunk($newEntries, 1000);
                foreach ($chunks as $chunk) {
                    LessonPlan::insert($chunk);
                }
                
                DB::commit();
                return ['success' => true, 'message' => 'Successfully added ' . count($newEntries) . ' new lesson plan entries'];
            } else {
                DB::commit();
                return ['success' => true, 'message' => 'No new entries to add - all students already have lesson plans'];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error setting up student data: ' . $e->getMessage());
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
}