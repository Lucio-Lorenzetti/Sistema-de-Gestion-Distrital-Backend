<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\Gestion\GrupoController;
use App\Http\Controllers\Api\Gestion\RamaController;
use App\Http\Controllers\Api\Gestion\RoleController;
use App\Http\Controllers\Api\Gestion\RoleRequestController;
use App\Http\Controllers\Api\Gestion\UserController;
use App\Http\Controllers\Api\Gestion\DesignacionController;
use App\Http\Controllers\Api\Programas\ProgramController;
use App\Http\Controllers\Api\Programas\NoteController;
use App\Http\Controllers\Api\Comunicacion\NewsController;
use App\Http\Controllers\Api\Comunicacion\DownloadController;
use App\Http\Controllers\Api\Comunicacion\CoursesController;
use App\Http\Controllers\Api\Gestion\NotificationController;
use App\Http\Controllers\ActivityLogController;

// Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
Route::post('/email/verify/reenviar', [AuthController::class, 'resendVerification']);
Route::get('/grupos', [GrupoController::class, 'index']);
Route::get('/ramas', [RamaController::class, 'index']);
Route::get('/roles/solicitables', [RoleController::class, 'solicitables']);
Route::get('news', [NewsController::class, 'index']);
// Antes del wildcard: si no, GET /news/{news} la intercepta y "papelera" 404 como id inválido.
Route::get('news/papelera', [NewsController::class, 'papelera'])->middleware('auth:sanctum');
Route::get('news/{news}', [NewsController::class, 'show']);
Route::get('/courses', [CoursesController::class, 'index']);
Route::get('/courses/papelera', [CoursesController::class, 'papelera'])->middleware('auth:sanctum');
Route::get('/courses/{course}', [CoursesController::class, 'show']);
Route::get('/bibliografia', [DownloadController::class, 'index']);
Route::get('/bibliografia/papelera', [DownloadController::class, 'papelera'])->middleware('auth:sanctum');
Route::get('/bibliografia/{download}/descargar', [DownloadController::class, 'descargar']);
Route::get('/activity-logs', [ActivityLogController::class, 'index']);

// Protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/foto-perfil', [AuthController::class, 'updateFotoPerfil']);
    Route::delete('/me/foto-perfil', [AuthController::class, 'deleteFotoPerfil']);
    Route::put('/me/perfil', [AuthController::class, 'updatePerfil']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    Route::delete('/me/roles/{role}', [AuthController::class, 'renunciarRol']);

    Route::get('/me/notificaciones', [NotificationController::class, 'index']);
    Route::get('/me/notificaciones/no-leidas', [NotificationController::class, 'noLeidas']);
    Route::patch('/me/notificaciones/{id}/leer', [NotificationController::class, 'marcarLeida']);
    Route::patch('/me/notificaciones/leer-todas', [NotificationController::class, 'marcarTodasLeidas']);

    //Usuarios y roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::get('/usuarios', [UserController::class, 'index']);
    // Antes del {user}: si no, GET /usuarios/{user} la intercepta y "papelera" 404 como id inválido.
    Route::get('/usuarios/papelera', [UserController::class, 'papelera']);
    Route::patch('/usuarios/{id}/restore', [UserController::class, 'restore']);
    Route::get('/usuarios/{user}', [UserController::class, 'show']);
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy']);
    Route::post('/usuarios/{user}/roles', [UserController::class, 'assignRole']);
    Route::delete('/usuarios/{user}/roles/{role}', [UserController::class, 'revokeRole']);
    Route::get('/solicitudes-rol', [RoleRequestController::class, 'index']);
    Route::post('/solicitudes-rol', [RoleRequestController::class, 'store']);
    Route::patch('/solicitudes-rol/{roleRequest}/aprobar', [RoleRequestController::class, 'approve']);
    Route::patch('/solicitudes-rol/{roleRequest}/rechazar', [RoleRequestController::class, 'reject']);
    Route::patch('/grupos/{grupo}/jefe-de-grupo', [DesignacionController::class, 'jefeDeGrupo']);
    Route::patch('/distrito/director', [DesignacionController::class, 'director']);

    //Programas
    Route::get('/comentarios-pendientes', [NoteController::class, 'pendientes']);
    // Antes del apiResource: si no, GET /programas/{program} la intercepta y "papelera" 404 como id inválido.
    Route::get('programas/papelera', [ProgramController::class, 'papelera']);
    Route::apiResource('programas', ProgramController::class);
    Route::patch('programas/{id}/restore', [ProgramController::class, 'restore']);
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
    Route::patch('news/{id}/restore', [NewsController::class, 'restore']);

    //Descargas-Documentos
    Route::post('/bibliografia', [DownloadController::class, 'store']);
    Route::delete('/bibliografia/{download}', [DownloadController::class, 'destroy']);
    Route::patch('/bibliografia/{id}/restore', [DownloadController::class, 'restore']);

    //Cursos
    Route::post('/courses', [CoursesController::class, 'store']);
    Route::put('/courses/{course}', [CoursesController::class, 'update']);
    Route::patch('/courses/{course}', [CoursesController::class, 'patch']); // forzar cierre/finalización
    Route::delete('/courses/{course}', [CoursesController::class, 'destroy']);
    Route::patch('/courses/{id}/restore', [CoursesController::class, 'restore']);
});