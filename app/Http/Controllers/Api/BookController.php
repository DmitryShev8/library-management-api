<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class BookController extends Controller
{
    // Get all books
    public function index()
    {
        $books = Book::with('category:id,name')->get();

        return response()->json([
            'message' => 'Books retrieved successfully',
            'data' => $books
        ], 200);
    }

    // Create book
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|max:20|unique:books,isbn',
            'stock' => 'required|integer|min:0',
        ]);

        $book = Book::create($validated);

        return response()->json([
            'message' => 'Book created successfully',
            'data' => $book->load('category:id,name')
        ], 201);
    }

    // Get book detail
    public function show(string $id)
    {
        $book = Book::with('category:id,name')->findOrFail($id);

        return response()->json([
            'message' => 'Book retrieved successfully',
            'data' => $book
        ], 200);
    }

    // Update book
    public function update(Request $request, string $id)
    {
        $book = Book::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'isbn' => 'sometimes|required|string|max:20|unique:books,isbn,' . $id,
            'stock' => 'sometimes|required|integer|min:0',
        ]);

        $book->update($validated);

        return response()->json([
            'message' => 'Book updated successfully',
            'data' => $book->refresh()->load('category:id,name')
        ], 200);
    }

    // Delete book
    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);

        try {
            $book->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Cannot delete this book because it has borrowing history'
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Book deleted successfully'
        ], 200);
    }
}
