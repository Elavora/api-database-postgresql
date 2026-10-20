# elavora/api-database-postgresql

Extensão PostgreSQL opcional para o framework Elavora, baseada em `elavora/api-database-pdo`.

Versão 1.0.0: exige `elavora/api-framework` `^2.0`, `elavora/api-database-pdo` `^1.0`, PHP `>=8.3`
e `ext-pdo_pgsql`.

Registre `PostgreSqlExtension` com `host`, `port`, `database`, `username`, `password` e `options`.
O pacote monta o DSN `pgsql:host=<host>;port=<port>;dbname=<database>`. A chave `connections`
habilita conexões nomeadas. Com `DB_DRIVER=postgresql`, o framework registra a extensão sozinho.

Especificação completa, testes e validação local: [docs/USO.md](docs/USO.md).
