<?php

use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GradingController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\DuplicateConfigController;
use App\Http\Controllers\ActivityLogsController; // ADD THIS IMPORT
use Illuminate\Support\Facades\Route;

// Authentication Routes (Public)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {
    
    // Root redirect - SINGLE DEFINITION
    Route::get('/', function () {
        $user = auth()->user();
        
        // Debug the redirect logic
        \Log::info('Root redirect for user: ' . $user->email, [
            'canEdit' => $user->canEdit(),
            'canGrade' => $user->canGrade(),
            'hasRole_viewer' => $user->hasRole('viewer'),
            'roles' => $user->getRoles()->toArray()
        ]);
        
        // Check what the user CAN access and redirect accordingly
        if ($user->canEdit() || $user->hasRole('viewer')) {
            return redirect()->route('student-progress.index');
        } elseif ($user->canGrade() && !$user->hasRole('viewer')) {
            // Pure grader - redirect to grading
            return redirect()->route('grading.index');
        } else {
            // Show a general dashboard
            return redirect()->route('dashboard.index');
        }
    });

    // General dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Password Change Routes
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('change-password');
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Main student progress routes - Fixed middleware
    Route::prefix('student-progress')->name('student-progress.')->group(function () {
        // View operations (Admin, Planner, Viewer can access)
        Route::middleware(['role:admin,planner,viewer'])->group(function () {
            Route::get('/', [StudentProgressController::class, 'index'])->name('index');
            Route::get('/students', [StudentProgressController::class, 'getStudents'])->name('students');
            Route::get('/subjects', [StudentProgressController::class, 'getSubjects'])->name('subjects');
            Route::post('/lesson-plan-data', [StudentProgressController::class, 'getLessonPlanData'])->name('lesson-plan-data');
            Route::post('/lesson-plan-data-by-date-range', [StudentProgressController::class, 'getLessonPlanDataByDateRange'])->name('lesson-plan-data-by-date-range');
        });
        
        // Edit operations (Admin and Planner only)
        Route::middleware(['edit.permission'])->group(function () {
            Route::post('/setup-student-data', [StudentProgressController::class, 'setupStudentData'])->name('setup-student-data');
            Route::post('/sync-config', [StudentProgressController::class, 'syncConfigToDatabase'])->name('sync-config');
            Route::post('/update-last-completed-page', [StudentProgressController::class, 'updateLastCompletedPage'])->name('update-last-completed-page');
            Route::post('/update-level', [StudentProgressController::class, 'updateLevel'])->name('update-level');
            Route::post('/update-repeats', [StudentProgressController::class, 'updateRepeats'])->name('update-repeats');
            Route::delete('/delete-student-data', [StudentProgressController::class, 'deleteStudentData'])->name('delete-student-data');
            Route::get('/api/subject-levels', [StudentProgressController::class, 'getSubjectLevels'])->name('api.subject-levels');
            Route::get('/api/next-level', [StudentProgressController::class, 'getNextLevel'])->name('api.next-level');
            Route::post('/update-test-day', [StudentProgressController::class, 'updateTestDay'])->name('student-progress.update-test-day');
        });
    });

    // Grading Routes (Admin, Planner, Grader)
    Route::middleware(['grade.permission'])->group(function () {
        Route::get('/grading', [GradingController::class, 'index'])->name('grading.index');
        
        Route::prefix('api/grading')->group(function () {
            Route::get('/students', [GradingController::class, 'getStudents']);
            Route::get('/subjects', [GradingController::class, 'getSubjectsForStudent']);
            Route::get('/multi-day-data', [GradingController::class, 'getMultiDayGradingData']);
            Route::post('/save-page-override', [GradingController::class, 'savePageOverride']);
            Route::delete('/remove-page-override', [GradingController::class, 'removePageOverride']);
            Route::post('/save-grade-to-override', [GradingController::class, 'saveGradeToPageOverride']);
            Route::post('/bulk-save-lesson-plan-changes', [GradingController::class, 'bulkSaveLessonPlanChanges']);
            Route::post('/bulk-save-grades', [GradingController::class, 'bulkSaveGrades']);
            Route::get('/export-multi-day-grades', [GradingController::class, 'exportMultiDayGrades']);
        });
    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        
        // Configuration Management (Admin and Planner)
        Route::middleware(['role:admin,planner'])->group(function () {
            Route::post('/generate-current-month', [StudentProgressController::class, 'generateCurrentMonth'])->name('generate-current-month');
            Route::get('/config', [AdminController::class, 'configIndex'])->name('config');
            Route::post('/config', [AdminController::class, 'storeConfig'])->name('config.store');
            Route::put('/config/{config}', [AdminController::class, 'updateConfig'])->name('config.update');
            Route::delete('/config/{config}', [AdminController::class, 'destroyConfig'])->name('config.destroy');
            Route::post('/config/upload-csv', [AdminController::class, 'uploadConfigCsv'])->name('config.upload-csv');

            Route::get('/concepts', [AdminController::class, 'conceptsIndex'])->name('concepts');
            Route::post('/concepts', [AdminController::class, 'storeConcept'])->name('concepts.store');
            Route::put('/concepts/{concept}', [AdminController::class, 'updateConcept'])->name('concepts.update');
            Route::delete('/concepts/{concept}', [AdminController::class, 'destroyConcept'])->name('concepts.destroy');
            Route::post('/concepts/upload-csv', [AdminController::class, 'uploadConceptCsv'])->name('concepts.upload-csv');

            
            
            Route::get('/setup', [AdminController::class, 'setupPage'])->name('setup');
            Route::get('/delete', [AdminController::class, 'deletePage'])->name('delete');
        });

        // Activity Logs Routes (Admin only) - NEW SECTION
        Route::middleware(['role:admin'])->prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::get('/', [ActivityLogsController::class, 'index'])->name('index');
            Route::get('/logs', [ActivityLogsController::class, 'getLogs'])->name('logs');
            Route::get('/filter-options', [ActivityLogsController::class, 'getFilterOptions'])->name('filter-options');
            Route::get('/summary', [ActivityLogsController::class, 'getActivitySummary'])->name('summary');
            Route::get('/export', [ActivityLogsController::class, 'exportLogs'])->name('export');
        });

        Route::middleware(['role:admin'])->prefix('monthly-report')->name('monthly-report.')->group(function () {
    Route::get('/', [MonthlyReportController::class, 'index'])->name('index');
    Route::post('/generate', [MonthlyReportController::class, 'generateReport'])->name('generate');
    Route::get('/export', [MonthlyReportController::class, 'exportReport'])->name('export');
    Route::get('/available-months', [MonthlyReportController::class, 'getAvailableMonths'])->name('available-months');

    
});

Route::middleware(['role:admin'])->prefix('duplicate-config')->name('duplicate-config.')->group(function () {
        Route::get('/', [DuplicateConfigController::class, 'index'])->name('index');
        Route::get('/find', [DuplicateConfigController::class, 'findDuplicates'])->name('find');
        Route::get('/statistics', [DuplicateConfigController::class, 'getStatistics'])->name('statistics');
        Route::post('/delete-exact', [DuplicateConfigController::class, 'deleteExactDuplicates'])->name('delete-exact');
        Route::post('/delete-conflicts-by-level', [DuplicateConfigController::class, 'deleteConflictsByHighestLevel'])->name('delete-conflicts-by-level');
        Route::post('/delete-entry', [DuplicateConfigController::class, 'deleteSpecificEntry'])->name('delete-entry');
    });

        // User Management Routes (Admin only)
        Route::middleware(['role:admin'])->prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('toggle-status');
        });
    });
});

// DEBUGGING ROUTES - Remove after testing
Route::middleware(['auth'])->group(function () {
    Route::get('/debug/user', function () {
        $user = auth()->user();
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoles()->toArray(),
            'canEdit' => $user->canEdit(),
            'canGrade' => $user->canGrade(),
            'hasRole_viewer' => $user->hasRole('viewer'),
            'hasRole_grader' => $user->hasRole('grader'),
            'userRoles_count' => $user->userRoles()->count(),
        ]);
    });
});

/*
IMPORTANT: Add these to your app/Http/Kernel.php $routeMiddleware array:

'role' => \App\Http\Middleware\CheckRole::class,
'edit.permission' => \App\Http\Middleware\CheckEditPermission::class,
'grade.permission' => \App\Http\Middleware\CheckGradePermission::class,
*/