<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Rama;
use Illuminate\Support\Facades\Cache;

class RamaController extends Controller
{
    public function index()
    {
        // Orden por id (no alfabético): coincide con el orden real en que se
        // siembran las ramas (Castores, Lobatos, Unidad Scout, Caminantes,
        // Rovers) y es el orden que se espera en cualquier lugar de la app
        // donde se listen ramas.
        //
        // No hay ningún endpoint que cree/renombre una rama: TTL largo, sin
        // invalidación.
        $ramas = Cache::remember('catalogo:ramas', 86400, function () {
            return Rama::select('id', 'nombre')->orderBy('id')->get();
        });

        return response()->json($ramas);
    }
}
