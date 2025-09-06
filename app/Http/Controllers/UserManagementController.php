<?php

// app/Http/Controllers/UserManagementController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index()
{
    $users = User::orderBy('created_at', 'desc')->get();
    $roles = User::getRoles(); // Add this line
    return view('admin.users.index', compact('users', 'roles')); // Update this line
}

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = User::getRoles();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(array_keys(User::getRoles()))],
            'is_active' => 'boolean',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
            ]);

            Log::info('User created', [
                'created_by' => auth()->id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            Log::error('Error creating user', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to create user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing a user
     */
    public function edit(User $user)
    {
        $roles = User::getRoles();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(array_keys(User::getRoles()))],
            'is_active' => 'boolean',
        ]);

        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            Log::info('User updated', [
                'updated_by' => auth()->id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating user', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to update user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        try {
            // Prevent admin from deleting themselves
            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'You cannot delete your own account.');
            }

            // Prevent deletion of the last admin
            if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot delete the last administrator account.');
            }

            Log::info('User deleted', [
                'deleted_by' => auth()->id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting user', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        try {
            // Prevent admin from deactivating themselves
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account.'
                ]);
            }

            // Prevent deactivation of the last admin
            if ($user->isAdmin() && $user->is_active && User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate the last active administrator.'
                ]);
            }

            $user->update(['is_active' => !$user->is_active]);

            Log::info('User status toggled', [
                'toggled_by' => auth()->id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'new_status' => $user->is_active ? 'active' : 'inactive'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully.',
                'is_active' => $user->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling user status', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status.'
            ]);
        }
    }
}