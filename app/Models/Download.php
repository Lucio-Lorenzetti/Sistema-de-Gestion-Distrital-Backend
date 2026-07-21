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
        return $this->archivo_path ? url(Storage::url($this->archivo_path)) : null;
    }


    public function getUrlDescargaAttribute()
    {
        if ($this->tipo !== 'archivo') {
            return null;
        }
        return url("/api/bibliografia/{$this->id}/descargar");
    }
}