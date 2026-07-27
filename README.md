# elavora/api-database-postgresql

Extensao opcional PostgreSQL baseada em `elavora/api-database-pdo`.

Registre `PostgreSqlExtension` com `host`, `port`, `database`, `username`,
`password` e `options`. A chave `connections` habilita conexoes nomeadas.

O container recebe `PdoDatabase` e `TransactionManager` sobre a conexao
`default`. O pacote requer PHP 8.3 e dependencias Elavora 1.x.
