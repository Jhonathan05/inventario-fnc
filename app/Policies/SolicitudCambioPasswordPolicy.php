<?php

namespace App\Policies;

use App\Models\SolicitudCambioPassword;
use App\Models\User;

class SolicitudCambioPasswordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SolicitudCambioPassword $solicitud): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SolicitudCambioPassword $solicitud): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, SolicitudCambioPassword $solicitud): bool
    {
        return $user->isAdmin();
    }
}
