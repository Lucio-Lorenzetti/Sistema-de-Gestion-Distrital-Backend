<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailCambiadoNotification;
use App\Notifications\PasswordCambiadaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * Cambiar la contraseña o el correo de una cuenta: qué se exige, qué sesiones
 * se cierran y a quién se avisa. Pega contra los endpoints reales.
 *
 * Las migraciones completas usan sintaxis de Postgres y no corren en sqlite,
 * así que acá se crean a mano solo las tablas mínimas que estos endpoints tocan.
 */
class CambioDeCredencialesTest extends TestCase
{
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ramas', function (Blueprint $t) { $t->id(); $t->string('nombre'); $t->timestamps(); });
        Schema::create('grupos', function (Blueprint $t) { $t->id(); $t->string('nombre'); $t->timestamps(); });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('nombre'); $t->timestamps(); });
        Schema::create('user_roles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('rama_id')->nullable();
            $t->unsignedBigInteger('grupo_id')->nullable();
            $t->unsignedBigInteger('asignado_por_id')->nullable();
            $t->timestamp('asignado_at')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('totem')->nullable();
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->boolean('activo')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->unsignedBigInteger('grupo_id')->nullable();
            $t->unsignedBigInteger('rama_id')->nullable();
            $t->string('foto_perfil')->nullable();
            $t->rememberToken();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->text('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        $this->usuario = User::forceCreate([
            'name' => 'Lucio',
            'email' => 'viejo@mail.com',
            'password' => Hash::make('ClaveVieja1'),
            'email_verified_at' => now(),
        ]);

        Notification::fake();
    }

    /** Dos sesiones abiertas: la que hace el pedido y "otro dispositivo". */
    private function conDosSesiones(): string
    {
        $otra = $this->usuario->createToken('otro-dispositivo');
        $actual = $this->usuario->createToken('este-dispositivo');

        $this->assertSame(2, DB::table('personal_access_tokens')->count());

        return $actual->plainTextToken;
    }

    private function sesionesAbiertas(): array
    {
        return DB::table('personal_access_tokens')->pluck('name')->all();
    }

    // ---------------------------------------------------------------- contraseña

    public function test_con_la_contrasena_actual_incorrecta_no_cambia_nada(): void
    {
        $token = $this->conDosSesiones();

        $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'incorrecta',
            'password' => 'ClaveNueva1',
            'password_confirmation' => 'ClaveNueva1',
        ])->assertStatus(422)->assertJsonPath('errors.current_password.0', 'La contraseña actual no es correcta.');

        $this->assertTrue(Hash::check('ClaveVieja1', $this->usuario->fresh()->password));
        $this->assertCount(2, $this->sesionesAbiertas());
        Notification::assertNothingSent();
    }

    public function test_si_la_confirmacion_no_coincide_el_error_esta_en_espanol(): void
    {
        $token = $this->conDosSesiones();

        $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'ClaveVieja1',
            'password' => 'ClaveNueva1',
            'password_confirmation' => 'otra-cosa',
        ])->assertStatus(422)->assertJsonPath('errors.password.0', 'La confirmación no coincide con la nueva contraseña.');
    }

    public function test_cambiar_la_contrasena_cierra_las_otras_sesiones_y_avisa_por_mail(): void
    {
        $token = $this->conDosSesiones();

        $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'ClaveVieja1',
            'password' => 'ClaveNueva1',
            'password_confirmation' => 'ClaveNueva1',
        ])->assertOk();

        $this->assertTrue(Hash::check('ClaveNueva1', $this->usuario->fresh()->password));
        // Queda la sesión que hizo el cambio; la del "otro dispositivo" se cerró.
        $this->assertSame(['este-dispositivo'], $this->sesionesAbiertas());
        Notification::assertSentTo($this->usuario, PasswordCambiadaNotification::class);
    }

    // -------------------------------------------------------------------- correo

    public function test_cambiar_el_correo_sin_contrasena_actual_se_rechaza(): void
    {
        $token = $this->conDosSesiones();

        $this->withToken($token)->putJson('/api/me/perfil', [
            'name' => 'Lucio',
            'email' => 'nuevo@mail.com',
        ])->assertStatus(422)->assertJsonPath('errors.current_password.0', 'Para cambiar tu correo, ingresá tu contraseña actual.');

        $this->assertSame('viejo@mail.com', $this->usuario->fresh()->email);
        $this->assertCount(2, $this->sesionesAbiertas());
        Notification::assertNothingSent();
    }

    public function test_cambiar_el_correo_con_contrasena_incorrecta_se_rechaza(): void
    {
        $token = $this->conDosSesiones();

        $this->withToken($token)->putJson('/api/me/perfil', [
            'name' => 'Lucio',
            'email' => 'nuevo@mail.com',
            'current_password' => 'incorrecta',
        ])->assertStatus(422)->assertJsonPath('errors.current_password.0', 'La contraseña actual no es correcta.');

        $this->assertSame('viejo@mail.com', $this->usuario->fresh()->email);
    }

    public function test_cambiar_el_correo_con_la_contrasena_correcta_avisa_a_la_direccion_anterior(): void
    {
        $token = $this->conDosSesiones();

        $this->withToken($token)->putJson('/api/me/perfil', [
            'name' => 'Lucio',
            'email' => 'nuevo@mail.com',
            'current_password' => 'ClaveVieja1',
        ])->assertOk()->assertJsonPath('email', 'nuevo@mail.com');

        $this->assertSame('nuevo@mail.com', $this->usuario->fresh()->email);
        $this->assertSame(['este-dispositivo'], $this->sesionesAbiertas());
        // El aviso va al correo VIEJO, no al nuevo.
        Notification::assertSentOnDemand(
            EmailCambiadoNotification::class,
            fn ($notificacion, $canales, $notifiable) => $notifiable->routes['mail'] === 'viejo@mail.com'
        );
    }

    public function test_editar_otros_datos_sin_tocar_el_correo_no_pide_contrasena_ni_cierra_sesiones(): void
    {
        $token = $this->conDosSesiones();

        $this->withToken($token)->putJson('/api/me/perfil', [
            'name' => 'Lucio Lorenzetti',
            'totem' => 'Lobo',
            'email' => 'viejo@mail.com',
        ])->assertOk();

        $this->assertSame('Lucio Lorenzetti', $this->usuario->fresh()->name);
        $this->assertCount(2, $this->sesionesAbiertas());
        Notification::assertNothingSent();
    }

    // ---------------------------------------------------- recuperar la contraseña

    public function test_restablecer_con_el_link_cierra_todas_las_sesiones_y_avisa(): void
    {
        $this->conDosSesiones();
        $tokenDelLink = Password::createToken($this->usuario);

        $this->postJson('/api/reset-password', [
            'token' => $tokenDelLink,
            'email' => 'viejo@mail.com',
            'password' => 'ClaveNueva1',
            'password_confirmation' => 'ClaveNueva1',
        ])->assertOk();

        $this->assertTrue(Hash::check('ClaveNueva1', $this->usuario->fresh()->password));
        $this->assertSame([], $this->sesionesAbiertas());
        Notification::assertSentTo($this->usuario, PasswordCambiadaNotification::class);
    }

    public function test_un_link_vencido_o_ajeno_da_un_mensaje_claro_y_no_cambia_nada(): void
    {
        $this->postJson('/api/reset-password', [
            'token' => 'token-que-no-existe',
            'email' => 'viejo@mail.com',
            'password' => 'ClaveNueva1',
            'password_confirmation' => 'ClaveNueva1',
        ])->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'El link para restablecer la contraseña venció o ya fue usado. Pedí uno nuevo desde "Olvidé mi contraseña".');

        $this->assertTrue(Hash::check('ClaveVieja1', $this->usuario->fresh()->password));
        Notification::assertNothingSent();
    }

    public function test_el_link_de_una_cuenta_no_sirve_para_restablecer_otra(): void
    {
        $otra = User::forceCreate([
            'name' => 'Otra', 'email' => 'otra@mail.com',
            'password' => Hash::make('ClaveOtra1'), 'email_verified_at' => now(),
        ]);
        $tokenDeOtra = Password::createToken($otra);

        // Alguien con el link de "otra" intenta usarlo contra la cuenta de Lucio.
        $this->postJson('/api/reset-password', [
            'token' => $tokenDeOtra,
            'email' => 'viejo@mail.com',
            'password' => 'Hackeada123',
            'password_confirmation' => 'Hackeada123',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('ClaveVieja1', $this->usuario->fresh()->password));
    }
}
