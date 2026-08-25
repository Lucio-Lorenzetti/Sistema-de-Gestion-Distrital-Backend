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
   
    protected $appends = ['url_publica', 'url_descarga'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlPublicaAttribute()
    {
        if ($this->tipo === 'link') {
            return $this->link;
        }
        if (!$this->archivo_path) {
            return null;
        }

        // Mismo criterio que User::getFotoPerfilUrlAttribute(): el disco "s3"
        // (Supabase Storage) devuelve una URL absoluta, el disco local
        // "public" devuelve una ruta relativa que hay que completar.
        $path = Storage::disk(config('filesystems.uploads_disk'))->url($this->archivo_path);

        return str_starts_with($path, 'http') ? $path : url($path);
    }


    public function getUrlDescargaAttribute()
    {
        if ($this->tipo !== 'archivo') {
            return null;
        }
        return url("/api/bibliografia/{$this->id}/descargar");
    }
}