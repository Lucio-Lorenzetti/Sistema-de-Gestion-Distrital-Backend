<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- IMPORTANTE: Importar Sanctum
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    // Agregamos HasApiTokens para que Laravel pueda generar los tokens de sesión
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'grupo_id', 
        'rama_id', 
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * El "Escudo" del SuperAdmin
     */
    protected static function booted()
    {
        static::deleting(function ($user) {
            // Si intentan borrar al ID 1, lanzamos un error
            if ($user->id === 1) {
                throw new \Exception("Acción denegada: El SuperAdministrador no puede ser eliminado.");
            }
        });
    }
    
    // Relación con Roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // Helper útil para tus Middlewares de "Educador" vs "Director"
    public function hasRole($roleSlug)
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    // Relación con el Grupo (Pompeya, etc.)
    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }
}