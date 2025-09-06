<?php

// routes/web.php - Updated with Authentication
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Public)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {
    
    // Root redirect
    Route::get('/', function () {
        return redirect()->route('student-progress.index');
    });

    // Password Change Routes
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('change-password');
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Main student progress routes (All authenticated users can view)
    Route::prefix('student-progress')->name('student-progress.')->group(function () {
        Route::get('/', [StudentProgressController::class, 'index'])->name('index');
        Route::get('/students', [StudentProgressController::class, 'getStudents'])->name('students');
        Route::get('/subjects', [StudentProgressController::class, 'getSubjects'])->name('subjects');
        Route::post('/lesson-plan-data', [StudentProgressController::class, 'getLessonPlanData'])->name('lesson-plan-data');
        Route::post('/lesson-plan-data-by-date-range', [StudentProgressController::class, 'getLessonPlanDataByDateRange'])->name('lesson-plan-data-by-date-range');
        
        // Edit operations (Admin and Planner only)
        Route::middleware(['edit.permission'])->group(function () {
            Route::post('/setup-student-data', [StudentProgressController::class, 'setupStudentData'])->name('setup-student-data');
            Route::post('/sync-config', [StudentProgressController::class, 'syncConfigToDatabase'])->name('sync-config');
            Route::post('/update-last-completed-page', [StudentProgressController::class, 'updateLastCompletedPage'])->name('update-last-completed-page');
            Route::post('/update-level', [StudentProgressController::class, 'updateLevel'])->name('update-level');
            Route::post('/update-repeats', [StudentProgressController::class, 'updateRepeats'])->name('update-repeats');
            Route::delete('/delete-student-data', [StudentProgressController::class, 'deleteStudentData'])->name('delete-student-data');
        });
    });

    // Admin routes (Admin and Planner can access most features)
    Route::prefix('admin')->name('admin.')->group(function () {
        
        // Student Configuration Management (Admin and Planner)
        Route::middleware(['role:admin,planner'])->group(function () {
            Route::get('/config', [AdminController::class, 'configIndex'])->name('config');
            Route::post('/config', [AdminController::class, 'storeConfig'])->name('config.store');
            Route::put('/config/{config}', [AdminController::class, 'updateConfig'])->name('config.update');
            Route::delete('/config/{config}', [AdminController::class, 'destroyConfig'])->name('config.destroy');
            Route::post('/config/upload-csv', [AdminController::class, 'uploadConfigCsv'])->name('config.upload-csv');

            // New Concepts Management (Admin and Planner)
            Route::get('/concepts', [AdminController::class, 'conceptsIndex'])->name('concepts');
            Route::post('/concepts', [AdminController::class, 'storeConcept'])->name('concepts.store');
            Route::put('/concepts/{concept}', [AdminController::class, 'updateConcept'])->name('concepts.update');
            Route::delete('/concepts/{concept}', [AdminController::class, 'destroyConcept'])->name('concepts.destroy');
            Route::post('/concepts/upload-csv', [AdminController::class, 'uploadConceptCsv'])->name('concepts.upload-csv');
            
            // Data Management Pages (Admin and Planner)
            Route::get('/setup', [AdminController::class, 'setupPage'])->name('setup');
            Route::get('/delete', [AdminController::class, 'deletePage'])->name('delete');
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

/*
Additional steps required for implementation:

1. Add to app/Http/Kernel.php in the $routeMiddleware array:
'role' => \App\Http\Middleware\CheckRole::class,
'edit.permission' => \App\Http\Middleware\CheckEditPermission::class,

2. Update config/auth.php to use 'users' table:
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],

3. Run migrations and seeders:
php artisan migrate
php artisan db:seed --class=AdminUserSeeder

4. Install Laravel Breeze or create basic auth views (login form provided above)

Key Security Features:
- Authentication required for all routes except login
- Role-based access control
- Edit permissions separated from view permissions
- Admin-only user management
- Active user validation
- CSRF protection on all forms
- Proper session management
*/