<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleRequest extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'rama_id',
        'grupo_id',
        'estado',
        'motivo_rechazo',
        'revisado_por_id',
        'revisado_at',
    ];

    protected $casts = [
        'revisado_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function rama()
    {
        return $this->belongsTo(Rama::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por_id');
    }
}
