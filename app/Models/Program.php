<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $table = 'programs';

    protected $fillable = [
        'titulo',
        'diagnostico',
        'objetivos',
        'cronograma',
        'anexos',
        'estado',
        'owner_id',
        'rama_id',
        'grupo_id',
    ];

    protected $casts = [
        'cronograma' => 'array',
        'anexos' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rama()
    {
        return $this->belongsTo(Rama::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Scope de visibilidad para Educadores:
     * ve los programas que él mismo subió, o los de su mismo grupo + rama.
     *
     * Uso: Program::visiblePara($user)->get();
     */
    public function scopeVisiblePara($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('grupo_id', $user->grupo_id)
                     ->where('rama_id', $user->rama_id);
              });
        });
    }
}