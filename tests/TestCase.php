<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->ensureSafeTestDatabase($app);

        return $app;
    }

    protected function ensureSafeTestDatabase(Application $app): void
    {
        if (($_SERVER['MASR_POSTGRES_TESTS'] ?? null) === '1' && $app['config']->get('database.default') !== 'pgsql') {
            throw new RuntimeException('The PostgreSQL suite must not silently fall back to another database engine.');
        }

        if ($app['config']->get('database.default') === 'pgsql') {
            $connection = $app['config']->get('database.connections.pgsql');
            if ($app->configurationIsCached()
                || ! $app->environment('testing')
                || ! empty($connection['url'])
                || $connection['host'] !== '127.0.0.1'
                || (string) $connection['port'] !== '54329'
                || $connection['database'] !== 'masr_security_test'
                || $connection['username'] !== 'masr_test') {
                throw new RuntimeException('PostgreSQL tests may only use masr_test@127.0.0.1:54329/masr_security_test, without a database URL or cached configuration.');
            }
        }
    }
}
