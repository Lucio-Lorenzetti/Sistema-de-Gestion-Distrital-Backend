<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'nombre',
        'requiere_rama',
        'requiere_grupo',
        'autosolicitable',
        'reemplazo_unico',
        'unico_por_usuario',
    ];

    protected $casts = [
        'requiere_rama' => 'boolean',
        'requiere_grupo' => 'boolean',
        'autosolicitable' => 'boolean',
        'unico_por_usuario' => 'boolean',
    ];

    public $timestamps = false; // Como no le pusimos timestamps en la migración, hay que avisarle
}
