<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowingController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

Route::apiResource('books', BookController::class)
    ->only(['index', 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin only: Category Management
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show']);
    });

    // Admin and Staff: Create and Update Books
    Route::middleware('role:admin,staff')->group(function () {
        Route::apiResource('books', BookController::class)
            ->only(['store', 'update']);
    });

    // Admin only: Delete Books
    Route::middleware('role:admin')->group(function () {
        Route::delete('/books/{book}', [BookController::class, 'destroy']);
    });

    // Borrowing history: all authenticated users
    Route::prefix('borrowings')
        ->controller(BorrowingController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
        });

    // Member only: Borrow and Return Books
    Route::middleware('role:member')->prefix('borrowings')
        ->controller(BorrowingController::class)
        ->group(function () {
            Route::post('/', 'store');
            Route::put('/{id}/return', 'returnBook');
        });
});