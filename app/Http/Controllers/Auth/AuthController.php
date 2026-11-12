<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Grupo;
use App\Models\Rama;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Notifications\EmailCambiadoNotification;
use App\Notifications\PasswordCambiadaNotification;
use App\Services\RoleRequestService;
use App\Services\SafeNotifier;
use App\Services\UserScopeCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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

        // La cuenta y la solicitud de rol ya están guardadas (el DB::transaction
        // de arriba ya se confirmó): que falle el mail de verificación no puede
        // tirar abajo el registro, que ya se concretó.
        try {
            $solicitud->user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el email de verificación al registrarse: ' . $e->getMessage());
        }

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
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::error('No se pudo reenviar el email de verificación: ' . $e->getMessage());
            }
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

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el link de recuperación de contraseña: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Si el correo existe, se envió un link para restablecer la contraseña.']);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'token.required' => 'El link para restablecer la contraseña no es válido. Pedí uno nuevo.',
            'email.required' => 'El link para restablecer la contraseña no es válido. Pedí uno nuevo.',
            'email.email' => 'El link para restablecer la contraseña no es válido. Pedí uno nuevo.',
            'password.required' => 'Ingresá la nueva contraseña.',
            'password.min' => 'La nueva contraseña tiene que tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            // Quien recupera la cuenta no sabe quién más pudo tenerla abierta:
            // se cierran TODAS las sesiones y se avisa por mail.
            $user->tokens()->delete();
            SafeNotifier::enviar($user, new PasswordCambiadaNotification());
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [match ($status) {
                    Password::RESET_THROTTLED => 'Esperá un minuto antes de volver a intentarlo.',
                    default => 'El link para restablecer la contraseña venció o ya fue usado. Pedí uno nuevo desde "Olvidé mi contraseña".',
                }],
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
            'current_password' => 'nullable|string',
        ], [
            'name.required' => 'Escribí tu nombre y apellido.',
            'name.max' => 'El nombre es demasiado largo (máximo 255 caracteres).',
            'totem.max' => 'El tótem es demasiado largo (máximo 100 caracteres).',
            'email.required' => 'Escribí tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'email.unique' => 'Ese correo ya está en uso por otra cuenta.',
        ]);

        $emailAnterior = $user->email;
        $cambiaEmail = $validated['email'] !== $emailAnterior;

        // El correo es la llave de "Olvidé mi contraseña": quien lo cambia se
        // queda con la cuenta. Por eso, para cambiarlo hay que confirmar con la
        // contraseña actual — así una sesión abierta que alguien más aprovecha
        // no alcanza para desviar la cuenta a otro mail.
        if ($cambiaEmail) {
            $actual = $validated['current_password'] ?? '';

            if ($actual === '') {
                throw ValidationException::withMessages([
                    'current_password' => ['Para cambiar tu correo, ingresá tu contraseña actual.'],
                ]);
            }

            if (! Hash::check($actual, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['La contraseña actual no es correcta.'],
                ]);
            }
        }

        $user->update(Arr::except($validated, ['current_password']));

        if ($cambiaEmail) {
            $this->cerrarOtrasSesiones($request);

            // Al correo ANTERIOR: el dueño legítimo se entera aunque no haya sido él.
            SafeNotifier::enviar(
                Notification::route('mail', $emailAnterior),
                new EmailCambiadoNotification($user->email)
            );
        }

        return response()->json($user->load(['roles', 'grupo', 'rama']));
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Ingresá tu contraseña actual.',
            'password.required' => 'Ingresá la nueva contraseña.',
            'password.min' => 'La nueva contraseña tiene que tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $this->cerrarOtrasSesiones($request);
        SafeNotifier::enviar($user, new PasswordCambiadaNotification());

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }

    /**
     * Cierra todas las sesiones del usuario MENOS la que hizo el pedido: al
     * cambiar la contraseña o el correo, quien lo hizo sigue adentro y cualquier
     * otro dispositivo (el de alguien que la tuviera abierta) queda afuera.
     */
    private function cerrarOtrasSesiones(Request $request): void
    {
        $usuario = $request->user();
        $actual = $usuario->currentAccessToken();
        $tokens = $usuario->tokens();

        if ($actual && isset($actual->id)) {
            $tokens->where('id', '!=', $actual->id);
        }

        $tokens->delete();
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