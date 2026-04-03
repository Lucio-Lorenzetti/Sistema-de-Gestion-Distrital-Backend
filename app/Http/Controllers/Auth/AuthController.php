<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required', // Para identificar desde dónde se conectan
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Creamos el token. Aquí puedes incluir los roles en las habilidades (abilities) si quisieras
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => $user->load(['roles', 'grupo.distrito']), // Cargamos info útil para el front
            'token' => $token
        ]);
    }

    public function me(Request $request)
    {
        // Retorna el usuario autenticado con sus relaciones
        return response()->json($request->user()->load(['roles', 'grupo.distrito', 'rama']));
    }

    public function logout(Request $request)
    {
        // Revoca el token que se está usando actualmente
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}