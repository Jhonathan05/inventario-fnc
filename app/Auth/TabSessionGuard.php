<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;

class TabSessionGuard extends SessionGuard
{
    /**
     * Get a unique identifier for the auth session value.
     *
     * @return string
     */
    public function getName()
    {
        $tabId = $this->request->input('_tab') ?: $this->request->query('_tab') ?: $this->request->header('X-Tab-Id') ?: 'default';
        return 'login_' . $this->name . '_' . sha1(static::class) . '_' . $tabId;
    }
}
