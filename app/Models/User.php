<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'grupo_id',
        'rama_id',
        'activo',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->id === 1) {
                throw new \Exception("Acción denegada: El SuperAdministrador no puede ser eliminado.");
            }
        });
    }

    /**
     * Relación con Roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole($roleNombre)
    {
        return $this->roles()
            ->whereRaw('LOWER(nombre) = ?', [strtolower($roleNombre)])
            ->exists();
    }

    /**
     * Verifica si el usuario tiene alguno de los roles indicados
     * Uso: $user->hasAnyRole(['Director', 'Aux Comunicación'])
     */
    public function hasAnyRole(array $roles): bool
    {
        $rolesNormalizados = array_map('strtolower', $roles);

        return $this->roles()
            ->whereIn(\DB::raw('LOWER(nombre)'), $rolesNormalizados)
            ->exists();
    }

    /**
     * Relación con el Grupo
     */
    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    /**
     * Relación con la Rama
     */
    public function rama()
    {
        return $this->belongsTo(Rama::class, 'rama_id');
    }
}