<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class CapitalizeFirstLetter extends TransformsRequest
{
    /**
     * Rutas que JAMÁS se transforman: en los catálogos y en las correcciones
     * el valor debe ser EXACTO (códigos de catálogo, seriales, hostnames,
     * procesadores en mayúsculas…). El title-case es para nombres de
     * personas en formularios, no para datos técnicos.
     */
    private const RUTAS_EXACTAS = [
        'catalogos',
        'correcciones',
    ];
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
        // Firma de URLs firmadas (verification.verify). `MB_CASE_TITLE` la
        // corrompía (p. ej. "e3406bf4..." → "E3406Bf4...") y el middleware
        // `signed` rechazaba el enlace con 403.
        'signature',
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

    public function handle($request, \Closure $next)
    {
        $ruta = $request->path();

        foreach (self::RUTAS_EXACTAS as $prefijo) {
            if ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/')) {
                return $next($request); // datos técnicos: valor exacto, sin transformar
            }
        }

        return parent::handle($request, $next);
    }
}
