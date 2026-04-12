<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramNote extends Model
{
    // Permitimos que estos campos se llenen desde el controlador [cite: 1411]
    protected $fillable = [
        'program_id',
        'user_id',
        'contenido',
        'resuelta'
    ];

    public function program() {
        return $this->belongsTo(Program::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}