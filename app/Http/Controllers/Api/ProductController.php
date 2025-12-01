<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with('category');

            // Filter by category if provided
            if ($request->has('category_id')) {
                $categoryId = $request->input('category_id');
                
                if (!is_numeric($categoryId) || $categoryId <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid category ID',
                        'error' => 'The provided category ID is invalid.',
                    ], 400);
                }
                
                $query->where('category_id', $categoryId);
            }

            $products = $query->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => [
                    'products' => $products,
                    'total' => $products->count(),
                ]
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error fetching products: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Database error',
                'error' => config('app.debug') ? $e->getMessage() : 'A database error occurred while fetching products. Please try again later.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while fetching products. Please try again later.',
            ], 500);
        }
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:categories,id',
            ]);

            DB::beginTransaction();

            // Handle file upload
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                try {
                    $file = $request->file('thumbnail');
                    
                    // Validate file size
                    if ($file->getSize() > 2048 * 1024) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'File too large',
                            'error' => 'The thumbnail file size must not exceed 2MB.',
                        ], 413);
                    }

                    $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
                    // Use Storage facade for proper file handling
                    $thumbnailPath = $file->storeAs('public', $filename);
                    // Get the public URL path (without 'public/' prefix for database storage)
                    $thumbnailPath = str_replace('public/', '', $thumbnailPath);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error uploading file: ' . $e->getMessage());
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'File upload failed',
                        'error' => 'An error occurred while uploading the thumbnail. Please try again.',
                    ], 500);
                }
            }

            $product = Product::create([
                'name' => $validated['name'],
                'thumbnail' => $thumbnailPath,
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'],
            ]);

            $product->load('category');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => [
                    'product' => $product,
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
            Log::error('Error creating product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => 'An error occurred while creating the product. Please try again later.',
            ], 500);
        }
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID',
                    'error' => 'The provided product ID is invalid.',
                ], 400);
            }

            $product = Product::with('category')->find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'error' => 'The requested product does not exist.',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => [
                    'product' => $product,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product',
                'error' => 'An error occurred while fetching the product. Please try again later.',
            ], 500);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID',
                    'error' => 'The provided product ID is invalid.',
                ], 400);
            }

            $product = Product::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'error' => 'The requested product does not exist.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'nullable|string',
                'category_id' => 'sometimes|exists:categories,id',
            ]);

            DB::beginTransaction();

            $updateData = [];
            
            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
            }
            
            if (isset($validated['description'])) {
                $updateData['description'] = $validated['description'];
            }

            if (isset($validated['category_id'])) {
                $updateData['category_id'] = $validated['category_id'];
            }

            // Handle file upload
            if ($request->hasFile('thumbnail')) {
                try {
                    $file = $request->file('thumbnail');
                    
                    // Validate file size
                    if ($file->getSize() > 2048 * 1024) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'File too large',
                            'error' => 'The thumbnail file size must not exceed 2MB.',
                        ], 413);
                    }

                    // Delete old thumbnail if exists
                    if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
                        try {
                            Storage::disk('public')->delete($product->thumbnail);
                        } catch (\Exception $e) {
                            Log::warning('Error deleting old thumbnail: ' . $e->getMessage());
                        }
                    }

                    $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
                    // Use Storage facade for proper file handling
                    $thumbnailPath = $file->storeAs('public', $filename);
                    // Get the public URL path (without 'public/' prefix for database storage)
                    $updateData['thumbnail'] = str_replace('public/', '', $thumbnailPath);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error uploading file: ' . $e->getMessage());
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'File upload failed',
                        'error' => 'An error occurred while uploading the thumbnail. Please try again.',
                    ], 500);
                }
            }

            if (empty($updateData)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No data to update',
                    'error' => 'Please provide at least one field to update.',
                ], 400);
            }

            $product->update($updateData);
            $product->load('category');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => [
                    'product' => $product->fresh(),
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
            Log::error('Error updating product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => 'An error occurred while updating the product. Please try again later.',
            ], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID',
                    'error' => 'The provided product ID is invalid.',
                ], 400);
            }

            $product = Product::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'error' => 'The requested product does not exist.',
                ], 404);
            }

            DB::beginTransaction();

            // Delete thumbnail file if exists
            if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
                try {
                    Storage::disk('public')->delete($product->thumbnail);
                } catch (\Exception $e) {
                    Log::warning('Error deleting thumbnail file: ' . $e->getMessage());
                }
            }

            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => 'An error occurred while deleting the product. Please try again later.',
            ], 500);
        }
    }
}
