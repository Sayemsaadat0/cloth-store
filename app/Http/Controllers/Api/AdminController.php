<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Get all users (Admin only)
     */
    public function index()
    {
        $users = User::all();
        
        return response()->json([
            'message' => 'Users retrieved successfully',
            'users' => $users,
            'total' => $users->count(),
        ]);
    }

    /**
     * Get a specific user by ID (Admin only)
     */
    public function show($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
        
        return response()->json([
            'message' => 'User retrieved successfully',
            'user' => $user,
        ]);
    }

    /**
     * Create a new user (Admin only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
        ]);

        // Check if user already exists
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            return response()->json([
                'message' => 'User already exists',
                'error' => 'A user with this email address already exists.',
            ], 409);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    /**
     * Update a user by ID (Admin only)
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|in:user,admin',
        ]);

        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }
        
        if ($request->has('role')) {
            $updateData['role'] = $request->role;
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Delete a user by ID (Admin only)
     */
    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
                'error' => 'Self-deletion is not allowed.',
            ], 403);
        }

        // Revoke all tokens
        $user->tokens()->delete();
        
        // Delete user
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get admin dashboard stats (Admin only)
     */
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalAdmins = User::where('role', User::ROLE_ADMIN)->count();
        $totalRegularUsers = User::where('role', User::ROLE_USER)->count();
        
        return response()->json([
            'message' => 'Dashboard stats retrieved successfully',
            'stats' => [
                'total_users' => $totalUsers,
                'total_admins' => $totalAdmins,
                'total_regular_users' => $totalRegularUsers,
            ],
        ]);
    }
}
