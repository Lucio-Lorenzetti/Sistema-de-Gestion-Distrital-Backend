<?php

namespace Tests\Feature;

use App\Services\ErrorBuffer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Pruebas del Dashboard del Developer que no necesitan base de datos.
 *
 * Las migraciones del proyecto usan SQL propio de PostgreSQL, así que los tests
 * contra la base (SQLite en memoria) todavía no pueden correrlas: por eso acá
 * solo se cubre lo que depende del cache y de la capa de seguridad.
 */
class SistemaDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_los_endpoints_del_dashboard_exigen_sesion(): void
    {
        $this->getJson('/api/sistema/estado')->assertUnauthorized();
        $this->getJson('/api/sistema/metricas')->assertUnauthorized();
    }

    public function test_el_registro_de_actividad_ya_no_es_publico(): void
    {
        $this->getJson('/api/activity-logs')->assertUnauthorized();
    }

    public function test_el_buffer_guarda_errores_y_advertencias_pero_no_info(): void
    {
        Log::info('Mensaje informativo');
        Log::warning('Advertencia de prueba');
        Log::error('Falla de prueba del SMTP');

        $mensajes = array_column(ErrorBuffer::recientes(), 'mensaje');

        // Más reciente primero.
        $this->assertSame(['Falla de prueba del SMTP', 'Advertencia de prueba'], $mensajes);
    }

    public function test_el_buffer_conserva_solo_las_ultimas_25_entradas(): void
    {
        foreach (range(1, 30) as $n) {
            Log::error("Error {$n}");
        }

        $recientes = ErrorBuffer::recientes();

        $this->assertCount(25, $recientes);
        $this->assertSame('Error 30', $recientes[0]['mensaje']);
        $this->assertSame('Error 6', $recientes[24]['mensaje']);
    }

    public function test_el_buffer_recorta_los_mensajes_largos(): void
    {
        Log::error(str_repeat('x', 1000));

        $this->assertLessThanOrEqual(300, mb_strlen(ErrorBuffer::recientes()[0]['mensaje']));
    }
}
