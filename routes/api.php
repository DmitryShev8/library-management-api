<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowingController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Category routes
Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

// Public Book routes
Route::apiResource('books', BookController::class)
    ->only(['index', 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    // Category management: Admin only
    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show'])
        ->middleware('role:admin');

    // Book management
    // Admin, Staff, and Member can create and update books
    Route::post('/books', [BookController::class, 'store'])
        ->middleware('role:admin,staff,member');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->middleware('role:admin,staff,member');

    Route::patch('/books/{book}', [BookController::class, 'update'])
        ->middleware('role:admin,staff,member');

    // Admin only can delete books
    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->middleware('role:admin');

    // Borrowing routes
    Route::prefix('borrowings')
        ->controller(BorrowingController::class)
        ->group(function () {

            // All authenticated roles can view borrowings
            Route::get('/', 'index');
            Route::get('/{id}', 'show');

            // Admin and Member can borrow books
            Route::post('/', 'store')
                ->middleware('role:admin,member');

            // Admin and Member can return borrowed books
            Route::put('/{id}/return', 'returnBook')
                ->middleware('role:admin,member');
        });
});
