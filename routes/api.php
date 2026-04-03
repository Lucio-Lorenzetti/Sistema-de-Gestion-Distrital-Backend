<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Programas\ProgramController; 

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    //Logout, Me
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Rutas de Programas (CRUD)
    Route::apiResource('programas', ProgramController::class);
});

// Ruta de prueba para el middleware de rol
Route::middleware(['auth:sanctum', 'role:director'])->group(function () {
    Route::get('/test-director', function () {
        return response()->json(['message' => 'Hola Director, vos tenés las llaves del Distrito.']);
    });
});