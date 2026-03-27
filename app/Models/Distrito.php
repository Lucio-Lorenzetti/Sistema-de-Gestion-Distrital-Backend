<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    protected $fillable = [
        'numero',
        'zona'
    ];

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }
}