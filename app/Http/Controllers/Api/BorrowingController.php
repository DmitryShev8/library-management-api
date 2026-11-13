<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    // Menampilkan seluruh data peminjaman
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:borrowed,returned',
        ]);

        $query = Borrowing::with([
            'book',
            'user:id,name,email'
        ]);

        // Member hanya dapat melihat peminjamannya sendiri
        if ($request->user()->role === 'member') {
            $query->where('user_id', $request->user()->id);
        }

        // Filter berdasarkan status jika parameter diberikan
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $borrowings = $query->latest()->paginate(10);

        return response()->json([
            'message' => 'Borrowings retrieved successfully',
            'data' => $borrowings
        ], 200);
    }

    // Menampilkan detail peminjaman
    public function show(Request $request, string $id)
    {
        $query = Borrowing::with([
            'book',
            'user:id,name,email'
        ]);

        // Member hanya dapat melihat peminjamannya sendiri
        if ($request->user()->role === 'member') {
            $query->where('user_id', $request->user()->id);
        }

        $borrowing = $query->findOrFail($id);

        return response()->json([
            'message' => 'Borrowing retrieved successfully',
            'data' => $borrowing
        ], 200);
    }

    // Membuat peminjaman buku
    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'due_at' => 'required|date|after:now',
        ]);

        $borrowing = DB::transaction(function () use ($validated, $request) {
            $book = Book::whereKey($validated['book_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($book->stock < 1) {
                return null;
            }

            $book->decrement('stock');

            return Borrowing::create([
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
                'borrower_name' => $request->user()->name,
                'borrowed_at' => now(),
                'due_at' => $validated['due_at'],
                'status' => 'borrowed',
            ]);
        });

        if (!$borrowing) {
            return response()->json([
                'message' => 'Book is out of stock'
            ], 409);
        }

        return response()->json([
            'message' => 'Book borrowed successfully',
            'data' => $borrowing->load('book', 'user:id,name,email')
        ], 201);
    }

    // Mengembalikan buku
    public function returnBook(Request $request, string $id)
    {
        $borrowing = DB::transaction(function () use ($id, $request) {

            $query = Borrowing::whereKey($id);

            // Member hanya dapat mengembalikan peminjamannya sendiri
            if ($request->user()->role === 'member') {
                $query->where('user_id', $request->user()->id);
            }

            $borrowing = $query
                ->lockForUpdate()
                ->firstOrFail();

            if ($borrowing->status === 'returned') {
                return null;
            }

            $book = Book::whereKey($borrowing->book_id)
                ->lockForUpdate()
                ->firstOrFail();

            $borrowing->update([
                'status' => 'returned',
                'returned_at' => now(),
            ]);

            $book->increment('stock');

            return $borrowing;
        });

        if (!$borrowing) {
            return response()->json([
                'message' => 'This book has already been returned'
            ], 409);
        }

        return response()->json([
            'message' => 'Book returned successfully',
            'data' => $borrowing->refresh()->load([
                'book',
                'user:id,name,email'
            ])
        ], 200);
    }
}
