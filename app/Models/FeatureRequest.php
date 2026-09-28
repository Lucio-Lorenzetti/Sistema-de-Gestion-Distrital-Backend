<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureRequest extends Model
{
    protected $fillable = ['autor_id', 'contenido', 'estado', 'prioridad'];

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
