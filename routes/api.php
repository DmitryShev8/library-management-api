<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;

// Public routes
Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);
});