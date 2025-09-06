<?php

// app/Http/Controllers/StudentProgressController.php - FINAL FIX

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\LessonPlanService;
use App\Services\StudentConfigService;
use App\Models\StudentConfig;
use App\Models\LessonPlan;
use App\Models\NewConcept;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentProgressController extends Controller
{
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

    // Rest of your methods remain the same...
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

            $result = $this->lessonPlanService->updateLastCompletedPage(
                $request->input('student_name'),
                $request->input('subject'),
                $request->input('month'),
                $request->input('date'),
                $request->input('last_completed_page')
            );

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

            $result = $this->lessonPlanService->updateLevel(
                $request->input('student_name'),
                $request->input('subject'),
                $request->input('month'),
                $request->input('date'),
                $request->input('new_level')
            );

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
                'repeats' => 'required|integer|min:-1'
            ]);

            $result = $this->lessonPlanService->updateRepeats(
                $request->input('student_name'),
                $request->input('subject'),
                $request->input('month'),
                $request->input('date'),
                $request->input('repeats')
            );

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
             Log::info("g:".$request->input('student_name'));
            $result = $this->lessonPlanService->deleteStudentData(
                $request->input('student_name'),
                $request->input('subject'),
                $request->input('month'),
                $request->input('year')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error deleting student data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete student data: ' . $e->getMessage()
            ], 500);
        }
    }
}