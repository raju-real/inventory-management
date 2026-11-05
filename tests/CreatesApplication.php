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

        // Stops before any migration if cached config has replaced the phpunit.xml settings.
        if ($app['config']->get('database.default') !== 'sqlite') {
            throw new \RuntimeException('Tests must use SQLite. Run "artisan config:clear" first: they would wipe the real database.');
        }

        return $app;
    }
}
