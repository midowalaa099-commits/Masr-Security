<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class PostgreSqlTestSafetyTest extends TestCase
{
    #[TestWith(['url', 'postgresql://example.invalid/production'])]
    #[TestWith(['host', 'example.invalid'])]
    #[TestWith(['port', '5432'])]
    #[TestWith(['database', 'production'])]
    #[TestWith(['username', 'production'])]
    public function test_postgresql_tests_reject_non_disposable_connections(string $key, string $value): void
    {
        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql', [
            'url' => null,
            'host' => '127.0.0.1',
            'port' => '54329',
            'database' => 'masr_security_test',
            'username' => 'masr_test',
        ]);
        config()->set("database.connections.pgsql.$key", $value);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PostgreSQL tests may only use masr_test@127.0.0.1:54329/masr_security_test');

        $this->ensureSafeTestDatabase($this->app);
    }
}
