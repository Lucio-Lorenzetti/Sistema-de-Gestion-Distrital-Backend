<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Download extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'archivo_path',
        'link',
        'tipo',
        'user_id'
    ];

    protected $appends = ['url_publica'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Devuelve la URL final, sea archivo subido o link externo
    public function getUrlPublicaAttribute()
    {
        if ($this->tipo === 'link') {
            return $this->link;
        }
        return $this->archivo_path ? Storage::url($this->archivo_path) : null;
    }
}