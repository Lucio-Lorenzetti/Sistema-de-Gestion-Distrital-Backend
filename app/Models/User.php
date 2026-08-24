<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'totem',
        'email',
        'password',
        'grupo_id',
        'rama_id',
        'activo',
        'foto_perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'foto_perfil_url',
        'nombre_visible',
    ];

    public function getFotoPerfilUrlAttribute()
    {
        return $this->foto_perfil ? url(Storage::url($this->foto_perfil)) : null;
    }

    /**
     * Nombre que tiene que verse en cualquier interacción (comentarios, autoría
     * de programas, etc.): tótem + nombre real entre paréntesis si tiene tótem
     * cargado, si no el nombre real solo. Única fuente de verdad — se calcula
     * acá para que cualquier respuesta que serialice un User lo traiga ya
     * resuelto, sin tener que repetir la lógica en cada lugar del frontend.
     */
    public function getNombreVisibleAttribute(): string
    {
        return $this->totem ? "{$this->totem} ({$this->name})" : $this->name;
    }

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
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['rama_id', 'grupo_id', 'asignado_por_id', 'asignado_at']);
    }

    /**
     * Solicitudes de rol hechas POR este usuario (no las que él revisa).
     */
    public function roleRequests()
    {
        return $this->hasMany(RoleRequest::class);
    }

    /**
     * Developer: acceso total, ver User::hasRole()/hasAnyRole() y el
     * Gate::before en AppServiceProvider. Consulta directa (no vía hasRole)
     * para no recursar.
     */
    public function isDeveloper(): bool
    {
        return $this->roles()
            ->whereRaw('LOWER(nombre) = ?', ['developer'])
            ->exists();
    }

    /**
     * El scope (rama/grupo) de UNA asignación de rol puntual — a diferencia de
     * $this->rama_id/$this->grupo_id, que son solo el caché de la asignación de
     * Educador (ver App\Services\UserScopeCache). Usar este método para Jefe de
     * Grupo / Aux Prog Rama, cuyo scope real vive en el pivot user_roles.
     */
    public function roleScope(string $roleNombre): ?object
    {
        $rol = $this->roles()
            ->whereRaw('LOWER(nombre) = ?', [strtolower($roleNombre)])
            ->first();

        return $rol?->pivot;
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole($roleNombre)
    {
        if ($this->isDeveloper()) {
            return true;
        }

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
        if ($this->isDeveloper()) {
            return true;
        }

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