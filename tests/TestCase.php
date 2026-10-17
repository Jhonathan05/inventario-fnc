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
     * El blindaje NO puede vivir en `beforeRefreshingDatabase()`: al usar
     * `RefreshDatabase` en cada test, los métodos del trait se insertan en la
     * clase hija y sombrean cualquier override del TestCase base, dejándolo
     * como código muerto. `createApplication()` sí se respeta y se ejecuta
     * antes de que el trait migre la base de datos.
     *
     * La solución es `php artisan config:clear` o, mejor, `composer test`
     * (que ya lo hace).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");

        if (! preg_match('/_test(ing)?$/', $database)) {
            throw new RuntimeException(sprintf(
                'Las pruebas apuntan a la base de datos "%s" y RefreshDatabase la vaciaría. '
                .'Ejecuta "php artisan config:clear" (o "composer test") para usar la base de datos '
                .'de pruebas (docker-compose.test.yml).',
                $database,
            ));
        }

        return $app;
    }
}
