<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('userRoles')->orderBy('created_at', 'desc')->get();
        $availableRoles = User::getRoleLabels();
        
        return view('admin.users.index', compact('users', 'availableRoles'));
    }

    public function create()
    {
        $availableRoles = User::getRoleLabels();
        return view('admin.users.create', compact('availableRoles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => ['required', Rule::in(User::getAllRoles())],
            'is_active' => 'boolean',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $user->syncRoles($request->roles);

            Log::info('User created', [
                'created_by' => auth()->id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $request->roles
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

    public function edit(User $user)
    {
        $user->load('userRoles');
        $availableRoles = User::getRoleLabels();
        $userRoles = $user->getRoles()->toArray();
        
        return view('admin.users.edit', compact('user', 'availableRoles', 'userRoles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => ['required', Rule::in(User::getAllRoles())],
            'is_active' => 'boolean',
        ]);

        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'is_active' => $request->boolean('is_active', true),
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);
            $user->syncRoles($request->roles);

            Log::info('User updated', [
                'updated_by' => auth()->id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $request->roles
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

    public function destroy(User $user)
    {
        try {
            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'You cannot delete your own account.');
            }

            if ($user->hasRole(User::ROLE_ADMIN) && 
                User::whereHas('userRoles', function($q) {
                    $q->where('role', User::ROLE_ADMIN);
                })->count() <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot delete the last administrator account.');
            }

            Log::info('User deleted', [
                'deleted_by' => auth()->id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->getRoles()->toArray()
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

    public function toggleStatus(User $user)
    {
        try {
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account.'
                ]);
            }

            if ($user->hasRole(User::ROLE_ADMIN) && $user->is_active && 
                User::whereHas('userRoles', function($q) {
                    $q->where('role', User::ROLE_ADMIN);
                })->where('is_active', true)->count() <= 1) {
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