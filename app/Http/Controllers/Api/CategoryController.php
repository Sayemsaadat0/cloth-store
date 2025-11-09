<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index()
    {
        try {
            $categories = Category::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => [
                    'categories' => $categories,
                    'total' => $categories->count(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => 'An error occurred while fetching categories. Please try again later.',
            ], 500);
        }
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories',
                'status' => 'sometimes|in:active,inactive',
            ]);

            DB::beginTransaction();

            $category = Category::create([
                'name' => $validated['name'],
                'status' => $validated['status'] ?? Category::STATUS_ACTIVE,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => [
                    'category' => $category,
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
            Log::error('Error creating category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => 'An error occurred while creating the category. Please try again later.',
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category ID',
                    'error' => 'The provided category ID is invalid.',
                ], 400);
            }

            $category = Category::find($id);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                    'error' => 'The requested category does not exist.',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully',
                'data' => [
                    'category' => $category,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category',
                'error' => 'An error occurred while fetching the category. Please try again later.',
            ], 500);
        }
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category ID',
                    'error' => 'The provided category ID is invalid.',
                ], 400);
            }

            $category = Category::find($id);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                    'error' => 'The requested category does not exist.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
                'status' => 'sometimes|in:active,inactive',
            ]);

            DB::beginTransaction();

            $updateData = [];
            
            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
            }
            
            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            if (empty($updateData)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No data to update',
                    'error' => 'Please provide at least one field to update.',
                ], 400);
            }

            $category->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => [
                    'category' => $category->fresh(),
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
            Log::error('Error updating category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => 'An error occurred while updating the category. Please try again later.',
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category ID',
                    'error' => 'The provided category ID is invalid.',
                ], 400);
            }

            $category = Category::find($id);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                    'error' => 'The requested category does not exist.',
                ], 404);
            }

            DB::beginTransaction();

            // Check if category has products
            if ($category->products()->count() > 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category',
                    'error' => 'This category has associated products. Please remove or reassign products before deleting.',
                ], 409);
            }

            $category->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => 'An error occurred while deleting the category. Please try again later.',
            ], 500);
        }
    }
}
