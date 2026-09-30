<?php

namespace App\Policies;

use App\Models\SolicitudCambioPassword;
use App\Models\User;

class SolicitudCambioPasswordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function view(User $user, SolicitudCambioPassword $solicitud): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(User $user, SolicitudCambioPassword $solicitud): bool
    {
        return $user->hasRole('Administrador');
    }

    public function reject(User $user, SolicitudCambioPassword $solicitud): bool
    {
        return $user->hasRole('Administrador');
    }
}
