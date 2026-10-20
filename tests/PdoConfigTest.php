<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension;
use Elavora\Api\Framework\Contracts\Extension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PdoConfigTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function conexaoUnicaProvider(): iterable
    {
        yield 'host e database, porta padrao 5432' => [
            ['host' => 'db', 'database' => 'api'],
            ['host' => 'db', 'database' => 'api', 'dsn' => 'pgsql:host=db;port=5432;dbname=api'],
        ];
        yield 'porta int' => [
            ['host' => 'db', 'port' => 5433, 'database' => 'api'],
            ['host' => 'db', 'port' => 5433, 'database' => 'api', 'dsn' => 'pgsql:host=db;port=5433;dbname=api'],
        ];
        yield 'porta string numerica fica string no array' => [
            ['host' => 'db', 'port' => '5433', 'database' => 'api'],
            ['host' => 'db', 'port' => '5433', 'database' => 'api', 'dsn' => 'pgsql:host=db;port=5433;dbname=api'],
        ];
        yield 'porta null vira 5432 no dsn' => [
            ['host' => 'db', 'port' => null, 'database' => 'api'],
            ['host' => 'db', 'port' => null, 'database' => 'api', 'dsn' => 'pgsql:host=db;port=5432;dbname=api'],
        ];
        yield 'username, password e options repassados' => [
            ['host' => 'db', 'database' => 'api', 'username' => 'u', 'password' => 'p', 'options' => [PDO::ATTR_TIMEOUT => 3]],
            [
                'host' => 'db',
                'database' => 'api',
                'username' => 'u',
                'password' => 'p',
                'options' => [PDO::ATTR_TIMEOUT => 3],
                'dsn' => 'pgsql:host=db;port=5432;dbname=api',
            ],
        ];
        yield 'dsn recebido e sobrescrito' => [
            ['host' => 'db', 'database' => 'api', 'dsn' => 'sqlite::memory:'],
            ['host' => 'db', 'database' => 'api', 'dsn' => 'pgsql:host=db;port=5432;dbname=api'],
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $esperado
     */
    #[DataProvider('conexaoUnicaProvider')]
    public function testPdoConfigConexaoUnica(array $config, array $esperado): void
    {
        self::assertEquals($esperado, (new PostgreSqlExtension($config))->pdoConfig());
    }

    public function testPortaStringContinuaStringNoArray(): void
    {
        $resultado = (new PostgreSqlExtension(['host' => 'db', 'port' => '5433', 'database' => 'api']))->pdoConfig();

        self::assertSame('5433', $resultado['port']);
        self::assertSame('pgsql:host=db;port=5433;dbname=api', $resultado['dsn']);
    }

    public function testConnectionsNullTratadoComoConexaoUnica(): void
    {
        // A doc nao define se a chave 'connections' => null fica no array devolvido;
        // por isso o teste confere so o que a doc garante.
        $resultado = (new PostgreSqlExtension(['connections' => null, 'host' => 'db', 'database' => 'api']))->pdoConfig();

        self::assertSame('pgsql:host=db;port=5432;dbname=api', $resultado['dsn']);
        self::assertSame('db', $resultado['host']);
        self::assertSame('api', $resultado['database']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function variasConexoesProvider(): iterable
    {
        yield 'duas conexoes com porta padrao e porta propria' => [
            ['connections' => [
                'default' => ['host' => 'a', 'database' => 'x'],
                'r' => ['host' => 'b', 'port' => 6000, 'database' => 'y'],
            ]],
            ['connections' => [
                'default' => ['host' => 'a', 'database' => 'x', 'dsn' => 'pgsql:host=a;port=5432;dbname=x'],
                'r' => ['host' => 'b', 'port' => 6000, 'database' => 'y', 'dsn' => 'pgsql:host=b;port=6000;dbname=y'],
            ]],
        ];
        yield 'chaves da raiz ignoradas' => [
            ['host' => 'ignorado', 'connections' => ['default' => ['host' => 'a', 'database' => 'x']]],
            ['connections' => ['default' => ['host' => 'a', 'database' => 'x', 'dsn' => 'pgsql:host=a;port=5432;dbname=x']]],
        ];
        yield 'connections vazio' => [
            ['connections' => []],
            ['connections' => []],
        ];
        yield 'dsn sobrescrito em cada conexao e opcoes repassadas' => [
            ['connections' => ['default' => [
                'host' => 'a',
                'port' => '7000',
                'database' => 'x',
                'username' => 'u',
                'password' => 'p',
                'options' => [PDO::ATTR_TIMEOUT => 3],
                'dsn' => 'sqlite::memory:',
            ]]],
            ['connections' => ['default' => [
                'host' => 'a',
                'port' => '7000',
                'database' => 'x',
                'username' => 'u',
                'password' => 'p',
                'options' => [PDO::ATTR_TIMEOUT => 3],
                'dsn' => 'pgsql:host=a;port=7000;dbname=x',
            ]]],
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $esperado
     */
    #[DataProvider('variasConexoesProvider')]
    public function testPdoConfigVariasConexoes(array $config, array $esperado): void
    {
        self::assertEquals($esperado, (new PostgreSqlExtension($config))->pdoConfig());
    }

    public function testChavesDaRaizNaoAparecemComConnections(): void
    {
        $resultado = (new PostgreSqlExtension([
            'host' => 'ignorado',
            'database' => 'ignorado',
            'connections' => ['default' => ['host' => 'a', 'database' => 'x']],
        ]))->pdoConfig();

        self::assertSame(['connections'], array_keys($resultado));
    }

    public function testPdoConfigDevolveSempreOMesmoResultado(): void
    {
        $extension = new PostgreSqlExtension(['host' => 'db', 'port' => 5433, 'database' => 'api']);

        self::assertEquals($extension->pdoConfig(), $extension->pdoConfig());
    }

    public function testConstrutorNaoValidaNemLanca(): void
    {
        self::assertInstanceOf(PostgreSqlExtension::class, new PostgreSqlExtension([]));
    }

    public function testClasseFinalQueImplementaExtension(): void
    {
        self::assertInstanceOf(Extension::class, new PostgreSqlExtension(['host' => 'db', 'database' => 'api']));
        self::assertTrue((new ReflectionClass(PostgreSqlExtension::class))->isFinal());
    }
}
