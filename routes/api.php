<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Programas\ProgramController;
use App\Http\Controllers\Api\Programas\NoteController; 
use App\Http\Controllers\Api\Comunicacion\NewsController;       
use App\Http\Controllers\Api\Comunicacion\DownloadController;  

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (Requieren Token)
Route::middleware('auth:sanctum')->group(function () {
    
    //Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('programas', ProgramController::class);

    Route::get('programas/{program}/notas', [NoteController::class, 'index']);
    Route::post('programas/{program}/notas', [NoteController::class, 'store']);

    Route::get('news', [NewsController::class, 'index']); 
    Route::post('news', [NewsController::class, 'store']); 
    Route::delete('news/{news}', [NewsController::class, 'destroy']);

    Route::get('downloads', [DownloadController::class, 'index']);
    Route::post('downloads', [DownloadController::class, 'store']);
});

// 4. Rutas exclusivas de Jerarquía (Director/Administración)
Route::middleware(['auth:sanctum', 'role:Director'])->group(function () {
    Route::get('/test-director', function () {
        return response()->json(['message' => 'Hola Director, vos tenés las llaves del Distrito.']);
    });
});