
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

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-300 mb-2">
                        Role <span class="text-red-400">*</span>
                    </label>
                    <select id="role" 
                            name="role" 
                            required
                            class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded focus:outline-none focus:border-primary @error('role') border-red-400 @enderror">
                        @foreach($roles as $roleKey => $roleLabel)
                            <option value="{{ $roleKey }}" {{ old('role', $user->role) === $roleKey ? 'selected' : '' }}>
                                {{ $roleLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
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
@endsection