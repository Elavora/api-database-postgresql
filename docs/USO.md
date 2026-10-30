# Guia de uso

Extensão PostgreSQL opcional para o framework Elavora, baseada em `elavora/api-database-pdo`.

Este pacote monta o DSN `pgsql:` a partir de `host`, `port` e `database` e entrega a configuração
pronta ao `PdoConnectionFactory` do pacote pdo.

## Versão e compatibilidade

| Item | Valor |
| --- | --- |
| Versão deste pacote | **1.0.0** (sai do PR com label `upgrade`, que sobe a major a partir da `v0.2.0`) |
| `elavora/api-framework` exigido | `^2.0` |
| `elavora/api-database-pdo` exigido | `^1.0` |
| PHP exigido | `>=8.3` (o mesmo mínimo do framework 2.0) |
| Extensão exigida | `ext-pdo_pgsql` `*` |

O `composer.json` do pacote declara exatamente:

```json
"require": {
    "php": ">=8.3",
    "ext-pdo_pgsql": "*",
    "elavora/api-database-pdo": "^1.0",
    "elavora/api-framework": "^2.0"
}
```

e o alias da `main` é `"dev-main": "1.0-dev"` em `extra.branch-alias`.

A versão 1.0.0 não aceita mais o framework `0.x`, o pdo `0.x` nem PHP 8.1/8.2.

## Instalação

```bash
composer require elavora/api-database-postgresql:^1.0
```

## Uso com as variáveis `DB_*` do framework

O `EnvironmentExtensions` do framework 2.0 registra esta extensão sozinho quando `DB_DRIVER=postgresql`.
Ele lê as variáveis abaixo e passa um array com estas chaves ao construtor de `PostgreSqlExtension`:

| Variável | Chave | Padrão quando ausente ou vazia |
| --- | --- | --- |
| `DB_HOST` | `host` | `'postgresql'` |
| `DB_PORT` | `port` (int) | `5432` |
| `DB_DATABASE` | `database` | `'app'` |
| `DB_USERNAME` | `username` | `'app'` |
| `DB_PASSWORD` | `password` | `''` (só quando ausente; vazia continua `''`) |

Este pacote não lê variáveis de ambiente. Ele só recebe o array.

## Exemplo rápido

```php
use Elavora\Api\Extension\DatabasePostgreSql\PostgreSqlExtension;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;

$application = Application::create()->extend(new PostgreSqlExtension([
    'host' => 'db',
    'port' => 5432,
    'database' => 'api',
    'username' => 'api',
    'password' => 'segredo',
]));

$pdo = $application->container()->get(DatabaseConnectionFactory::class)->connection();
```

Para várias conexões, use a chave `connections`:

```php
new PostgreSqlExtension([
    'connections' => [
        'default' => ['host' => 'db', 'database' => 'api', 'username' => 'api', 'password' => 'x'],
        'relatorios' => ['host' => 'replica', 'port' => 5433, 'database' => 'api'],
    ],
]);
```

## API pública

Namespace: `Elavora\Api\Extension\DatabasePostgreSql`.

### `final class PostgreSqlExtension implements Extension`

`Extension` é `Elavora\Api\Framework\Contracts\Extension` (framework 2.0).

```php
public function __construct(array $config)
public function pdoConfig(): array
public function register(Application $application): void
```

- O construtor só guarda o array. Não valida nada e não lança exceção.
- `pdoConfig()` valida a configuração e devolve o array que vai para o `PdoConnectionFactory`.
  Pode ser chamado várias vezes e devolve sempre o mesmo resultado.
- `register()` chama `pdoConfig()` e registra no container da aplicação, com `bind`,
  `DatabaseConnectionFactory::class` (`Elavora\Api\Framework\Contracts\DatabaseConnectionFactory`)
  apontando para um `new PdoConnectionFactory(pdoConfig())`
  (`Elavora\Api\Extension\DatabasePdo\PdoConnectionFactory`).
- `register()` não abre conexão. Mas a validação acontece nele: configuração inválida lança a
  exceção já em `$application->extend(...)`.
- Esta extensão registra só `DatabaseConnectionFactory::class`. Ela não registra `PdoDatabase`
  nem `TransactionManager`.

### Formatos de configuração

1. **Conexão única:** o array não tem a chave `connections` (ou ela vale `null`).
2. **Várias conexões:** o array tem a chave `connections` com um mapa `nome => array de conexão`.
   As outras chaves da raiz são ignoradas.

Chaves de uma conexão:

| Chave | Obrigatória | Regra |
| --- | --- | --- |
| `host` | sim | string não vazia |
| `database` | sim | string não vazia |
| `port` | não | int ou string numérica; ausente ou `null` vira `5432`; o valor é convertido com `(int)` |
| `username`, `password`, `options` | não | repassadas sem mudança ao pdo |
| `dsn` | não | sempre sobrescrita pelo DSN montado |

### Montagem do DSN

Formato: `pgsql:host=<host>;port=<port>;dbname=<database>`.

O array devolvido para cada conexão é o array de entrada com a chave `dsn` adicionada
(ou trocada). Todas as outras chaves continuam iguais, inclusive `host`, `port` e `database`
(o `port` original não é convertido no array, só dentro do DSN).
A ordem das chaves não faz parte do contrato: compare com `assertEquals`, não com `assertSame`.

Exemplos de conexão única (`(new PostgreSqlExtension($config))->pdoConfig()`):

| `$config` | Resultado |
| --- | --- |
| `['host' => 'db', 'database' => 'api']` | `['host' => 'db', 'database' => 'api', 'dsn' => 'pgsql:host=db;port=5432;dbname=api']` |
| `['host' => 'db', 'port' => 5433, 'database' => 'api']` | `dsn` = `'pgsql:host=db;port=5433;dbname=api'` |
| `['host' => 'db', 'port' => '5433', 'database' => 'api']` | `dsn` = `'pgsql:host=db;port=5433;dbname=api'` e `port` continua `'5433'` |
| `['host' => 'db', 'port' => null, 'database' => 'api']` | `dsn` = `'pgsql:host=db;port=5432;dbname=api'` |
| `['host' => 'db', 'database' => 'api', 'username' => 'u', 'password' => 'p', 'options' => [PDO::ATTR_TIMEOUT => 3]]` | as cinco chaves iguais, mais `dsn` = `'pgsql:host=db;port=5432;dbname=api'` |
| `['host' => 'db', 'database' => 'api', 'dsn' => 'sqlite::memory:']` | `dsn` = `'pgsql:host=db;port=5432;dbname=api'` (o `dsn` recebido é descartado) |
| `['connections' => null, 'host' => 'db', 'database' => 'api']` | tratado como conexão única |

Exemplos de várias conexões:

| `$config` | Resultado |
| --- | --- |
| `['connections' => ['default' => ['host' => 'a', 'database' => 'x'], 'r' => ['host' => 'b', 'port' => 6000, 'database' => 'y']]]` | `['connections' => ['default' => ['host' => 'a', 'database' => 'x', 'dsn' => 'pgsql:host=a;port=5432;dbname=x'], 'r' => ['host' => 'b', 'port' => 6000, 'database' => 'y', 'dsn' => 'pgsql:host=b;port=6000;dbname=y']]]` |
| `['host' => 'ignorado', 'connections' => ['default' => ['host' => 'a', 'database' => 'x']]]` | `['connections' => ['default' => [..., 'dsn' => 'pgsql:host=a;port=5432;dbname=x']]]` (sem `host` na raiz) |
| `['connections' => []]` | `['connections' => []]` |

### Erros

Todos são `InvalidArgumentException`, lançados por `pdoConfig()` e, portanto, também por
`register()` / `Application::extend()`.

| `$config` | Mensagem |
| --- | --- |
| `[]` | `A configuracao PostgreSQL deve informar host e database.` |
| `['database' => 'api']` | `A configuracao PostgreSQL deve informar host e database.` |
| `['host' => 'db']` | `A configuracao PostgreSQL deve informar host e database.` |
| `['host' => '', 'database' => 'api']` | `A configuracao PostgreSQL deve informar host e database.` |
| `['host' => 'db', 'database' => '']` | `A configuracao PostgreSQL deve informar host e database.` |
| `['host' => 123, 'database' => 'api']` | `A configuracao PostgreSQL deve informar host e database.` |
| `['dsn' => 'pgsql:host=db;dbname=api']` | `A configuracao PostgreSQL deve informar host e database.` (`dsn` sozinho não basta) |
| `['connections' => ['r' => 'pgsql:host=db']]` | `Configuracao PostgreSQL 'r' deve ser um array.` |
| `['connections' => ['r' => ['host' => 'db']]]` | `A configuracao PostgreSQL deve informar host e database.` |

Fora do contrato (não teste): `connections` que não é array nem `null`, e `port` não numérico
(ex.: `'abc'` vira `0` no DSN).

### Integração com o framework

Exemplos testáveis sem banco:

1. `Application::create()->extend(new PostgreSqlExtension(['host' => 'db', 'database' => 'api']))`
   não lança nada, e `container()->get(DatabaseConnectionFactory::class)` é uma instância de
   `PdoConnectionFactory`. Duas chamadas de `get` devolvem o mesmo objeto.
2. `Application::create()->extend(new PostgreSqlExtension([]))` lança
   `InvalidArgumentException` com `A configuracao PostgreSQL deve informar host e database.`
3. `EnvironmentExtensions::load('/tmp', ['DB_DRIVER' => 'postgresql'])`
   (`Elavora\Api\Framework\Bootstrap\EnvironmentExtensions`) devolve uma lista com uma
   `PostgreSqlExtension`, e o `pdoConfig()` dela é
   `['host' => 'postgresql', 'port' => 5432, 'database' => 'app', 'username' => 'app', 'password' => '', 'dsn' => 'pgsql:host=postgresql;port=5432;dbname=app']`.
4. `EnvironmentExtensions::load('/tmp', ['DB_DRIVER' => 'postgresql', 'DB_HOST' => 'db', 'DB_PORT' => '6543', 'DB_DATABASE' => 'loja', 'DB_USERNAME' => 'u', 'DB_PASSWORD' => 'p'])`
   devolve uma `PostgreSqlExtension` cujo `pdoConfig()` tem `dsn` = `'pgsql:host=db;port=6543;dbname=loja'`,
   `username` = `'u'` e `password` = `'p'`.

Abrir a conexão (`connection()`) precisa de `ext-pdo_pgsql` e de um PostgreSQL real.
Isso fica fora dos testes unitários (veja abaixo).

## Desenvolvimento e testes

### Testes unitários

Os testes unitários não abrem conexão. Eles cobrem `pdoConfig()`, os erros, o `register()` e o
`EnvironmentExtensions`. A imagem `ghcr.io/elavora/php-dev:8.3-latest` não tem `pdo_pgsql`, então o
`composer install` usa `--ignore-platform-req=ext-pdo_pgsql`.

### Teste de integração

O teste que faz `SELECT 1` num PostgreSQL real continua em `tests/`, mas chama `markTestSkipped`
quando `extension_loaded('pdo_pgsql')` é falso **ou** a variável `POSTGRES_HOST` não está definida.
Com as duas presentes, ele conecta em `POSTGRES_HOST`, porta `POSTGRES_PORT` (padrão `5432`),
banco, usuário e senha `api`.

### Comando padrão (depois do pdo 1.0.0 no Packagist)

Pelo PowerShell, na pasta do repo:

```powershell
docker run --rm -v "${PWD}:/origem:ro" ghcr.io/elavora/php-dev:8.3-latest sh -c "cp -r /origem /tmp/w && cd /tmp/w && composer install --no-interaction --ignore-platform-req=ext-pdo_pgsql && composer validate --strict --no-check-publish && vendor/bin/phpunit"
```

### Validação local antes do pdo 1.0.0 sair

Enquanto `elavora/api-database-pdo` 1.0.0 não estiver no Packagist, o comando padrão falha
(`^1.0` não existe). Nesse meio-tempo, use o clone local do pdo como repositório `path`.
O repositório é adicionado só na cópia `/tmp/w` dentro do container, então o `composer.json`
do repo não muda e nada disso é commitado.

Pré-requisito: o clone `D:\Felipe\Dev\Elavora\api-database-pdo` está na branch `feat/framework-2`
com o `composer.json` já pedindo `elavora/api-framework` `^2.0`.

```powershell
$script = @'
set -e
cp -r /origem /tmp/w && cd /tmp/w
composer config repositories.pdo '{"type":"path","url":"/pdo","options":{"symlink":false,"versions":{"elavora/api-database-pdo":"1.0.0"}}}'
composer install --no-interaction --ignore-platform-req=ext-pdo_pgsql
vendor/bin/phpunit
'@
docker run --rm -v "${PWD}:/origem:ro" -v "D:\Felipe\Dev\Elavora\api-database-pdo:/pdo:ro" ghcr.io/elavora/php-dev:8.3-latest sh -c $script
```

A opção `versions` faz o Composer tratar o clone como `1.0.0`, o que satisfaz `^1.0`.
O `composer validate` fica de fora aqui porque o `composer.json` da cópia foi alterado.

### Ordem de merge

1. PR do `api-database-pdo` (label `upgrade`) é mergeado e a `v1.0.0` sai no Packagist.
2. Só então o PR deste pacote é mergeado. Antes do merge, rode de novo o comando padrão
   (sem o repositório `path`) para confirmar que o pdo 1.0.0 resolve pelo Packagist.

## Observações

- Mantenha regras de produto fora deste pacote.
- Prefira configurar extensões no bootstrap da aplicação.
- Instale apenas os módulos que a aplicação realmente usa.
