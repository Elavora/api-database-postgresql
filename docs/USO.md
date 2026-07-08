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
    'dsn' => getenv('DB_DSN'),
    'username' => getenv('DB_USER') ?: null,
    'password' => getenv('DB_PASSWORD') ?: null,
]));
```

## Principais pontos de entrada

- `Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension`

## Dependencias de runtime

- `ext-pdo_pgsql` `*`
- `elavora/api-database-pdo` `^0.1`
- `elavora/api-framework` `^0.3.1`

## Validacao no projeto consumidor

Depois de instalar o pacote, rode os testes da aplicacao consumidora. Para uma verificacao isolada do pacote, use container:

```bash
docker run --rm -v "${PWD}:/workspace" -w "/workspace/api-database-postgresql" composer:2 composer validate --strict --no-check-publish
docker run --rm -v "${PWD}:/workspace" -w "/workspace/api-database-postgresql" composer:2 sh -lc "find . \\( -path ./.git -o -path ./vendor \\) -prune -o -name '*.php' -print0 | xargs -0 -r -n1 php -l"
```

## Observacoes

- Mantenha regras de produto fora deste pacote.
- Prefira configurar extensoes no bootstrap da aplicacao.
- Instale apenas os modulos que a aplicacao realmente usa.