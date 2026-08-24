<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Rama;

class RamaController extends Controller
{
    public function index()
    {
        // Orden por id (no alfabético): coincide con el orden real en que se
        // siembran las ramas (Castores, Lobatos, Unidad Scout, Caminantes,
        // Rovers) y es el orden que se espera en cualquier lugar de la app
        // donde se listen ramas.
        return response()->json(
            Rama::select('id', 'nombre')->orderBy('id')->get()
        );
    }
}
