<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    // Get all categories
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ], 200);
    }

    // Create category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string'
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    // Get category detail
    public function show(string $id)
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'message' => 'Category retrieved successfully',
            'data' => $category
        ], 200);
    }

    // Update category
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $id,
            'description' => 'sometimes|nullable|string'
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ], 200);
    }

    // Delete category
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        try {
            $category->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Category cannot be deleted because it still has books.'
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Category deleted successfully'
        ], 200);
    }
}
