<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Grupo extends Model
{
    protected $fillable = [
        'numero',
        'nombre',
        'distrito_id',
        'direccion',
        'telefono',
        'telefono_whatsapp',
        'instagram',
        'facebook',
        'descripcion',
    ];

    protected $casts = [
        'telefono_whatsapp' => 'boolean',
    ];

    protected $hidden = ['foto'];

    protected $appends = ['foto_url'];

    /**
     * Mismo criterio que User::getFotoPerfilUrlAttribute(): el disco "s3"
     * devuelve URL absoluta, el "public" local una relativa.
     */
    public function getFotoUrlAttribute()
    {
        if (!$this->foto) {
            return null;
        }

        $path = Storage::disk(config('filesystems.uploads_disk'))->url($this->foto);

        return str_starts_with($path, 'http') ? $path : url($path);
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
}
