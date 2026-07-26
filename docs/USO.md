# Guia de uso

Extensao opcional PostgreSQL baseada em `elavora/api-database-pdo`.

## Instalacao

```bash
composer require elavora/api-database-postgresql
```

## Quando usar

- Registrar conexoes de banco como extensao da aplicacao.
- Consumir contratos de banco pelo container do framework.
- Manter configuracao de DSN e credenciais fora da regra de negocio.

## Exemplo rapido

```php
use Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension;

$application->extend(new PostgreSqlExtension([
    'host' => getenv('DB_HOST') ?: 'postgres',
    'port' => (int) (getenv('DB_PORT') ?: 5432),
    'database' => getenv('DB_DATABASE') ?: 'api',
    'username' => getenv('DB_USERNAME') ?: null,
    'password' => getenv('DB_PASSWORD') ?: null,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ],
]));
```

As credenciais devem vir do ambiente da aplicacao e nao devem ser versionadas.
A extensao monta o DSN PostgreSQL a partir desses campos.

Campos obrigatorios:

- `host`: endereco do servidor PostgreSQL.
- `database`: nome do banco.

Campos opcionais:

- `port`: padrao `5432`.
- `username` e `password`: padrao `null`; obtenha-os do ambiente.
- `options`: padrao `[]`; recebe atributos PDO com chaves inteiras.

## Conexoes nomeadas e transacoes

```php
$application->extend(new PostgreSqlExtension([
    'connections' => [
        'default' => [
            'host' => getenv('DB_HOST') ?: 'postgres',
            'database' => getenv('DB_DATABASE') ?: 'api',
            'username' => getenv('DB_USERNAME') ?: null,
            'password' => getenv('DB_PASSWORD') ?: null,
        ],
        'analytics' => [
            'host' => getenv('ANALYTICS_DB_HOST') ?: 'postgres',
            'database' => getenv('ANALYTICS_DB_DATABASE') ?: 'analytics',
            'username' => getenv('ANALYTICS_DB_USERNAME') ?: null,
            'password' => getenv('ANALYTICS_DB_PASSWORD') ?: null,
        ],
    ],
]));
```

Ao usar `connections`, configure obrigatoriamente a chave `default`.
`PdoDatabase` e `TransactionManager` compartilham essa conexao. As demais
conexoes sao obtidas por `DatabaseConnectionFactory::connection('analytics')`
e mantem instancias e transacoes isoladas.

```php
use Elavora\Api\Extension\DatabasePdo\PdoDatabase;
use Elavora\Api\Framework\Contracts\TransactionManager;

$database = $application->container()->get(PdoDatabase::class);
$transactions = $application->container()->get(TransactionManager::class);

$transactions->begin();
try {
    $database->insert('users', ['name' => 'Ana'], 'id');
    $transactions->commit();
} catch (Throwable $exception) {
    $transactions->rollback();
    throw $exception;
}
```

## Principais pontos de entrada

- `Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension`

## Dependencias de runtime

- `ext-pdo_pgsql` `*`
- PHP `>=8.3`
- `elavora/api-database-pdo` `^1.0`
- `elavora/api-framework` `^1.0`

## Validacao no projeto consumidor

Depois de instalar o pacote, rode os testes da aplicacao consumidora. Para uma verificacao isolada do pacote, use container:

```bash
docker run --rm -v "${PWD}:/workspace" -w "/workspace/api-database-postgresql" composer:2 composer validate --strict --no-check-publish
docker run --rm -v "${PWD}:/workspace" -w "/workspace/api-database-postgresql" composer:2 composer lint
```

`composer lint` usa somente PHP e funciona em Linux, macOS e Windows. Para
`composer check`, use uma imagem PHP com Composer e `pdo_pgsql`; os testes de
integracao tambem precisam de um PostgreSQL acessivel pelas variaveis usadas no
exemplo. Dependencias podem ser instaladas dentro desse container.

## Observacoes

- Mantenha regras de produto fora deste pacote.
- Prefira configurar extensoes no bootstrap da aplicacao.
- Instale apenas os modulos que a aplicacao realmente usa.
