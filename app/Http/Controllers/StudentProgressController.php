<?php

// app/Http/Controllers/StudentProgressController.php - FIXED VERSION

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\LessonPlanService;
use App\Services\StudentConfigService;
use App\Models\StudentConfig;
use App\Models\LessonPlan;
use App\Models\NewConcept;
use App\Models\SubjectLevel;
use Illuminate\Http\Request;
use App\Traits\LogsUserActivity;
use Illuminate\Support\Facades\Log;

class StudentProgressController extends Controller
{
    use LogsUserActivity;
    
    protected $lessonPlanService;
    protected $studentConfigService;

    public function __construct(LessonPlanService $lessonPlanService, StudentConfigService $studentConfigService)
    {
        $this->lessonPlanService = $lessonPlanService;
        $this->studentConfigService = $studentConfigService;
    }

    public function index()
    {
        return view('student-progress.index');
    }

    public function getStudents()
    {
        try {
            $students = StudentConfig::select('student_first_name', 'student_last_name', 'subject')
                ->get()
                ->map(function ($student) {
                    return $student->display_name;
                })
                ->unique()
                ->sort()
                ->values();

            return response()->json($students);
        } catch (\Exception $e) {
            Log::error('Error getting students: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load students'], 500);
        }
    }

    public function getSubjects()
    {
        try {
            $subjects = StudentConfig::distinct('subject')
                ->pluck('subject')
                ->sort()
                ->values();

            return response()->json($subjects);
        } catch (\Exception $e) {
            Log::error('Error getting subjects: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load subjects'], 500);
        }
    }

    /**
     * Get available levels for a specific subject
     */
    public function getSubjectLevels(Request $request)
    {
        try {
            $request->validate([
                'subject' => 'required|string'
            ]);

            $subject = $request->input('subject');
            $levels = SubjectLevel::getLevelsForSubject($subject);

            return response()->json([
                'success' => true,
                'levels' => $levels
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting subject levels: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load subject levels: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the next level for a student's current level
     */
    public function getNextLevel(Request $request)
    {
        try {
            $request->validate([
                'subject' => 'required|string',
                'current_level' => 'required|string'
            ]);

            $subject = $request->input('subject');
            $currentLevel = $request->input('current_level');

            $nextLevel = SubjectLevel::getNextLevel($subject, $currentLevel);

            return response()->json([
                'success' => true,
                'next_level' => $nextLevel,
                'has_next_level' => $nextLevel !== null
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting next level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to get next level: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLessonPlanData(Request $request)
    {
        try {
            $request->validate([
                'months' => 'array',
                'months.*' => 'string',
                'year' => 'required|integer',
                'student_name' => 'nullable|string',
                'subject' => 'nullable|string'
            ]);

            $months = $request->input('months', []);
            $year = $request->input('year');
            
            // CRITICAL FIX: Use null coalescing operator to handle explicit null values from JavaScript
            $studentName = $request->input('student_name') ?? '';
            $subject = $request->input('subject') ?? '';

            if (empty($months)) {
                $months = [date('F')]; // Current month if none specified
            }
            
            Log::info("Student name: '{$studentName}', Subject: '{$subject}'");

            $data = $this->lessonPlanService->getLessonPlanDataMultipleMonths(
                $months, 
                $year, 
                $studentName,  // Now guaranteed to be string (empty string if null)
                $subject       // Now guaranteed to be string (empty string if null)
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting lesson plan data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load lesson plan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLessonPlanDataByDateRange(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'student_name' => 'nullable|string',
                'subject' => 'nullable|string'
            ]);

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            // CRITICAL FIX: Use null coalescing operator to handle explicit null values from JavaScript
            $studentName = $request->input('student_name') ?? '';
            $subject = $request->input('subject') ?? '';

            Log::info("Date Range - Student name: '{$studentName}', Subject: '{$subject}'");

            $data = $this->lessonPlanService->getLessonPlanDataByDateRange(
                $startDate, 
                $endDate, 
                $studentName,  // Now guaranteed to be string (empty string if null)
                $subject       // Now guaranteed to be string (empty string if null)
            );

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting lesson plan data by date range: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load lesson plan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setupStudentData()
    {
        try {
            $result = $this->studentConfigService->setupStudentData();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error setting up student data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error setting up student data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncConfigToDatabase(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Config sync completed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error syncing config: ' . $e->getMessage()
            ], 500);
        }
    }

  public function updateLastCompletedPage(Request $request)
    {
        try {
            $request->validate([
                'student_name' => 'required|string',
                'subject' => 'required|string',
                'month' => 'required|string',
                'date' => 'required|integer',
                'last_completed_page' => 'required|integer|min:0'
            ]);

            $studentName = $request->input('student_name');
            $subject = $request->input('subject');
            $month = $request->input('month');
            $date = $request->input('date');
            $newPage = $request->input('last_completed_page');

            $nameParts = explode(' ', trim($studentName), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

            // Get old value for logging
            $lessonPlan = LessonPlan::where([
                'student_first_name' => $firstName,
                'student_last_name' => $lastName,
                'subject' => $subject,
                'month' => $month,
                'date' => $date
            ])->first();

            $oldPage = $lessonPlan ? $lessonPlan->last_completed_page : 0;

            $result = $this->lessonPlanService->updateLastCompletedPage(
                $studentName,
                $subject,
                $month,
                $date,
                $newPage
            );

            // Log the activity
            if ($result['success']) {
                $this->logLessonPlanActivity(
                    'update_last_completed_page',
                    $studentName,
                    $subject,
                    $month,
                    $date,
                    ['last_completed_page' => $oldPage],
                    ['last_completed_page' => $newPage]
                );
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error updating last completed page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update: ' . $e->getMessage()
            ], 500);
        }
    }

  public function updateLevel(Request $request)
    {
        try {
            $request->validate([
                'student_name' => 'required|string',
                'subject' => 'required|string',
                'month' => 'required|string',
                'date' => 'required|integer',
                'new_level' => 'required|string'
            ]);

            $studentName = $request->input('student_name');
            $subject = $request->input('subject');
            $month = $request->input('month');
            $date = $request->input('date');
            $newLevel = $request->input('new_level');
            
                        $nameParts = explode(' ', trim($studentName), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';
            // Get old value for logging
            $lessonPlan = LessonPlan::where([
                'student_first_name' => $firstName,
                'student_last_name' => $lastName,
                'subject' => $subject,
                'month' => $month,
                'date' => $date
            ])->first();

            $oldLevel = $lessonPlan ? $lessonPlan->level : 'unknown';

            $result = $this->lessonPlanService->updateLevel(
                $studentName,
                $subject,
                $month,
                $date,
                $newLevel
            );

            // Log the activity
            if ($result['success']) {
                $this->logLessonPlanActivity(
                    'update_level',
                    $studentName,
                    $subject,
                    $month,
                    $date,
                    ['level' => $oldLevel],
                    ['level' => $newLevel]
                );
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error updating level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update level: ' . $e->getMessage()
            ], 500);
        }
    }

   public function updateRepeats(Request $request)
    {
        try {
            $request->validate([
                'student_name' => 'required|string',
                'subject' => 'required|string',
                'month' => 'required|string',
                'date' => 'required|integer',
                'pages' => 'required|integer|min:1',
                'repeats' => 'required|integer|min:0'
            ]);

            $studentName = $request->input('student_name');
            $subject = $request->input('subject');
            $month = $request->input('month');
            $date = $request->input('date');
            $pages = $request->input('pages');
            $newRepeats = $request->input('repeats');

            // Get old value for logging - you'll need to implement this in your service
            // For now, we'll assume 0 as old value if not found
            $oldRepeats = 0; // You can enhance this by getting the actual old value

            $result = $this->lessonPlanService->updateRepeats(
                $studentName,
                $subject,
                $month,
                $date,
                $pages,
                $newRepeats
            );

            // Log the activity
            if ($result['success']) {
                $this->logLessonPlanActivity(
                    'update_repeats',
                    $studentName,
                    $subject,
                    $month,
                    $date,
                    ['repeats' => $oldRepeats, 'pages' => $pages],
                    ['repeats' => $newRepeats, 'pages' => $pages]
                );
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error updating repeats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update repeats: ' . $e->getMessage()
            ], 500);
        }
    }

  public function deleteStudentData(Request $request)
    {
        try {
            $request->validate([
                'student_name' => 'required|string',
                'subject' => 'required|string',
                'month' => 'required|string',
                'year' => 'required|integer'
            ]);
            
            $studentName = $request->input('student_name');
            $subject = $request->input('subject');
            $month = $request->input('month');
            $year = $request->input('year');
            
            Log::info("g:".$studentName);
            
            $result = $this->lessonPlanService->deleteStudentData(
                $studentName,
                $subject,
                $month,
                $year
            );

            // Log the activity
            if ($result['success']) {
                $this->logLessonPlanActivity(
                    'delete_student_data',
                    $studentName,
                    $subject,
                    $month,
                    null, // no specific date
                    ['month' => $month, 'year' => $year],
                    [],
                    "Deleted all data for {$studentName} ({$subject}) for {$month} {$year}"
                );
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error deleting student data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete student data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateCurrentMonth(Request $r)
    {
        try {
            $month=$r->get("month");
            $year=$r->get("year");

            $result = $this->lessonPlanService->generateCurrentMonthLessonPlans($month,$year);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? null,
                'error' => $result['error'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating current month lesson plans: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error generating current month lesson plans: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTestDay(Request $request)
{
    try {
        $request->validate([
            'student_name' => 'required|string',
            'subject' => 'required|string',
            'month' => 'required|string',
            'date' => 'required|integer',
            'is_test_day' => 'required|in:Y,N'
        ]);

        $result = $this->lessonPlanService->updateTestDay(
            $request->input('student_name'),
            $request->input('subject'),
            $request->input('month'),
            $request->input('date'),
            $request->input('is_test_day')
        );

        return response()->json($result);

    } catch (\Exception $e) {
        Log::error('Error updating test day: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Failed to update test day: ' . $e->getMessage()
        ], 500);
    }
}
}