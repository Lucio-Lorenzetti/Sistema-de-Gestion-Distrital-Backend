<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\Programas\ProgramController;
use App\Http\Controllers\Api\Programas\NoteController; // 1. IMPORTACIÓN NECESARIA
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (Requieren Token)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth: Logout, Me
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // 2. CRUD de Programas (Protegido por Sanctum + ProgramPolicy interna)
    Route::apiResource('programas', ProgramController::class);

    // 3. Notas de Programas (Feedback colaborativo)
    // Las sacamos del middleware de director porque, según tu matriz, 
    // los Auxiliares de Rama también pueden dejar notas. [cite: 1153, 1157]
    Route::get('programas/{program}/notas', [NoteController::class, 'index']);
    Route::post('programas/{program}/notas', [NoteController::class, 'store']);

    // Noticias
    Route::get('news', [NewsController::class, 'index']); // Público (logueado)
    Route::post('news', [NewsController::class, 'store']); // Restringido en controlador
    Route::delete('news/{news}', [NewsController::class, 'destroy']);

    // Descargas
    Route::get('downloads', [DownloadController::class, 'index']);
    Route::post('downloads', [DownloadController::class, 'store']);
});

// 4. Rutas exclusivas de Jerarquía (Director/Administración)
Route::middleware(['auth:sanctum', 'role:Director'])->group(function () {
    Route::get('/test-director', function () {
        return response()->json(['message' => 'Hola Director, vos tenés las llaves del Distrito.']);
    });
});