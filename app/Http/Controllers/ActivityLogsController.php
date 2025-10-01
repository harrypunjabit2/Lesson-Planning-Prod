<?php

// app/Http/Controllers/ActivityLogsController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityLogsController extends Controller
{
    public function index()
    {
        return view('activity-logs.index');
    }

    public function getLogs(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'action' => 'nullable|string',
                'student_name' => 'nullable|string',
                'subject' => 'nullable|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100'
            ]);

            $query = UserActivityLog::with('user')
                ->select('user_activity_logs.*')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('student_name')) {
                $query->where('student_name', 'like', '%' . $request->student_name . '%');
            }

            if ($request->filled('subject')) {
                $query->where('subject', $request->subject);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->get('per_page', 25);
            $logs = $query->paginate($perPage);

            // Format the data for response
            $formattedLogs = $logs->getCollection()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->user->name ?? 'Unknown User',
                    'user_email' => $log->user->email ?? 'Unknown Email',
                    'user_roles' => $log->user ? $log->user->getRoles()->toArray() : [],
                    'action' => $log->action,
                    'action_label' => $log->action_label,
                    'entity_type' => $log->entity_type,
                    'student_name' => $log->student_name,
                    'subject' => $log->subject,
                    'month' => $log->month,
                    'date' => $log->date,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'formatted_timestamp' => $log->formatted_timestamp,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedLogs,
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load activity logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFilterOptions()
    {
        try {
            // Get users who have made changes (graders/planners)
            $users = User::whereHas('userRoles', function($query) {
                $query->whereIn('role', ['grader', 'planner', 'admin']);
            })
            ->orWhereHas('activityLogs') // Users who have activity logs
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

            // Get unique actions
            $actions = UserActivityLog::distinct('action')
                ->pluck('action')
                ->sort()
                ->values();

            // Get unique students
            $students = UserActivityLog::whereNotNull('student_name')
                ->distinct('student_name')
                ->pluck('student_name')
                ->sort()
                ->values();

            // Get unique subjects
            $subjects = UserActivityLog::whereNotNull('subject')
                ->distinct('subject')
                ->pluck('subject')
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'actions' => $actions,
                    'students' => $students,
                    'subjects' => $subjects
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load filter options: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getActivitySummary(Request $request)
    {
        try {
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'user_id' => 'nullable|exists:users,id'
            ]);

            $query = UserActivityLog::query();

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Activity by action type
            $actionSummary = (clone $query)
                ->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get();

            // Activity by user
            $userSummary = (clone $query)
                ->join('users', 'user_activity_logs.user_id', '=', 'users.id')
                ->select('users.name', 'users.email', DB::raw('count(*) as count'))
                ->groupBy('users.id', 'users.name', 'users.email')
                ->orderBy('count', 'desc')
                ->get();

            // Daily activity (last 30 days)
            $dailyActivity = (clone $query)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // Most active students (by changes made to them)
            $studentActivity = (clone $query)
                ->whereNotNull('student_name')
                ->select('student_name', DB::raw('count(*) as count'))
                ->groupBy('student_name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'action_summary' => $actionSummary,
                    'user_summary' => $userSummary,
                    'daily_activity' => $dailyActivity,
                    'student_activity' => $studentActivity,
                    'total_activities' => $query->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load activity summary: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportLogs(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'action' => 'nullable|string',
                'student_name' => 'nullable|string',
                'subject' => 'nullable|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'format' => 'required|in:csv,excel'
            ]);

            $query = UserActivityLog::with('user')->orderBy('created_at', 'desc');

            // Apply same filters as getLogs
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('student_name')) {
                $query->where('student_name', 'like', '%' . $request->student_name . '%');
            }

            if ($request->filled('subject')) {
                $query->where('subject', $request->subject);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $logs = $query->get();

            $headers = [
                'User Name',
                'User Email',
                'Action',
                'Student Name',
                'Subject',
                'Month',
                'Date',
                'Description',
                'Old Values',
                'New Values',
                'IP Address',
                'Timestamp'
            ];

            $data = $logs->map(function ($log) {
                return [
                    $log->user->name ?? 'Unknown User',
                    $log->user->email ?? 'Unknown Email',
                    $log->action_label,
                    $log->student_name ?? '',
                    $log->subject ?? '',
                    $log->month ?? '',
                    $log->date ?? '',
                    $log->description ?? '',
                    json_encode($log->old_values),
                    json_encode($log->new_values),
                    $log->ip_address ?? '',
                    $log->created_at->format('Y-m-d H:i:s')
                ];
            });

            $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';

            // Create CSV in memory
            $handle = fopen('php://temp', 'w+');
            fputcsv($handle, $headers);
            
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            
            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to export logs: ' . $e->getMessage()
            ], 500);
        }
    }
}