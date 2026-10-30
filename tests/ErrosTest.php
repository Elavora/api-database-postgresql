<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension;
use Elavora\Api\Framework\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ErrosTest extends TestCase
{
    private const HOST_E_DATABASE = 'A configuracao PostgreSQL deve informar host e database.';

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function configInvalidaProvider(): iterable
    {
        yield 'vazio' => [[], self::HOST_E_DATABASE];
        yield 'sem host' => [['database' => 'api'], self::HOST_E_DATABASE];
        yield 'sem database' => [['host' => 'db'], self::HOST_E_DATABASE];
        yield 'host vazio' => [['host' => '', 'database' => 'api'], self::HOST_E_DATABASE];
        yield 'database vazio' => [['host' => 'db', 'database' => ''], self::HOST_E_DATABASE];
        yield 'host nao string' => [['host' => 123, 'database' => 'api'], self::HOST_E_DATABASE];
        yield 'dsn sozinho nao basta' => [['dsn' => 'pgsql:host=db;dbname=api'], self::HOST_E_DATABASE];
        yield 'conexao nomeada nao array' => [
            ['connections' => ['r' => 'pgsql:host=db']],
            "Configuracao PostgreSQL 'r' deve ser um array.",
        ];
        yield 'conexao nomeada sem database' => [
            ['connections' => ['r' => ['host' => 'db']]],
            self::HOST_E_DATABASE,
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('configInvalidaProvider')]
    public function testPdoConfigLancaComMensagemExata(array $config, string $mensagem): void
    {
        $extension = new PostgreSqlExtension($config);

        try {
            $extension->pdoConfig();
            self::fail('Era esperada InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($mensagem, $exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('configInvalidaProvider')]
    public function testExtendLancaComMensagemExata(array $config, string $mensagem): void
    {
        $extension = new PostgreSqlExtension($config);

        try {
            Application::create()->extend($extension);
            self::fail('Era esperada InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($mensagem, $exception->getMessage());
        }
    }
}
