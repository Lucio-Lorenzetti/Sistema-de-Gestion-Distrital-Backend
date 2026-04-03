<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Maneja la petición entrante.
     * * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  <-- Este es el rol que definiremos en la ruta
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Verificamos que haya un usuario autenticado
        // 2. Usamos el helper hasRole del modelo User
        if (!$request->user() || !$request->user()->hasRole($role)) {
            return response()->json([
                'status' => 'error',
                'message' => "Acceso denegado: Se requiere el rol de {$role}."
            ], 403); // 403 Forbidden
        }

        return $next($request);
    }
}