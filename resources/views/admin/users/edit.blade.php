<!-- 2. UPDATED EDIT USER VIEW -->
<!-- resources/views/admin/users/edit.blade.php -->
@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-effect rounded-lg p-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold gradient-text">Edit User</h1>
            <p class="text-gray-400 text-sm mt-1">Update user information and permissions</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium text-sm transition-all">
            Back to Users
        </a>
    </div>

    <!-- Edit Form -->
    <div class="glass-effect rounded-lg p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                        Full Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $user->name) }}"
                           required
                           class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all @error('name') border-red-400 @enderror">
                    @error('name')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                        Email Address <span class="text-red-400">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email', $user->email) }}"
                           required
                           class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all @error('email') border-red-400 @enderror">
                    @error('email')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                        New Password <span class="text-gray-500">(leave blank to keep current)</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password"
                           class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all @error('password') border-red-400 @enderror">
                    @error('password')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">
                        Confirm New Password
                    </label>
                    <input type="password" 
                           id="password_confirmation" 
                           name="password_confirmation"
                           class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded text-sm focus:bg-white/15 focus:border-primary transition-all">
                </div>
            </div>

            <!-- Roles Section -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-3">
                    Roles <span class="text-red-400">*</span> <span class="text-gray-500">(Select one or more)</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($availableRoles as $roleKey => $roleLabel)
                        <div class="relative">
                            <input type="checkbox" 
                                   id="role_{{ $roleKey }}" 
                                   name="roles[]" 
                                   value="{{ $roleKey }}"
                                   {{ in_array($roleKey, old('roles', $userRoles)) ? 'checked' : '' }}
                                   class="sr-only role-checkbox">
                            <label for="role_{{ $roleKey }}" 
                                   class="role-card flex items-center p-3 bg-white/5 border border-white/20 rounded-lg cursor-pointer transition-all hover:bg-white/10 hover:border-white/30">
                                <div class="flex-shrink-0 mr-3">
                                    <div class="w-5 h-5 border-2 border-white/30 rounded flex items-center justify-center role-checkbox-visual">
                                        <svg class="w-3 h-3 text-primary hidden role-check" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <div class="font-medium text-white">{{ $roleLabel }}</div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        @switch($roleKey)
                                            @case('admin')
                                                Full system access including user management
                                                @break
                                            @case('planner')
                                                View and edit lesson plan data and configurations
                                                @break
                                            @case('grader')
                                                Access to grading functionality only
                                                @break
                                            @case('viewer')
                                                Read-only access to lesson plan data
                                                @break
                                        @endswitch
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('roles')
                    <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary bg-white/10 border-white/20 rounded focus:ring-primary focus:ring-2">
                    <label for="is_active" class="ml-2 block text-sm text-gray-300">
                        Active User
                    </label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex space-x-3 justify-end pt-4 border-t border-white/10">
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition-all">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-gradient-to-r from-primary to-secondary text-white rounded font-medium hover:shadow-lg transition-all">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.role-checkbox:checked + .role-card {
    background-color: rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.5);
}

.role-checkbox:checked + .role-card .role-checkbox-visual {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.role-checkbox:checked + .role-card .role-check {
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');
    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateRoleCardStyling);
    });
    
    updateRoleCardStyling();
    
    function updateRoleCardStyling() {
        roleCheckboxes.forEach(checkbox => {
            const card = checkbox.nextElementSibling;
            const visual = card.querySelector('.role-checkbox-visual');
            const check = card.querySelector('.role-check');
            
            if (checkbox.checked) {
                card.classList.add('border-primary', 'bg-primary/20');
                visual.classList.add('bg-primary', 'border-primary');
                check.classList.remove('hidden');
            } else {
                card.classList.remove('border-primary', 'bg-primary/20');
                visual.classList.remove('bg-primary', 'border-primary');
                check.classList.add('hidden');
            }
        });
    }
});
</script>
@endsection