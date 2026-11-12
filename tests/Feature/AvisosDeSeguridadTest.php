<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailCambiadoNotification;
use App\Notifications\PasswordCambiadaNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

/**
 * Avisos por mail cuando cambia la contraseña o el correo de una cuenta. Sin
 * base de datos: solo se arma el mensaje y se mira qué dice.
 */
class AvisosDeSeguridadTest extends TestCase
{
    public function test_el_aviso_de_contrasena_va_solo_por_mail_y_ofrece_recuperar_la_cuenta(): void
    {
        config(['app.frontend_url' => 'https://distrito.example']);
        $usuario = new User();
        $usuario->name = 'Lucio';

        $notificacion = new PasswordCambiadaNotification();
        $mail = $notificacion->toMail($usuario);

        $this->assertSame(['mail'], $notificacion->via($usuario));
        $this->assertSame('Tu contraseña fue cambiada', $mail->subject);
        $this->assertSame('Hola Lucio,', $mail->greeting);
        $this->assertSame('https://distrito.example/recuperar-contrasena', $mail->actionUrl);
        $this->assertStringContainsString('NO fuiste vos', implode(' ', $mail->introLines));
    }

    public function test_el_aviso_de_correo_nombra_la_direccion_nueva_y_no_trae_enlaces(): void
    {
        $notificacion = new EmailCambiadoNotification('otro@mail.com');
        $mail = $notificacion->toMail(new AnonymousNotifiable());

        $this->assertSame(['mail'], $notificacion->via(new AnonymousNotifiable()));
        $this->assertSame('El correo de tu cuenta fue cambiado', $mail->subject);
        $this->assertStringContainsString('otro@mail.com', implode(' ', $mail->introLines));
        $this->assertStringContainsString('NO fuiste vos', implode(' ', $mail->introLines));
        // Sin botón: quien lo recibe tiene que avisar a una persona, no hacer clic.
        $this->assertNull($mail->actionUrl);
    }
}
