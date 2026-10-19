<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * La verificación de correo (rutas `verify-email`, middleware `verified`) sigue
 * activa: los usuarios se aprovisionan ya verificados desde Keycloak/mock
 * (ver App\Services\Auth\IdentityProvisioner), y el contrato se mantiene
 * funcional para los grupos de rutas que usan `verified`.
 */
class User extends Authenticatable implements MustVerifyEmail
{
const ROLE_ADMIN = 'Administrador';
    const ROLE_ANALISTA = 'Analista Tic';

    /**
     * @use HasFactory<UserFactory>
     */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Perfiles con gestión total (conceder/cambiar roles, editar usuarios
     * admin, aprobar cambios de contraseña). Lote 19: el Analista TIC se
     * iguala al Administrador en privilegios; toda verificación de
     * "privilegio de gestión" debe usar este helper en lugar de comparar
     * `hasRole('Administrador')` a mano.
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_ANALISTA]);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'force_password_change',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'force_password_change' => 'boolean',
        ];
    }
}
