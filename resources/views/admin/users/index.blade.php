<!-- resources/views/admin/users/index.blade.php -->
@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-effect rounded-lg p-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold gradient-text">User Management</h1>
            <p class="text-gray-400 text-sm mt-1">Manage system users and their permissions</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="bg-gradient-to-r from-primary to-secondary text-white px-4 py-2 rounded font-medium text-sm hover:shadow-lg hover:-translate-y-0.5 transition-all">
            Add New User
        </a>
    </div>

    <!-- Users Table -->
    <div class="glass-effect rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-800/90">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-300 uppercase tracking-wide">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-300 uppercase tracking-wide">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-300 uppercase tracking-wide">Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-300 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-300 uppercase tracking-wide">Created</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-300 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="hover:bg-primary/10 transition-colors border-b border-white/5">
                            <td class="px-4 py-3 font-medium">
                                {{ $user->name }}
                                @if($user->id === auth()->id())
                                    <span class="ml-2 bg-blue-500 text-white px-2 py-1 rounded text-xs font-semibold">You</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-300">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    @if($user->role === 'admin') bg-red-500 text-white
                                    @elseif($user->role === 'planner') bg-blue-500 text-white
                                    @else bg-gray-500 text-white
                                    @endif">
                                    {{ $user->role_display }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button onclick="toggleUserStatus({{ $user->id }})" 
                                        class="px-2 py-1 rounded text-xs font-semibold transition-all
                                        @if($user->is_active) bg-green-500 text-white hover:bg-green-600
                                        @else bg-red-500 text-white hover:bg-red-600
                                        @endif">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">
                                {{ $user->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs transition-all">
                                        Edit
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <button onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')" 
                                                class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs transition-all">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                No users found. <a href="{{ route('admin.users.create') }}" class="text-primary hover:underline">Create the first user</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md mx-4 border border-white/20">
        <h3 class="text-lg font-semibold text-red-400 mb-2">Confirm Deletion</h3>
        <p class="text-gray-300 mb-4">Are you sure you want to delete <span id="deleteUserName" class="font-semibold"></span>? This action cannot be undone.</p>
        <div class="flex space-x-3 justify-end">
            <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded transition-all">
                Cancel
            </button>
            <form id="deleteForm" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded transition-all">
                    Delete User
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleUserStatus(userId) {
    fetch(`/admin/users/${userId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        showError('Failed to update user status');
    });
}

function deleteUser(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = `/admin/users/${userId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
@endpush

<!-- resources/views/admin/users/create.blade.php -->

