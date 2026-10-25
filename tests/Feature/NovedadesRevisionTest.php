<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProgramNote;
use App\Services\NovedadesRevision;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feed de novedades de revisión de los auxiliares de programa. Sin base de datos:
 * el servicio trabaja sobre modelos ya cargados, que se arman a mano en memoria.
 */
class NovedadesRevisionTest extends TestCase
{
    private const YO = 1;
    private const EDUCADOR = 2;

    private Carbon $ahora;
    private Carbon $desde;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahora = Carbon::parse('2026-10-26 12:00:00');
        $this->desde = $this->ahora->copy()->subDays(14);
    }

    private function programa(int $id, ?Carbon $enviadoAt, bool $trasComentarios = false): Program
    {
        $p = new Program();
        $p->id = $id;
        $p->titulo = "Programa {$id}";
        $p->enviado_at = $enviadoAt;
        $p->enviado_tras_comentarios = $trasComentarios;
        // En producción el controlador las trae precargadas; acá se evita ir a la base.
        $p->setRelation('owner', null)->setRelation('rama', null)->setRelation('grupo', null);

        return $p;
    }

    private function hilo(int $programaId, array $respuestas = [], ?Carbon $resueltoAt = null, ?int $resueltoPor = null): ProgramNote
    {
        $h = new ProgramNote();
        $h->program_id = $programaId;
        $h->contenido = 'Comentario raíz';
        $h->resuelta = $resueltoAt !== null;
        $h->resuelta_at = $resueltoAt;
        $h->resuelta_por = $resueltoPor;
        $h->setRelation('replies', collect($respuestas))->setRelation('resolutor', null);

        return $h;
    }

    private function respuesta(int $userId, Carbon $cuando, string $texto = 'Respuesta'): ProgramNote
    {
        $r = new ProgramNote();
        $r->user_id = $userId;
        $r->contenido = $texto;
        $r->created_at = $cuando;
        $r->setRelation('user', null);

        return $r;
    }

    private function tipos(iterable $programas, iterable $hilos): array
    {
        return NovedadesRevision::armar(collect($programas), collect($hilos), self::YO, $this->desde)
            ->pluck('tipo')->all();
    }

    public function test_un_programa_recien_enviado_aparece_como_enviado(): void
    {
        $p = $this->programa(10, $this->ahora->copy()->subHour());

        $this->assertSame(['enviado'], $this->tipos([$p], []));
    }

    public function test_un_programa_reenviado_tras_comentarios_aparece_como_reenviado(): void
    {
        $p = $this->programa(10, $this->ahora->copy()->subHour(), true);

        $this->assertSame(['reenviado'], $this->tipos([$p], []));
    }

    public function test_un_envio_viejo_no_aparece(): void
    {
        $p = $this->programa(10, $this->ahora->copy()->subDays(30));

        $this->assertSame([], $this->tipos([$p], []));
    }

    public function test_aparece_la_respuesta_de_otra_persona_pero_no_la_propia(): void
    {
        $p = $this->programa(10, null);
        $hilo = $this->hilo(10, [
            $this->respuesta(self::YO, $this->ahora->copy()->subHours(2)),
            $this->respuesta(self::EDUCADOR, $this->ahora->copy()->subHour(), 'Ya lo corregí'),
        ]);

        $novedades = NovedadesRevision::armar(collect([$p]), collect([$hilo]), self::YO, $this->desde);

        $this->assertCount(1, $novedades);
        $this->assertSame('respuesta', $novedades[0]['tipo']);
        $this->assertSame('Ya lo corregí', $novedades[0]['detalle']);
    }

    public function test_varias_respuestas_del_mismo_hilo_cuentan_una_sola_vez(): void
    {
        $p = $this->programa(10, null);
        $hilo = $this->hilo(10, [
            $this->respuesta(self::EDUCADOR, $this->ahora->copy()->subHours(3)),
            $this->respuesta(self::EDUCADOR, $this->ahora->copy()->subHours(1)),
        ]);

        $this->assertSame(['respuesta'], $this->tipos([$p], [$hilo]));
    }

    public function test_aparece_el_hilo_que_resolvio_otra_persona_pero_no_el_que_resolvi_yo(): void
    {
        $p = $this->programa(10, null);
        $ajeno = $this->hilo(10, [], $this->ahora->copy()->subHour(), self::EDUCADOR);
        $propio = $this->hilo(10, [], $this->ahora->copy()->subHour(), self::YO);

        $this->assertSame(['resuelto'], $this->tipos([$p], [$ajeno, $propio]));
    }

    public function test_un_hilo_resuelto_antes_de_esta_funcion_no_aparece(): void
    {
        $p = $this->programa(10, null);
        $hilo = $this->hilo(10, []);
        $hilo->resuelta = true; // resuelto de antes: sin fecha ni autor registrados

        $this->assertSame([], $this->tipos([$p], [$hilo]));
    }

    public function test_los_hilos_de_programas_fuera_de_la_lista_se_ignoran(): void
    {
        $p = $this->programa(10, null);
        $hiloDeOtroPrograma = $this->hilo(99, [$this->respuesta(self::EDUCADOR, $this->ahora->copy()->subHour())]);

        $this->assertSame([], $this->tipos([$p], [$hiloDeOtroPrograma]));
    }

    public function test_se_ordenan_de_mas_nueva_a_mas_vieja_y_respetan_el_limite(): void
    {
        $viejo = $this->programa(1, $this->ahora->copy()->subDays(3));
        $nuevo = $this->programa(2, $this->ahora->copy()->subHour());
        $medio = $this->programa(3, $this->ahora->copy()->subDay());

        $novedades = NovedadesRevision::armar(collect([$viejo, $nuevo, $medio]), collect(), self::YO, $this->desde, 2);

        $this->assertSame([2, 3], $novedades->pluck('program.id')->all());
    }
}
