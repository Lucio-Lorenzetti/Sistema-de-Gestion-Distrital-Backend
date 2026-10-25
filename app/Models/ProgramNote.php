<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramNote extends Model
{
    // Permitimos que estos campos se llenen desde el controlador [cite: 1411]
    protected $fillable = [
        'program_id',
        'user_id',
        'parent_id',
        'line_ref',
        'line_texto',
        'contenido',
        'resuelta',
        'resuelta_por',
        'resuelta_at',
    ];

    protected $casts = [
        'resuelta' => 'boolean',
        'resuelta_at' => 'datetime',
    ];

    public function resolutor() {
        return $this->belongsTo(User::class, 'resuelta_por');
    }

    public function program() {
        return $this->belongsTo(Program::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function parent() {
        return $this->belongsTo(ProgramNote::class, 'parent_id');
    }

    public function replies() {
        return $this->hasMany(ProgramNote::class, 'parent_id');
    }
}