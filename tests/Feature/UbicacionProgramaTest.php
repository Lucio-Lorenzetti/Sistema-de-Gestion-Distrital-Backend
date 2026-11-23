<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UbicacionPrograma;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * De qué rama y grupo es un programa nuevo: el usuario común queda atado a la
 * suya; solo el Developer puede elegir. Sin base de datos: los usuarios y sus
 * roles se arman a mano en memoria.
 */
class UbicacionProgramaTest extends TestCase
{
    private function usuario(array $roles, ?int $rama = null, ?int $grupo = null): User
    {
        $user = new User();
        $user->id = 1;
        $user->rama_id = $rama;
        $user->grupo_id = $grupo;
        $user->setRelation('roles', collect($roles)->map(fn ($nombre) => (object) ['nombre' => $nombre]));

        return $user;
    }

    private function mensajeDe(callable $accion, string $campo): string
    {
        try {
            $accion();
        } catch (ValidationException $e) {
            return $e->errors()[$campo][0] ?? '';
        }

        $this->fail('Tendría que haber fallado la validación.');
    }

    public function test_el_educador_usa_su_propia_rama_y_grupo(): void
    {
        $educador = $this->usuario(['Educador'], rama: 3, grupo: 7);

        $this->assertSame(['rama_id' => 3, 'grupo_id' => 7], UbicacionPrograma::resolver($educador, []));
    }

    public function test_el_educador_no_puede_elegir_otra_rama_ni_otro_grupo(): void
    {
        $educador = $this->usuario(['Educador'], rama: 3, grupo: 7);

        $resultado = UbicacionPrograma::resolver($educador, ['rama_id' => 5, 'grupo_id' => 9]);

        $this->assertSame(['rama_id' => 3, 'grupo_id' => 7], $resultado);
    }

    public function test_un_usuario_sin_rama_recibe_un_mensaje_claro(): void
    {
        $sinRama = $this->usuario(['Educador']);

        $mensaje = $this->mensajeDe(fn () => UbicacionPrograma::resolver($sinRama, []), 'rama_id');

        $this->assertStringContainsString('no tiene una rama asignada', $mensaje);
    }

    public function test_el_developer_elige_rama_y_grupo(): void
    {
        $developer = $this->usuario(['Developer']);

        $resultado = UbicacionPrograma::resolver($developer, ['rama_id' => '5', 'grupo_id' => '9']);

        $this->assertSame(['rama_id' => 5, 'grupo_id' => 9], $resultado);
    }

    public function test_el_developer_que_no_elige_rama_recibe_un_mensaje_claro(): void
    {
        $developer = $this->usuario(['Developer']);

        $mensaje = $this->mensajeDe(fn () => UbicacionPrograma::resolver($developer, ['grupo_id' => 9]), 'rama_id');

        $this->assertSame('Elegí la rama a la que pertenece el programa.', $mensaje);
    }

    public function test_el_developer_que_no_elige_grupo_recibe_un_mensaje_claro(): void
    {
        $developer = $this->usuario(['Developer']);

        $mensaje = $this->mensajeDe(fn () => UbicacionPrograma::resolver($developer, ['rama_id' => 5]), 'grupo_id');

        $this->assertSame('Elegí el grupo al que pertenece el programa.', $mensaje);
    }

    public function test_el_developer_que_ademas_es_educador_usa_su_asignacion_si_no_elige(): void
    {
        $ambos = $this->usuario(['Developer', 'Educador'], rama: 3, grupo: 7);

        $this->assertSame(['rama_id' => 3, 'grupo_id' => 7], UbicacionPrograma::resolver($ambos, []));
    }

    public function test_el_developer_que_ademas_es_educador_no_puede_elegir_otra_rama(): void
    {
        $ambos = $this->usuario(['Developer', 'Educador'], rama: 3, grupo: 7);

        $resultado = UbicacionPrograma::resolver($ambos, ['rama_id' => 5, 'grupo_id' => 9]);

        $this->assertSame(['rama_id' => 3, 'grupo_id' => 7], $resultado);
    }

    public function test_el_developer_con_otro_rol_que_no_es_educador_sigue_eligiendo(): void
    {
        $conComunicacion = $this->usuario(['Developer', 'Aux Comunicación']);

        $resultado = UbicacionPrograma::resolver($conComunicacion, ['rama_id' => 5, 'grupo_id' => 9]);

        $this->assertSame(['rama_id' => 5, 'grupo_id' => 9], $resultado);
    }
}
