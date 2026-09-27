<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\Grupo;
use App\Models\Rama;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\RoleRequestService;
use App\Services\UserScopeCache;
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

        $solicitud->user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Cuenta creada. Revisá tu correo para verificar tu cuenta, y esperá a que aprueben tu solicitud de rol.',
            'solicitud' => $solicitud,
        ], 201);
    }

    /**
     * Link firmado que manda sendEmailVerificationNotification() — sin
     * auth:sanctum (todavía no puede haber sesión: recién se registró y ni
     * siquiera está aprobado). La firma + el hash del email son la prueba de
     * identidad, no una sesión.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Link de verificación inválido.');
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tu email ya estaba verificado.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verificado correctamente.']);
    }

    /**
     * Reenviar el link de verificación — pública (todavía no hay sesión),
     * respuesta genérica para no filtrar si el email existe (mismo criterio
     * que forgotPassword()).
     */
    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'Si el email existe y no fue verificado todavía, te reenviamos el link.']);
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

        // Cuentas ya existentes antes de este feature quedaron verificadas por
        // migración (no se les puede pedir retroactivamente que verifiquen un
        // email que ya vienen usando hace tiempo) — este chequeo solo frena a
        // las registradas de acá en más.
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Todavía no verificaste tu email — revisá tu casilla de entrada.'],
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
     * Renunciar a un rol propio — self-service, sin pasar por Developer, y sin
     * designar reemplazo (a diferencia de DesignacionController, que siempre
     * traspasa Jefe de Grupo/Director a otra persona en el mismo paso). Nadie
     * puede renunciar al rol Developer, ni siquiera el propio Developer —
     * mismo criterio que ya rige para asignarse/sacarse roles a uno mismo en
     * UserController.
     */
    public function renunciarRol(Request $request, Role $role)
    {
        $user = $request->user();

        abort_if(strtolower($role->nombre) === 'developer', 403, 'No podés renunciar al rol Developer.');
        abort_if(!$user->roles->contains('id', $role->id), 404, 'No tenés ese rol asignado.');

        $user->roles()->detach($role->id);
        UserScopeCache::sync($user);

        ActivityLogger::log('rol_renunciado', 'Un usuario renunció a un rol propio', "{$user->name} → {$role->nombre}");

        return response()->json($user->load(['roles', 'grupo', 'rama']));
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