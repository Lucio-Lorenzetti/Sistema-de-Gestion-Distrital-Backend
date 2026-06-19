<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'contenido',
        'autor_id',
        'publicado_at',
        'estado',       // Nuevo campo
        'categoria',    // Nuevo campo
        'visitas',      // Nuevo campo
    ];

    protected $casts = [
        'publicado_at' => 'datetime',
        'visitas' => 'integer', // Aseguramos que siempre sea un número
    ];

    // Relación con el autor
    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}