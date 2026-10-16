<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Blindaje previo a `migrate:fresh`.
     *
     * La suite usa `RefreshDatabase`, que destruye y recrea el esquema. Si la
     * configuración está cacheada (`php artisan config:cache`, como hace el
     * entrypoint de Docker), Laravel ignora las variables de `phpunit.xml` y
     * las pruebas apuntarían a la base de datos de desarrollo: `migrate:fresh`
     * la dejaría sin datos.
     *
     * Este hook (invocado por el trait justo antes de migrar) aborta si la base
     * de datos no parece de pruebas. La solución es `php artisan config:clear`
     * o, mejor, `composer test` (que ya lo hace).
     */
    protected function beforeRefreshingDatabase()
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! preg_match('/_test(ing)?$/', $database)) {
            throw new RuntimeException(sprintf(
                'Las pruebas apuntan a la base de datos "%s" y RefreshDatabase la vaciaría. '
                .'Ejecuta "php artisan config:clear" (o "composer test") para usar la base de datos '
                .'de pruebas (docker-compose.test.yml).',
                $database,
            ));
        }
    }
}
