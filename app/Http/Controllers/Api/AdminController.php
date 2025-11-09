<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Get all users (Admin only)
     */
    public function index()
    {
        try {
            $users = User::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'users' => $users,
                    'total' => $users->count(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => 'An error occurred while fetching users. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get a specific user by ID (Admin only)
     */
    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user ID',
                    'error' => 'The provided user ID is invalid.',
                ], 400);
            }

            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'The requested user does not exist.',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => [
                    'user' => $user,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => 'An error occurred while fetching the user. Please try again later.',
            ], 500);
        }
    }

    /**
     * Create a new user (Admin only)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:user,admin',
            ]);

            // Check if user already exists
            $existingUser = User::where('email', $validated['email'])->first();
            
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already exists',
                    'error' => 'A user with this email address already exists.',
                ], 409);
            }

            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'user' => $user,
                ]
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => 'The provided data is invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => 'An error occurred while creating the user. Please try again later.',
            ], 500);
        }
    }

    /**
     * Update a user by ID (Admin only)
     */
    public function update(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user ID',
                    'error' => 'The provided user ID is invalid.',
                ], 400);
            }

            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'The requested user does not exist.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8|confirmed',
                'role' => 'sometimes|in:user,admin',
            ]);

            DB::beginTransaction();

            $updateData = [];
            
            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
            }
            
            if (isset($validated['email'])) {
                $updateData['email'] = $validated['email'];
            }
            
            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            
            if (isset($validated['role'])) {
                $updateData['role'] = $validated['role'];
            }

            if (empty($updateData)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No data to update',
                    'error' => 'Please provide at least one field to update.',
                ], 400);
            }

            $user->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'user' => $user->fresh(),
                ]
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => 'The provided data is invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => 'An error occurred while updating the user. Please try again later.',
            ], 500);
        }
    }

    /**
     * Delete a user by ID (Admin only)
     */
    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user ID',
                    'error' => 'The provided user ID is invalid.',
                ], 400);
            }

            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'The requested user does not exist.',
                ], 404);
            }

            // Prevent admin from deleting themselves
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete own account',
                    'error' => 'You cannot delete your own account.',
                ], 403);
            }

            DB::beginTransaction();

            // Revoke all tokens
            $user->tokens()->delete();
            
            // Delete user
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => 'An error occurred while deleting the user. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get admin dashboard stats (Admin only)
     */
    public function dashboard()
    {
        try {
            $totalUsers = User::count();
            $totalAdmins = User::where('role', User::ROLE_ADMIN)->count();
            $totalRegularUsers = User::where('role', User::ROLE_USER)->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard stats retrieved successfully',
                'data' => [
                    'stats' => [
                        'total_users' => $totalUsers,
                        'total_admins' => $totalAdmins,
                        'total_regular_users' => $totalRegularUsers,
                    ],
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard stats',
                'error' => 'An error occurred while fetching dashboard statistics. Please try again later.',
            ], 500);
        }
    }
}
