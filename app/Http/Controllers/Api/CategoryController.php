<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index()
    {
        $categories = Category::all();
        
        return response()->json([
            'message' => 'Categories retrieved successfully',
            'categories' => $categories,
            'total' => $categories->count(),
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'status' => $request->status ?? Category::STATUS_ACTIVE,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show($id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Category retrieved successfully',
            'category' => $category,
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'status' => 'sometimes|in:active,inactive',
        ]);

        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }

        $category->update($updateData);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category->fresh(),
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}

