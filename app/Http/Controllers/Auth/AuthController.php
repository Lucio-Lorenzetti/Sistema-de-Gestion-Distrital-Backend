<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\Grupo;
use App\Models\Rama;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\RoleRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Alta self-service: crea la cuenta (inactiva) + la solicitud del primer
     * rol, en un solo paso — para que aprobar sea una sola acción (activar +
     * asignar rol) del lado de quien administra.
     */
    public function register(Request $request, RoleRequestService $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'rama_id' => 'nullable|exists:ramas,id',
            'grupo_id' => 'nullable|exists:grupos,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $rama = isset($validated['rama_id']) ? Rama::find($validated['rama_id']) : null;
        $grupo = isset($validated['grupo_id']) ? Grupo::find($validated['grupo_id']) : null;

        $solicitud = DB::transaction(function () use ($validated, $role, $rama, $grupo, $service) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'activo' => false,
            ]);

            return $service->crearSolicitud($user, $role, $rama, $grupo);
        });

        return response()->json([
            'message' => 'Cuenta creada. Tu solicitud de rol quedó pendiente de aprobación.',
            'solicitud' => $solicitud,
        ], 201);
    }

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

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está pendiente de aprobación.'],
            ]);
        }

        // Cargamos relaciones requeridas por la interfaz del frontend
        $user->load(['roles', 'grupo', 'rama']);

        // Generamos también el token por si Zustand lo usa de respaldo alternativo
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
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
            'has_multiple_roles' => $user->roles->count() > 1,
        ]);
    }

    /**
     * Recuperación de contraseña self-service — nadie más puede conocer ni
     * fijar la contraseña de otro usuario, ni Director ni Developer. Respuesta
     * siempre genérica (no filtra si el mail existe o no).
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Si el correo existe, se envió un link para restablecer la contraseña.']);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Contraseña actualizada con éxito.']);
    }

    /**
     * Perfil propio: nombre y mail (la contraseña tiene su propio endpoint,
     * la foto ya tenía el suyo).
     */
    public function updatePerfil(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'totem' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json($user->load(['roles', 'grupo', 'rama']));
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    /**
     * Subir o reemplazar la foto de perfil del usuario logueado. No es obligatoria:
     * mientras no exista, el frontend sigue mostrando las iniciales.
     */
    public function updateFotoPerfil(Request $request)
    {
        $request->validate([
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();
        $disco = config('filesystems.uploads_disk');

        if ($user->foto_perfil) {
            Storage::disk($disco)->delete($user->foto_perfil);
        }

        $user->foto_perfil = $request->file('foto_perfil')->store('avatars', $disco);
        $user->save();

        return response()->json([
            'message' => 'Foto de perfil actualizada correctamente',
            'foto_perfil_url' => $user->foto_perfil_url,
        ]);
    }

    /**
     * Quitar la foto de perfil del usuario logueado (vuelve a mostrar iniciales).
     */
    public function deleteFotoPerfil(Request $request)
    {
        $user = $request->user();

        if ($user->foto_perfil) {
            Storage::disk(config('filesystems.uploads_disk'))->delete($user->foto_perfil);
            $user->foto_perfil = null;
            $user->save();
        }

        return response()->json(['message' => 'Foto de perfil eliminada correctamente']);
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