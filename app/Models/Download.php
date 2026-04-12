<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'archivo_path',
        'user_id'
    ];

    public function user() { 
        return $this->belongsTo(User::class);
    }
}