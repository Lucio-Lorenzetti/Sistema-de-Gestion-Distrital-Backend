<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    // Agregamos 'numero' aquí
    protected $fillable = ['numero', 'nombre', 'distrito_id'];

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
}