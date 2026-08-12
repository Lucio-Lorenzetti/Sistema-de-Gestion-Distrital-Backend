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
        'contenido',
        'resuelta'
    ];

    protected $casts = [
        'resuelta' => 'boolean',
    ];

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