<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Cargamos relaciones para que el Front sepa qué opciones mostrar
        $user->load(['roles', 'grupo', 'rama']);

        // Generamos el token de Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            // FLAG 1: Redirigir a /activar-cuenta
            'must_change_password' => $user->must_change_password, 
            // FLAG 2: Redirigir a /seleccionar-funcion
            'has_multiple_roles' => $user->roles->count() > 1,
            'status' => 'success'
        ]);
    }

    // 3. Selección de Función: Validar que el rol/grupo elegido sea válido
    public function seleccionarFuncion(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'grupo_id' => 'required|exists:groups,id',
        ]);

        $user = auth()->user();

        // Validamos que el usuario realmente tenga ese rol asignado en la tabla intermedia
        if (!$user->roles->contains($request->role_id)) {
            return response()->json(['message' => 'Función no autorizada.'], 403);
        }

        // Aquí podrías actualizar una "sesión activa" o simplemente confirmar
        return response()->json(['message' => 'Función seleccionada correctamente.']);
    }

    // 4. Flujo de Recuperación (Simplificado para API)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Aquí usarías el broker de Laravel para enviar el mail
        // Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Si el correo existe, se ha enviado un link.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed|regex:/[A-Z]/', // Valida mayúscula
        ]);

        // Lógica de reseteo de Laravel...
        return response()->json(['message' => 'Contraseña actualizada con éxito.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }
}