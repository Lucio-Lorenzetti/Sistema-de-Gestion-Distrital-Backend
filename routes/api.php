<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Programas\ProgramController;
use App\Http\Controllers\Api\Programas\NoteController;
use App\Http\Controllers\Api\Comunicacion\NewsController;
use App\Http\Controllers\Api\Comunicacion\DownloadController;
use App\Http\Controllers\Api\Comunicacion\CoursesController;

// Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::get('news', [NewsController::class, 'index']);
Route::get('news/{news}', [NewsController::class, 'show']);
Route::get('/courses', [CoursesController::class, 'index']);
Route::get('/courses/{course}', [CoursesController::class, 'show']);
Route::get('/bibliografia', [DownloadController::class, 'index']);
// Protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //Programas
    Route::apiResource('programas', ProgramController::class);
    Route::get('programas/{program}/notas', [NoteController::class, 'index']);
    Route::post('programas/{program}/notas', [NoteController::class, 'store']);

    //Noticias
    Route::post('news', [NewsController::class, 'store']);
    Route::put('news/{news}', [NewsController::class, 'update']);
    Route::delete('news/{news}', [NewsController::class, 'destroy']);

    //Descargas-Documentos
    Route::post('/bibliografia', [DownloadController::class, 'store']);
    Route::delete('/bibliografia/{download}', [DownloadController::class, 'destroy']);

    //Cursos
    Route::post('/courses', [CoursesController::class, 'store']);
    Route::put('/courses/{course}', [CoursesController::class, 'update']);
    Route::patch('/courses/{course}', [CoursesController::class, 'patch']); // forzar cierre/finalización
    Route::delete('/courses/{course}', [CoursesController::class, 'destroy']);
});

// 4. Rutas exclusivas de Jerarquía (Director/Administración)
Route::middleware(['auth:sanctum', 'role:Director'])->group(function () {
    Route::get('/test-director', function () {
        return response()->json(['message' => 'Hola Director, vos tenés las llaves del Distrito.']);
    });
});