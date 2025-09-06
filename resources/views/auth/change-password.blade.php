<!-- resources/views/auth/change-password.blade.php -->
@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="max-w-md mx-auto">
    <div class="glass-effect rounded-lg p-6">
        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold gradient-text mb-2">Change Password</h1>
            <p class="text-gray-400 text-sm">Update your account password</p>
        </div>

        <!-- Change Password Form -->
        <form method="POST" action="{{ route('change-password') }}" class="space-y-4">
            @csrf
            
            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-300 mb-2">
                    Current Password <span class="text-red-400">*</span>
                </label>
                <input type="password" 
                       id="current_password" 
                       name="current_password" 
                       required
                       class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all @error('current_password') border-red-400 @enderror">
                @error('current_password')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                    New Password <span class="text-red-400">*</span>
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required
                       minlength="8"
                       class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all @error('password') border-red-400 @enderror">
                @error('password')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-gray-500 text-xs mt-1">Minimum 8 characters required</p>
            </div>

            <!-- Confirm New Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">
                    Confirm New Password <span class="text-red-400">*</span>
                </label>
                <input type="password" 
                       id="password_confirmation" 
                       name="password_confirmation" 
                       required
                       class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-primary to-secondary text-white font-semibold py-3 rounded hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                    Update Password
                </button>
            </div>
        </form>

        <!-- Back Link -->
        <div class="mt-6 text-center">
            <a href="{{ route('student-progress.index') }}" class="text-sm text-primary hover:underline">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection