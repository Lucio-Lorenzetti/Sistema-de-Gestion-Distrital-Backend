<?php

namespace App\Http\Controllers\Api\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Grupo;

class GrupoController extends Controller
{
    public function index()
    {
        return response()->json(
            Grupo::select('id', 'nombre')->orderBy('nombre')->get()
        );
    }
}