<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;
use PHPUnit\Framework\TestCase;

final class PostgreSqlExtensionTest extends TestCase
{
    public function testConnectsUsingPostgreSqlExtension(): void
    {
        if (!extension_loaded('pdo_pgsql')) {
            self::markTestSkipped('ext-pdo_pgsql nao esta carregada.');
        }

        $host = getenv('POSTGRES_HOST');
        if ($host === false) {
            self::markTestSkipped('POSTGRES_HOST nao esta definida.');
        }

        $application = Application::create()->extend(new PostgreSqlExtension([
            'host' => $host,
            'port' => (int) (getenv('POSTGRES_PORT') ?: 5432),
            'database' => 'api',
            'username' => 'api',
            'password' => 'api',
        ]));
        $factory = $application->container()->get(DatabaseConnectionFactory::class);

        self::assertSame(1, (int) $factory->connection()->query('SELECT 1')->fetchColumn());
    }
}
