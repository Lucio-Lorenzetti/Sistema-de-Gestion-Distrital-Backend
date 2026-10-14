<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DistritoController extends Controller
{
    /**
     * Roles que forman el Consejo Distrital, en el orden en que se muestran
     * en la página pública. Developer queda afuera a propósito (es un rol
     * técnico, no un cargo del distrito).
     */
    private const ROLES_CONSEJO = ['Director', 'Aux Prog General', 'Aux Prog Rama', 'Aux Comunicación'];

    /**
     * Datos públicos de /distrito: integrantes del Consejo Distrital y grupos
     * con su Jefe de Grupo. Solo usuarios activos y no borrados.
     */
    /**
     * Depende de varias fuentes que cambian en puntos distintos de la app
     * (designaciones, aprobación de solicitudes de rol, foto/datos de un
     * grupo), así que en vez de invalidar desde cada uno de esos puntos
     * —superficie grande, fácil olvidarse de uno y dejar esto desactualizado—
     * se cachea con un TTL corto. Es la página pública "El Distrito": no hace
     * falta que se actualice al instante.
     */
    public function show()
    {
        return response()->json(
            Cache::remember('distrito:show', 120, fn () => $this->calcular())
        );
    }

    private function calcular(): array
    {
        $consejo = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->leftJoin('ramas', 'ramas.id', '=', 'user_roles.rama_id')
            ->whereIn('roles.nombre', self::ROLES_CONSEJO)
            ->where('users.activo', true)
            ->whereNull('users.deleted_at')
            ->select('users.id as user_id', 'users.name', 'users.totem', 'users.email', 'roles.nombre as rol', 'ramas.nombre as rama')
            ->get()
            ->sortBy(fn ($m) => [array_search($m->rol, self::ROLES_CONSEJO), $m->rama ?? '', $m->name])
            ->values();

        $jefes = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->where('roles.nombre', 'Jefe de Grupo')
            ->where('users.activo', true)
            ->whereNull('users.deleted_at')
            ->select('user_roles.grupo_id', 'users.name', 'users.totem', 'users.email')
            ->get()
            ->keyBy('grupo_id');

        $grupos = Grupo::select('id', 'numero', 'nombre', 'foto', 'direccion', 'telefono', 'telefono_whatsapp', 'instagram', 'facebook', 'descripcion')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($g) => [
                ...$g->toArray(),
                'jefe' => $jefes->get($g->id),
            ]);

        return [
            'consejo' => $consejo,
            'grupos' => $grupos,
        ];
    }
}
