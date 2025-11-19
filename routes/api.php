<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DrugSearchController;
use App\Http\Controllers\UserMedicationController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public drug search with rate limiting
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/drugs/search', [DrugSearchController::class, 'search']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User medications
    Route::get('/medications', [UserMedicationController::class, 'index']);
    Route::post('/medications', [UserMedicationController::class, 'store']);
    Route::delete('/medications/{rxcui}', [UserMedicationController::class, 'destroy']);
});