
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="glass-effect rounded-lg p-6">
        <h1 class="text-2xl font-bold gradient-text mb-2">Welcome, {{ $user->name }}!</h1>
        <p class="text-gray-400">{{ $user->role_display }}</p>
    </div>

    <!-- Available Features -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @if($availableFeatures['canViewLessonPlans'])
            <a href="{{ route('student-progress.index') }}" 
               class="glass-effect rounded-lg p-6 hover:bg-white/5 transition-all group">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white group-hover:text-primary transition-colors">
                            Lesson Plans
                        </h3>
                        <p class="text-sm text-gray-400">
                            {{ $availableFeatures['canEditLessonPlans'] ? 'View and edit' : 'View' }} student progress
                        </p>
                    </div>
                </div>
            </a>
        @endif

        @if($availableFeatures['canGrade'])
            <a href="{{ route('grading.index') }}" 
               class="glass-effect rounded-lg p-6 hover:bg-white/5 transition-all group">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white group-hover:text-primary transition-colors">
                            Grading
                        </h3>
                        <p class="text-sm text-gray-400">Grade student assignments</p>
                    </div>
                </div>
            </a>
        @endif

        @if($availableFeatures['canManageConfig'])
            <a href="{{ route('admin.config') }}" 
               class="glass-effect rounded-lg p-6 hover:bg-white/5 transition-all group">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white group-hover:text-primary transition-colors">
                            Configuration
                        </h3>
                        <p class="text-sm text-gray-400">Manage system settings</p>
                    </div>
                </div>
            </a>
        @endif

        @if($availableFeatures['canManageUsers'])
            <a href="{{ route('admin.users.index') }}" 
               class="glass-effect rounded-lg p-6 hover:bg-white/5 transition-all group">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white group-hover:text-primary transition-colors">
                            User Management
                        </h3>
                        <p class="text-sm text-gray-400">Manage system users</p>
                    </div>
                </div>
            </a>
        @endif
    </div>

    <!-- Role Information -->
    <div class="glass-effect rounded-lg p-6">
        <h3 class="text-lg font-semibold text-white mb-3">Your Roles & Permissions</h3>
        <div class="space-y-2">
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($user->getRoles() as $role)
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        @if($role === 'admin') bg-red-500 text-white
                        @elseif($role === 'planner') bg-blue-500 text-white
                        @elseif($role === 'grader') bg-green-500 text-white
                        @else bg-gray-500 text-white
                        @endif">
                        {{ App\Models\User::getRoleLabels()[$role] ?? ucfirst($role) }}
                    </span>
                @endforeach
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 {{ $availableFeatures['canViewLessonPlans'] ? 'text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="{{ $availableFeatures['canViewLessonPlans'] ? 'text-gray-300' : 'text-gray-500' }}">View Lesson Plans</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 {{ $availableFeatures['canEditLessonPlans'] ? 'text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="{{ $availableFeatures['canEditLessonPlans'] ? 'text-gray-300' : 'text-gray-500' }}">Edit Lesson Plans</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 {{ $availableFeatures['canGrade'] ? 'text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="{{ $availableFeatures['canGrade'] ? 'text-gray-300' : 'text-gray-500' }}">Grade Assignments</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 {{ $availableFeatures['canManageUsers'] ? 'text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="{{ $availableFeatures['canManageUsers'] ? 'text-gray-300' : 'text-gray-500' }}">Manage Users</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection