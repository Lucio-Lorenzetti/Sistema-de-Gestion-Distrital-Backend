<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'titulo',
        'contenido',
        'autor_id',      
        'publicado_at'  
    ];

    // Para que Laravel trate a publicado_at como una fecha real
    protected $casts = [
        'publicado_at' => 'datetime',
    ];

    // La relación debe apuntar a la tabla Users usando autor_id
    public function autor() { 
        return $this->belongsTo(User::class, 'autor_id'); 
    }
}