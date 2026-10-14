<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmailContract
{
    use CanResetPassword, HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes;

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
        if (!$this->foto_perfil) {
            return null;
        }

        // El disco "s3" (Supabase Storage) ya devuelve una URL absoluta;
        // el disco local "public" devuelve una ruta relativa que hay que
        // completar con el dominio de la app.
        $path = Storage::disk(config('filesystems.uploads_disk'))->url($this->foto_perfil);

        return str_starts_with($path, 'http') ? $path : url($path);
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
     * Roles del usuario, cargados una sola vez y reusados durante todo el
     * pedido. isDeveloper()/hasRole()/hasAnyRole()/roleScope() se llaman varias
     * veces por request (controller + Policy + Gate::before); sin este caché
     * cada llamada disparaba una consulta nueva.
     *
     * Ojo: si se modifican los roles de ESTE usuario a mitad de pedido
     * (sync/detach sobre $this), hay que invalidar con
     * $this->unsetRelation('roles') antes de volver a consultar.
     */
    protected function rolesCargados()
    {
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }

        return $this->roles;
    }

    /**
     * Developer: acceso total, ver User::hasRole()/hasAnyRole() y el
     * Gate::before en AppServiceProvider. Consulta directa (no vía hasRole)
     * para no recursar.
     */
    public function isDeveloper(): bool
    {
        return $this->rolesCargados()
            ->contains(fn ($rol) => strtolower($rol->nombre) === 'developer');
    }

    /**
     * El scope (rama/grupo) de UNA asignación de rol puntual — a diferencia de
     * $this->rama_id/$this->grupo_id, que son solo el caché de la asignación de
     * Educador (ver App\Services\UserScopeCache). Usar este método para Jefe de
     * Grupo / Aux Prog Rama, cuyo scope real vive en el pivot user_roles.
     */
    public function roleScope(string $roleNombre): ?object
    {
        $nombre = strtolower($roleNombre);

        $rol = $this->rolesCargados()
            ->first(fn ($rol) => strtolower($rol->nombre) === $nombre);

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

        $nombre = strtolower($roleNombre);

        return $this->rolesCargados()
            ->contains(fn ($rol) => strtolower($rol->nombre) === $nombre);
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

        return $this->rolesCargados()
            ->contains(fn ($rol) => in_array(strtolower($rol->nombre), $rolesNormalizados));
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