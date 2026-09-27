<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Role;
use App\Models\RoleRequest;
use App\Models\User;
use App\Policies\CoursePolicy;
use App\Policies\ProgramPolicy;
use App\Policies\RolePolicy;
use App\Policies\RoleRequestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Program::class, ProgramPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(RoleRequest::class, RoleRequestPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);

        // Developer: bypass total de cualquier Gate::authorize()/$user->can(),
        // sin importar la lógica de la policy (cubre incluso las que no usan
        // hasRole, como ProgramPolicy::update()). El bypass de los checks
        // crudos (hasAnyRole inline en NewsController/DownloadController,
        // CheckRole) vive en User::hasRole()/hasAnyRole().
        Gate::before(fn (User $user, string $ability) => $user->isDeveloper() ? true : null);

        // El link de "restablecer contraseña" apunta al FRONTEND (SPA), no a
        // una ruta del backend.
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');
            return "{$frontendUrl}/restablecer-contrasena?token={$token}&email=" . urlencode($user->email);
        });

        // Mismo criterio que ResetPassword: el link apunta al frontend, no a una
        // ruta del backend. Se genera primero la URL firmada "real" (misma ruta
        // nombrada que valida la firma en AuthController::verifyEmail) y se le
        // cambia el host/path por el del frontend, conservando id/hash/expires/
        // signature intactos — la firma sigue siendo válida porque se calculó
        // sobre esos mismos parámetros.
        VerifyEmail::createUrlUsing(function (User $user) {
            $temporaryUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
            );

            $query = parse_url($temporaryUrl, PHP_URL_QUERY);
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/verificar-email/{$user->getKey()}/" . sha1($user->getEmailForVerification()) . "?{$query}";
        });
    }
}