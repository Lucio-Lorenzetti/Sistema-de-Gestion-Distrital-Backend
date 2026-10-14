<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use Illuminate\Support\Facades\Cache;

class GrupoController extends Controller
{
    /**
     * Catálogo de grupos (id + nombre): no hay ningún endpoint en la app que
     * cree o renombre un grupo, así que se puede cachear con un TTL largo.
     * Se pide en casi todas las pantallas (programas, usuarios, mi perfil,
     * modales de rol), antes recorriendo la tabla completa en cada pedido.
     */
    public function index()
    {
        $grupos = Cache::remember('catalogo:grupos', 86400, function () {
            return Grupo::select('id', 'nombre')->orderBy('nombre')->get();
        });

        return response()->json($grupos);
    }
}