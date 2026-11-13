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

    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);

    Route::apiResource('books', BookController::class)
        ->except(['index', 'show']);

    // Borrowing routes
    Route::prefix('borrowings')
        ->controller(BorrowingController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::put('/{id}/return', 'returnBook');
        });
});