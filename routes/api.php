<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Gestion\GrupoController;
use App\Http\Controllers\Api\Programas\ProgramController;
use App\Http\Controllers\Api\Programas\NoteController;
use App\Http\Controllers\Api\Comunicacion\NewsController;
use App\Http\Controllers\Api\Comunicacion\DownloadController;
use App\Http\Controllers\Api\Comunicacion\CoursesController;
use App\Http\Controllers\ActivityLogController;

// Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::get('/grupos', [GrupoController::class, 'index']);
Route::get('news', [NewsController::class, 'index']);
Route::get('news/{news}', [NewsController::class, 'show']);
Route::get('/courses', [CoursesController::class, 'index']);
Route::get('/courses/{course}', [CoursesController::class, 'show']);
Route::get('/bibliografia', [DownloadController::class, 'index']);
Route::get('/bibliografia/{download}/descargar', [DownloadController::class, 'descargar']);
Route::get('/activity-logs', [ActivityLogController::class, 'index']);

// Protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/foto-perfil', [AuthController::class, 'updateFotoPerfil']);
    Route::delete('/me/foto-perfil', [AuthController::class, 'deleteFotoPerfil']);

    //Programas
    Route::get('/comentarios-pendientes', [NoteController::class, 'pendientes']);
    Route::apiResource('programas', ProgramController::class);
    Route::patch('programas/{program}/estado', [ProgramController::class, 'updateStatus']); // <-- faltaba esta
    Route::patch('programas/{program}/solicitar-aprobacion', [ProgramController::class, 'solicitarAprobacion']);
    Route::get('programas/{program}/notas', [NoteController::class, 'index']);
    Route::post('programas/{program}/notas', [NoteController::class, 'store']);
    Route::patch('programas/{program}/notas/{note}/resolucion', [NoteController::class, 'toggleResolucion']);
    Route::get('programas/{program}/pdf', [ProgramController::class, 'pdf']);

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