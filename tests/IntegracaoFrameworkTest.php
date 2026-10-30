<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabasePdo\PdoConnectionFactory;
use Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Bootstrap\EnvironmentExtensions;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;
use PHPUnit\Framework\TestCase;

final class IntegracaoFrameworkTest extends TestCase
{
    public function testExtendRegistraPdoConnectionFactory(): void
    {
        $application = Application::create()->extend(new PostgreSqlExtension(['host' => 'db', 'database' => 'api']));

        $factory = $application->container()->get(DatabaseConnectionFactory::class);

        self::assertInstanceOf(PdoConnectionFactory::class, $factory);
        self::assertSame($factory, $application->container()->get(DatabaseConnectionFactory::class));
    }

    public function testExtendComConfigVaziaLanca(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A configuracao PostgreSQL deve informar host e database.');

        Application::create()->extend(new PostgreSqlExtension([]));
    }

    public function testEnvironmentExtensionsComPadroes(): void
    {
        $extensions = EnvironmentExtensions::load('/tmp', ['DB_DRIVER' => 'postgresql']);

        self::assertCount(1, $extensions);
        $extension = array_values($extensions)[0];
        self::assertInstanceOf(PostgreSqlExtension::class, $extension);
        self::assertEquals([
            'host' => 'postgresql',
            'port' => 5432,
            'database' => 'app',
            'username' => 'app',
            'password' => '',
            'dsn' => 'pgsql:host=postgresql;port=5432;dbname=app',
        ], $extension->pdoConfig());
    }

    public function testEnvironmentExtensionsComVariaveis(): void
    {
        $extensions = EnvironmentExtensions::load('/tmp', [
            'DB_DRIVER' => 'postgresql',
            'DB_HOST' => 'db',
            'DB_PORT' => '6543',
            'DB_DATABASE' => 'loja',
            'DB_USERNAME' => 'u',
            'DB_PASSWORD' => 'p',
        ]);

        self::assertCount(1, $extensions);
        $extension = array_values($extensions)[0];
        self::assertInstanceOf(PostgreSqlExtension::class, $extension);

        $config = $extension->pdoConfig();
        self::assertSame('pgsql:host=db;port=6543;dbname=loja', $config['dsn']);
        self::assertSame('u', $config['username']);
        self::assertSame('p', $config['password']);
    }
}
