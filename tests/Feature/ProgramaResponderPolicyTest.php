<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use App\Policies\ProgramPolicy;
use Tests\TestCase;

/**
 * Quién puede responder/resolver hilos según el estado del programa.
 * Sin base de datos: la política solo lee atributos y la relación de roles,
 * que se arman a mano en memoria.
 */
class ProgramaResponderPolicyTest extends TestCase
{
    private function usuario(int $id, int $grupo, int $rama): User
    {
        $user = new User();
        $user->id = $id;
        $user->grupo_id = $grupo;
        $user->rama_id = $rama;
        $user->setRelation('roles', collect());

        return $user;
    }

    private function programa(string $estado): Program
    {
        $program = new Program();
        $program->owner_id = 1;
        $program->grupo_id = 10;
        $program->rama_id = 20;
        $program->estado = $estado;

        return $program;
    }

    public function test_en_borrador_el_equipo_del_programa_puede_responder_y_resolver(): void
    {
        $policy = new ProgramPolicy();
        $program = $this->programa('borrador');

        $this->assertTrue($policy->responder($this->usuario(1, 10, 20), $program), 'El autor.');
        $this->assertTrue($policy->responder($this->usuario(2, 10, 20), $program), 'Otro educador del mismo grupo y rama.');
    }

    public function test_en_borrador_quien_no_es_del_equipo_no_responde(): void
    {
        $policy = new ProgramPolicy();
        $program = $this->programa('borrador');

        $this->assertFalse($policy->responder($this->usuario(3, 99, 20), $program), 'Otro grupo, misma rama.');
        $this->assertFalse($policy->responder($this->usuario(4, 10, 99), $program), 'Mismo grupo, otra rama.');
    }

    public function test_en_borrador_no_se_crean_hilos_nuevos(): void
    {
        $policy = new ProgramPolicy();

        $this->assertFalse($policy->comment($this->usuario(1, 10, 20), $this->programa('borrador')));
    }

    public function test_en_enviado_responder_sigue_la_misma_regla_que_comentar(): void
    {
        $policy = new ProgramPolicy();
        $program = $this->programa('enviado');
        $educadorDeLaRama = $this->usuario(5, 99, 20);
        $educadorDeOtraRama = $this->usuario(6, 10, 99);

        $this->assertTrue($policy->responder($educadorDeLaRama, $program));
        $this->assertFalse($policy->responder($educadorDeOtraRama, $program));
    }

    public function test_aprobado_y_rechazado_dejan_los_hilos_en_solo_lectura(): void
    {
        $policy = new ProgramPolicy();
        $autor = $this->usuario(1, 10, 20);

        $this->assertFalse($policy->responder($autor, $this->programa('aprobado')));
        $this->assertFalse($policy->responder($autor, $this->programa('rechazado')));
    }
}
