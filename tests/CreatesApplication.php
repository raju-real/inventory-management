<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // config/database.php may choose its connection without looking at DB_CONNECTION, so the
        // tests set their own in-memory database here, whatever the app's config chooses.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        // Stops before any migration if anything still points the tests at a real database.
        $connection = $app['db']->connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Tests must use an in-memory SQLite database: they would wipe the real database.');
        }

        return $app;
    }
}
