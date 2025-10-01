<?php

// app/Http/Controllers/DuplicateConfigController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudentConfig;
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
     * Auto-delete exact duplicates (keep the first one, delete the rest)
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

            foreach ($request->input('duplicates') as $duplicate) {
                $ids = $duplicate['ids'];
                
                // Keep the first ID, delete the rest
                $idsToDelete = array_slice($ids, 1);
                
                if (!empty($idsToDelete)) {
                    $deleted = StudentConfig::whereIn('id', $idsToDelete)->delete();
                    $totalDeleted += $deleted;
                    
                    Log::info("Deleted exact duplicates: IDs " . implode(', ', $idsToDelete));
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$totalDeleted} duplicate entries",
                'deleted_count' => $totalDeleted
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
                $totalDuplicateRecords += ($group->count - 1); // Count extras, not the originals
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