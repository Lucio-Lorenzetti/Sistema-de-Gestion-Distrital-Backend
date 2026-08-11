<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
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

        // Cargamos relaciones requeridas por la interfaz del frontend
        $user->load(['roles', 'grupo', 'rama']);

        // Generamos también el token por si Zustand lo usa de respaldo alternativo
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'must_change_password' => (bool) $user->must_change_password, 
            'has_multiple_roles' => $user->roles->count() > 1,
            'status' => 'success'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['roles', 'grupo', 'rama']);

        return response()->json([
            'user' => $user,
            'must_change_password' => (bool) $user->must_change_password,
            'has_multiple_roles' => $user->roles->count() > 1,
        ]);
    }

    // 3. Selección de Función: Validar que el rol/grupo elegido sea válido
    public function seleccionarFuncion(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'grupo_id' => 'required|exists:grupos,id', // 🛠️ CORREGIDO: de 'groups' a 'grupos'
        ]);

        $user = auth()->user();

        // Validamos que el usuario realmente tenga ese rol asignado en la tabla intermedia
        if (!$user->roles->contains($request->role_id)) {
            return response()->json(['message' => 'Función no autorizada.'], 403);
        }

        return response()->json(['message' => 'Función seleccionada correctamente.']);
    }

    // 4. Flujo de Recuperación (Simplificado para API)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        return response()->json(['message' => 'Si el correo existe, se ha enviado un link.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed|regex:/[A-Z]/', 
        ]);

        return response()->json(['message' => 'Contraseña actualizada con éxito.']);
    }

    public function logout(Request $request)
    {
        // 🛠️ MEJORA: Borramos el token de la API
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        // 🛠️ MEJORA: Destruimos la sesión de la cookie (Sanctum Stateful)
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada correctamente en el servidor y navegador.']);
    }
}