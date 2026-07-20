<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'link_formulario',
        'categoria',
        'ramas',
        'fecha_cierre',
        'fecha_fin',
    ];

    protected $casts = [
        'ramas' => 'array',
        'fecha_cierre' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
    ];

    // — El estado nunca se guarda: se calcula siempre en base a la fecha de hoy —
    protected $appends = ['estado'];

    public function getEstadoAttribute(): string
    {
        $hoy = now()->toDateString();

        if ($this->fecha_fin && $hoy > $this->fecha_fin) {
            return 'Finalizado';
        }
        if ($this->fecha_cierre && $hoy > $this->fecha_cierre) {
            return 'Cerrado';
        }
        return 'Abierto';
    }
}
