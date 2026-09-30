<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class CapitalizeFirstLetter extends TransformsRequest
{
    /**
     * The attributes that should not be capitalized.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
        'email',
        '_token',
        '_method',
        'username', // If username is used for login and shouldn't be touched
        'remember',
        'slug',
        'uuid',
        'estado_operativo',
        'serial',
        'activo_fijo',
        'placa',
        'permissions',
        '_tab',
        'return_to',
    ];

    /**
     * Clean the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function cleanValue($key, $value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        return parent::cleanValue($key, $value);
    }

    /**
     * Transform the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        if (is_string($value)) {
            // Convierte a minúsculas y luego capitaliza cada palabra
            return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return $value;
    }
}
