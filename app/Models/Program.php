<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{

    protected $fillable = [
        'titulo',
        'diagnostico',
        'objetivos',
        'cronograma',
        'anexos',
        'estado',
        'user_id',
        'rama_id',
        'grupo_id'
    ];

    // Esto convierte los campos JSON de la DB en arrays de PHP automáticamente
    protected $casts = [
        'cronograma' => 'array',
        'anexos' => 'array',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function rama() {
        return $this->belongsTo(Rama::class);
    }

    public function grupo() {
        return $this->belongsTo(Grupo::class);
    }

    public function notes() {
        return $this->hasMany(ProgramNote::class);
    }
}
