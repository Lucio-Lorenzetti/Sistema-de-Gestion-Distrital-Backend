<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'copete',
        'contenido',
        'autor_id',
        'publicado_at',
        'estado',
        'categoria',
        'visitas',
        'imagen',  // ← agregá esta línea
    ];

    protected $casts = [
        'publicado_at' => 'datetime',
        'visitas' => 'integer',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}