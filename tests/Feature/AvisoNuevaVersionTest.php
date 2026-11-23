<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NuevaVersionNotification;
use App\Services\AvisoNuevaVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Aviso por mail de cada versión nueva al Director y a los Developer. Sin base de
 * datos: los destinatarios se arman a mano y el caché de los tests es en memoria.
 */
class AvisoNuevaVersionTest extends TestCase
{
    private function usuario(int $id, string $nombre): User
    {
        $usuario = new User();
        $usuario->id = $id;
        $usuario->name = $nombre;

        return $usuario;
    }

    public function test_el_mail_dice_la_version_el_titulo_y_va_solo_por_mail(): void
    {
        config(['app.frontend_url' => 'https://distrito.example']);
        $notificacion = new NuevaVersionNotification('1.5.0', 'Aviso de versiones');
        $mail = $notificacion->toMail($this->usuario(1, 'Lucio'));

        $this->assertSame(['mail'], $notificacion->via($this->usuario(1, 'Lucio')));
        $this->assertSame('Nueva versión del Sistema Distrital', $mail->subject);
        $this->assertSame('🚀 Nueva versión en marcha: v1.5.0', $mail->greeting);
        $this->assertContains(
            'Llegó una nueva versión del sistema del Distrito 3: ahora estamos en la versión v1.5.0. Gracias por acompañar el camino.',
            $mail->introLines
        );
        $this->assertStringContainsString('En esta versión: Aviso de versiones.', implode(' ', $mail->introLines));
        $this->assertSame('https://distrito.example/dashboard', $mail->actionUrl);
    }

    public function test_el_mail_sin_titulo_no_trae_la_linea_de_novedades(): void
    {
        $mail = (new NuevaVersionNotification('1.5.0'))->toMail($this->usuario(1, 'Lucio'));

        $this->assertStringNotContainsString('En esta versión', implode(' ', $mail->introLines));
    }

    public function test_la_primera_vez_avisa_la_version_actual_a_todos_los_destinatarios(): void
    {
        Notification::fake();
        Cache::flush();
        $director = $this->usuario(1, 'Director');
        $developer = $this->usuario(2, 'Developer');
        $otroDeveloper = $this->usuario(3, 'Otro Developer');

        $avisado = AvisoNuevaVersion::avisar('1.5.0', 'Título', collect([$director, $developer, $otroDeveloper]));

        $this->assertTrue($avisado);
        foreach ([$director, $developer, $otroDeveloper] as $usuario) {
            Notification::assertSentToTimes($usuario, NuevaVersionNotification::class, 1);
        }
    }

    public function test_la_misma_version_no_se_avisa_dos_veces(): void
    {
        Notification::fake();
        Cache::flush();
        $developer = $this->usuario(1, 'Developer');

        AvisoNuevaVersion::avisar('1.5.0', null, collect([$developer]));
        $segundaVez = AvisoNuevaVersion::avisar('1.5.0', null, collect([$developer]));

        $this->assertFalse($segundaVez);
        Notification::assertSentToTimes($developer, NuevaVersionNotification::class, 1);
    }

    public function test_cualquier_numero_que_suba_dispara_un_aviso_nuevo(): void
    {
        Notification::fake();
        Cache::flush();
        $developer = $this->usuario(1, 'Developer');

        // Parche, menor y mayor: cualquiera de los tres números cuenta.
        foreach (['1.5.0', '1.5.1', '1.6.0', '2.0.0'] as $version) {
            $this->assertTrue(AvisoNuevaVersion::avisar($version, null, collect([$developer])), $version);
        }

        Notification::assertSentToTimes($developer, NuevaVersionNotification::class, 4);
    }

    public function test_una_version_mas_vieja_que_la_ultima_avisada_no_se_anuncia(): void
    {
        Notification::fake();
        Cache::flush();
        $developer = $this->usuario(1, 'Developer');

        AvisoNuevaVersion::avisar('1.5.0', null, collect([$developer]));
        $rollback = AvisoNuevaVersion::avisar('1.4.0', null, collect([$developer]));

        $this->assertFalse($rollback);
        Notification::assertSentToTimes($developer, NuevaVersionNotification::class, 1);
    }

    public function test_las_versiones_se_comparan_por_numero_y_no_como_texto(): void
    {
        Notification::fake();
        Cache::flush();
        $developer = $this->usuario(1, 'Developer');

        AvisoNuevaVersion::avisar('1.9.0', null, collect([$developer]));

        // Como texto, "1.10.0" sería menor que "1.9.0"; como versión, es la siguiente.
        $this->assertTrue(AvisoNuevaVersion::avisar('1.10.0', null, collect([$developer])));
    }

    public function test_el_endpoint_rechaza_un_pedido_sin_el_secreto(): void
    {
        config(['services.aviso_version.secret' => 'secreto-de-prueba']);

        $this->postJson('/api/sistema/nueva-version', ['version' => '1.5.0'])->assertStatus(401);
        $this->postJson('/api/sistema/nueva-version', ['version' => '1.5.0'], ['X-Aviso-Secret' => 'otro'])->assertStatus(401);
    }

    public function test_el_endpoint_no_acepta_nada_si_el_secreto_no_esta_configurado(): void
    {
        config(['services.aviso_version.secret' => null]);

        $this->postJson('/api/sistema/nueva-version', ['version' => '1.5.0'], ['X-Aviso-Secret' => ''])->assertStatus(401);
    }

    public function test_el_endpoint_valida_el_formato_de_la_version(): void
    {
        config(['services.aviso_version.secret' => 'secreto-de-prueba']);

        $this->postJson('/api/sistema/nueva-version', ['version' => 'beta'], ['X-Aviso-Secret' => 'secreto-de-prueba'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('version');
    }

    public function test_el_endpoint_responde_que_no_aviso_una_version_ya_avisada(): void
    {
        config(['services.aviso_version.secret' => 'secreto-de-prueba']);
        Notification::fake();
        Cache::flush();
        // Con la versión ya registrada no se consulta a la base: llega solo hasta el servicio.
        Cache::forever('version_avisada', '1.5.0');

        $this->postJson('/api/sistema/nueva-version', ['version' => '1.5.0'], ['X-Aviso-Secret' => 'secreto-de-prueba'])
            ->assertOk()
            ->assertExactJson(['avisado' => false, 'version' => '1.5.0']);

        Notification::assertNothingSent();
    }
}
