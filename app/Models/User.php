<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
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
        'must_change_password', // 🛠️ AGREGADO: Para evitar errores de MassAssignment en Seeders
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
    
    /**
     * Relación con Roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Helper útil para tus Middlewares de "Educador" vs "Director"
     */
    public function hasRole($roleNombre)
    {
        // 🛠️ CORREGIDO: de $nombreDelRol a $roleNombre para que use el parámetro real de la función
        return $this->roles()
                ->whereRaw('LOWER(nombre) = ?', [strtolower($roleNombre)])
                ->exists();
    }

    /**
     * Relación con el Grupo (Pompeya, etc.)
     */
    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    /**
     * 🛠️ SOLUCIÓN AL ERROR 500: Relación con la Rama (Manada, Caminantes, etc.)
     * Ahora el ->load('rama') del AuthController no va a fallar
     */
    public function rama()
    {
        return $this->belongsTo(Rama::class, 'rama_id');
    }
}