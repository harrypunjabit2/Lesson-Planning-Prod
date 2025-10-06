<?php

// app/Http/Controllers/DuplicateConfigController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudentConfig;
use App\Models\SubjectLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateConfigController extends Controller
{
    public function index()
    {
        return view('admin.duplicate-config.index');
    }

    /**
     * Find and categorize duplicates
     */
    public function findDuplicates()
    {
        try {
            // Find all potential duplicates grouped by student/subject/month/year
            $duplicates = StudentConfig::select(
                'student_first_name',
                'student_last_name',
                'subject',
                'month',
                'year',
                DB::raw('COUNT(*) as count'),
                DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'),
                DB::raw('GROUP_CONCAT(pattern ORDER BY id SEPARATOR " | ") as patterns'),
                DB::raw('GROUP_CONCAT(level ORDER BY id SEPARATOR " | ") as levels'),
                DB::raw('GROUP_CONCAT(class_day_1 ORDER BY id SEPARATOR " | ") as class_days_1'),
                DB::raw('GROUP_CONCAT(class_day_2 ORDER BY id SEPARATOR " | ") as class_days_2')
            )
            ->groupBy('student_first_name', 'student_last_name', 'subject', 'month', 'year')
            ->having('count', '>', 1)
            ->get();

            $exactDuplicates = [];
            $patternConflicts = [];

            foreach ($duplicates as $duplicate) {
                $ids = explode(',', $duplicate->ids);
                $patterns = explode(' | ', $duplicate->patterns);
                $levels = explode(' | ', $duplicate->levels);
                $classDays1 = explode(' | ', $duplicate->class_days_1);
                $classDays2 = explode(' | ', $duplicate->class_days_2);

                // Check if all patterns are the same
                $uniquePatterns = array_unique($patterns);
                $uniqueLevels = array_unique($levels);
                $uniqueClassDays1 = array_unique($classDays1);
                $uniqueClassDays2 = array_unique($classDays2);

                $studentName = trim($duplicate->student_first_name . ' ' . $duplicate->student_last_name);

                // If everything is identical, it's an exact duplicate
                if (count($uniquePatterns) === 1 && 
                    count($uniqueLevels) === 1 && 
                    count($uniqueClassDays1) === 1 && 
                    count($uniqueClassDays2) === 1) {
                    
                    $exactDuplicates[] = [
                        'student_name' => $studentName,
                        'first_name' => $duplicate->student_first_name,
                        'last_name' => $duplicate->student_last_name,
                        'subject' => $duplicate->subject,
                        'month' => $duplicate->month,
                        'year' => $duplicate->year,
                        'count' => $duplicate->count,
                        'ids' => $ids,
                        'pattern' => $patterns[0],
                        'level' => $levels[0]
                    ];
                } else {
                    // Pattern or other field conflicts - needs manual review
                    $entries = [];
                    foreach ($ids as $index => $id) {
                        $entries[] = [
                            'id' => $id,
                            'pattern' => $patterns[$index] ?? 'N/A',
                            'level' => $levels[$index] ?? 'N/A',
                            'class_day_1' => $classDays1[$index] ?? 'N/A',
                            'class_day_2' => $classDays2[$index] ?? 'N/A'
                        ];
                    }

                    $patternConflicts[] = [
                        'student_name' => $studentName,
                        'first_name' => $duplicate->student_first_name,
                        'last_name' => $duplicate->student_last_name,
                        'subject' => $duplicate->subject,
                        'month' => $duplicate->month,
                        'year' => $duplicate->year,
                        'count' => $duplicate->count,
                        'entries' => $entries
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'exact_duplicates' => $exactDuplicates,
                    'pattern_conflicts' => $patternConflicts,
                    'total_exact' => count($exactDuplicates),
                    'total_conflicts' => count($patternConflicts)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error finding duplicates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to find duplicates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-delete exact duplicates (keep the first one by ID, delete all others)
     */
    public function deleteExactDuplicates(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'duplicates' => 'required|array',
                'duplicates.*.ids' => 'required|array'
            ]);

            $totalDeleted = 0;
            $processedGroups = 0;

            foreach ($request->input('duplicates') as $duplicate) {
                $ids = $duplicate['ids'];
                
                // Ensure IDs are sorted (lowest first)
                sort($ids);
                
                // Keep the first ID (lowest), delete ALL the rest
                $idToKeep = $ids[0];
                $idsToDelete = array_slice($ids, 1);
                
                if (!empty($idsToDelete)) {
                    $deleted = StudentConfig::whereIn('id', $idsToDelete)->delete();
                    $totalDeleted += $deleted;
                    $processedGroups++;
                    
                    Log::info("Kept ID {$idToKeep}, deleted " . count($idsToDelete) . " duplicates: IDs " . implode(', ', $idsToDelete));
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$totalDeleted} duplicate entries from {$processedGroups} groups. Kept the first entry of each group.",
                'deleted_count' => $totalDeleted,
                'groups_processed' => $processedGroups
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting exact duplicates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete duplicates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually delete a specific config entry
     */
    public function deleteSpecificEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id' => 'required|integer|exists:student_configs,id'
            ]);

            $config = StudentConfig::findOrFail($request->input('id'));
            
            $configInfo = [
                'student' => trim($config->student_first_name . ' ' . $config->student_last_name),
                'subject' => $config->subject,
                'month' => $config->month,
                'year' => $config->year,
                'pattern' => $config->pattern
            ];

            $config->delete();

            DB::commit();

            Log::info("Manually deleted config entry: " . json_encode($configInfo));

            return response()->json([
                'success' => true,
                'message' => 'Entry deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting specific entry: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-delete pattern conflicts by keeping only the highest level
     */
    public function deleteConflictsByHighestLevel(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'conflicts' => 'required|array',
                'conflicts.*.entries' => 'required|array'
            ]);

            $totalDeleted = 0;
            $processedGroups = 0;

            foreach ($request->input('conflicts') as $conflict) {
                $entries = $conflict['entries'];
                $subject = $conflict['subject'] ?? null;
                
                Log::info("Processing conflict - Subject: {$subject}, Entries: " . count($entries));
                
                if (!$subject || count($entries) < 2) {
                    Log::warning("Skipping conflict - Subject: {$subject}, Entry count: " . count($entries));
                    continue;
                }

                // Get all levels and find the highest one
                $levels = array_column($entries, 'level');
                $ids = array_column($entries, 'id');
                
                Log::info("Levels found: " . implode(', ', $levels));

                // Query subject_levels to find which level has the highest ID (highest level)
                $highestLevelRecord = SubjectLevel::where('subject', $subject)
                    ->whereIn('level', $levels)
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$highestLevelRecord) {
                    Log::warning("No matching level found in subject_levels for subject: {$subject}, levels: " . implode(', ', $levels));
                    continue;
                }

                $highestLevel = $highestLevelRecord->level;
                Log::info("Highest level identified: {$highestLevel} (ID: {$highestLevelRecord->id})");

                // Find IDs to keep (those with highest level)
                $idsToKeep = [];
                $idsToDelete = [];

                foreach ($entries as $entry) {
                    if ($entry['level'] === $highestLevel) {
                        $idsToKeep[] = $entry['id'];
                    } else {
                        $idsToDelete[] = $entry['id'];
                    }
                }

                Log::info("IDs to keep (highest level): " . implode(', ', $idsToKeep));
                Log::info("IDs to delete (lower levels): " . implode(', ', $idsToDelete));

                // Keep only ONE of the highest level entries, delete the rest
                if (count($idsToKeep) > 1) {
                    sort($idsToKeep);
                    $keepOne = array_shift($idsToKeep);
                    $idsToDelete = array_merge($idsToDelete, $idsToKeep);
                    Log::info("Multiple highest level entries found. Keeping ID: {$keepOne}, adding extras to delete list");
                }

                if (!empty($idsToDelete)) {
                    $deleted = StudentConfig::whereIn('id', $idsToDelete)->delete();
                    $totalDeleted += $deleted;
                    $processedGroups++;
                    
                    Log::info("Successfully deleted {$deleted} entries for subject {$subject}. Kept highest level ({$highestLevel}) entry.");
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$totalDeleted} entries with lower levels from {$processedGroups} groups. Kept only the highest level entry for each.",
                'deleted_count' => $totalDeleted,
                'groups_processed' => $processedGroups
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting conflicts by highest level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete conflicts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics about duplicates
     */
    public function getStatistics()
    {
        try {
            $totalConfigs = StudentConfig::count();
            
            $duplicateGroups = StudentConfig::select(
                'student_first_name',
                'student_last_name',
                'subject',
                'month',
                'year',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('student_first_name', 'student_last_name', 'subject', 'month', 'year')
            ->having('count', '>', 1)
            ->get();

            $totalDuplicateRecords = 0;
            foreach ($duplicateGroups as $group) {
                $totalDuplicateRecords += ($group->count - 1);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_configs' => $totalConfigs,
                    'total_duplicate_groups' => $duplicateGroups->count(),
                    'total_duplicate_records' => $totalDuplicateRecords,
                    'unique_configs_after_cleanup' => $totalConfigs - $totalDuplicateRecords
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}