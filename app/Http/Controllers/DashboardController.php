<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Determine what dashboard to show based on user roles
        $dashboardData = [
            'user' => $user,
            'availableFeatures' => [
                'canViewLessonPlans' => $user->canEdit() || $user->hasRole('viewer'),
                'canEditLessonPlans' => $user->canEdit(),
                'canGrade' => $user->canGrade(),
                'canManageUsers' => $user->canManageUsers(),
                'canManageConfig' => $user->canManageConfig(),
            ],
        ];
        
        return view('dashboard.index', $dashboardData);
    }
}