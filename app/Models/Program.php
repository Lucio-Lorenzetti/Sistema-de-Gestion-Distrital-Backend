<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'diagnostico',
        'objetivos',
        'educadores_a_cargo',
        'tipo',
        'rama_id',
        'grupo_id',
        'owner_id',
        'fecha_inicio',
        'fecha_fin',
        'cronograma',
        'anexos',
        'estado',
        'aprobacion_solicitada_at',
    ];

    protected $casts = [
        'cronograma' => 'array',
        'anexos' => 'array',
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'aprobacion_solicitada_at' => 'datetime',
    ];

    // Relaciones
    public function rama()
    {
        return $this->belongsTo(Rama::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function notes()
    {
        return $this->hasMany(ProgramNote::class)->whereNull('parent_id')->orderBy('created_at');
    }
}