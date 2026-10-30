# Desenvolvimento e testes

[README](../README.md) · Veja também: [Automação do repositório](AUTOMACAO.md) · [Guia de uso](USO.md)

## Ambiente

Você só precisa de Docker. O pacote usa a imagem `ghcr.io/elavora/php-dev:8.3-latest`, com PHP 8.3, Composer, Xdebug,
PHP-CS-Fixer e editorconfig-checker. PHPUnit e PHPStan vêm do `composer install`.

**Testes e PostgreSQL.** A suíte PHPUnit (`Elavora API Database PostgreSQL`, pasta `tests`) tem testes unitários, que montam o DSN
sem abrir conexão e não precisam de PostgreSQL nem de `pdo_pgsql`, e um teste que conecta de verdade
(`tests/PostgreSqlExtensionTest.php`, método `testConnectsUsingPostgreSqlExtension`, sem grupo próprio). Esse teste roda
`SELECT 1`; ele é pulado (`markTestSkipped`) quando a extensão `pdo_pgsql` não está carregada ou quando `POSTGRES_HOST` não
está definida (nessa ordem). Quando roda, usa `POSTGRES_HOST`, `POSTGRES_PORT` (padrão `5432`), banco `api`, usuário `api` e
senha `api`. O teste não assume um host fixo: sem `POSTGRES_HOST` ele não tenta conectar em nenhum host. Nesta máquina e
no CI ele fica pulado: a imagem `php-dev:8.3-latest` não traz `pdo_pgsql`, e nenhum serviço PostgreSQL é iniciado.

**`ext-pdo_pgsql` no `composer install`.** O `composer.json` exige `ext-pdo_pgsql`, que a imagem não tem. Por isso os
comandos `docker run` desta página usam `--ignore-platform-req=ext-pdo_pgsql`. Os testes unitários não dependem da
extensão.

**VS Code:** abra a pasta e rode **Dev Containers: Reopen in Container**. O `composer install` roda ao criar o
container, e o arquivo PHP é formatado ao salvar.

**Sem editor**, na pasta do repositório, no PowerShell (no Git Bash a conversão de caminhos quebra o `docker run`):

```powershell
docker run --rm --mount "type=bind,source=$($PWD.Path),target=/workspace" -w /workspace ghcr.io/elavora/php-dev:8.3-latest sh -lc 'git config --global --add safe.directory /workspace && composer install --no-interaction --prefer-dist --ignore-platform-req=ext-pdo_pgsql && composer check'
```

Esse comando cria `vendor/` e `composer.lock` na pasta (os dois estão no `.gitignore`) e pode atualizar o
`.editorconfig`.

## Scripts do Composer

| Comando | O que faz |
| --- | --- |
| `composer test` | testes PHPUnit |
| `composer lint` | checagem de sintaxe (`elavora-standards lint`) |
| `composer analyse` | PHPStan, nível 8 |
| `composer check` | `lint`, `test` e `analyse`, nessa ordem |

## Validar como o CI

O CI roda a suíte num clone limpo, com cobertura. O mount do Windows esconde erros de permissão e de symlink, e o
`composer check` sem cobertura não pega erro na configuração da cobertura. Para reproduzir o CI, no PowerShell, na
pasta do repositório (commite antes: o clone pega só o que está commitado):

```powershell
docker run --rm -v "$($PWD.Path):/origem:ro" -e XDEBUG_MODE=coverage ghcr.io/elavora/php-dev:8.3-latest sh -lc 'git config --global --add safe.directory "*" && git clone -q /origem /tmp/repo && cd /tmp/repo && composer validate --strict --no-check-publish && composer install --no-interaction --prefer-dist --ignore-platform-req=ext-pdo_pgsql && composer lint && composer analyse && composer test -- --coverage-clover=/tmp/clover.xml && git ls-files -z | xargs -0 editorconfig-checker'
```

Tudo precisa sair com código 0, e o aviso `No filter is configured` não pode aparecer. O comando não deixa nada na
sua pasta.

Para só formatar ou conferir o estilo, dentro do container: `php-cs-fixer fix` e `php-cs-fixer check`.

## Testes da automação

Conferem os workflows, o Dependabot, o devcontainer e os arquivos de estilo ([regras](AUTOMACAO.md#testes-da-automação)).
Usam Node.js e actionlint, também em container. No PowerShell:

```powershell
docker run --rm -v "$($PWD.Path):/w:ro" -w /w node:22-alpine node --test "tests/automation/*.test.cjs"
docker run --rm -v "$($PWD.Path):/w:ro" -w /w rhysd/actionlint:1.7.12 -no-color
```

## O que roda no CI

Em cada PR e em cada push na `main`:

| Workflow | O que faz |
| --- | --- |
| **Qualidade** (`quality / Qualidade PHP`) | formata (commita `style: organiza o código` na branch do PR), roda editorconfig, `composer audit`, `composer lint`, `composer analyse` e `composer test` com as dependências mínimas e com cobertura (avisa abaixo de 80%), e deixa um comentário fixo no PR |
| **CodeQL** | análise de segurança dos workflows |
| **Dependency Review** | aponta dependências novas com vulnerabilidade alta |
| **Automação** | testes da automação e actionlint |

- Em PR de fork, PR do Dependabot e push na `main`, a formatação não commita: só avisa.
- Depois que o CI commitar na sua branch, dê `git pull` antes do próximo push.
- **Nenhum check trava o merge.** Quem revisa olha o resultado e decide.
- Os testes PHPUnit rodam dentro do workflow **Qualidade**, na imagem do pipeline central.
- **Pendência conhecida (`ext-pdo_pgsql` no CI).** O workflow central não instala extensões PHP e não passa
  `--ignore-platform-req` ao `composer install`. Como este pacote exige `ext-pdo_pgsql` e a imagem do pipeline não a traz,
  as etapas que instalam as dependências podem falhar no CI com `ext-pdo_pgsql ... missing from your system`. Este
  repositório não contorna isso nem ignora o check vermelho: a correção é no `Elavora/.github` ou na imagem. Mesmo com a
  extensão instalada, o teste de integração continua pulado no CI, porque não há `POSTGRES_HOST` (o pipeline não define a variável nem sobe um PostgreSQL). Detalhes em
  [Automação do repositório](AUTOMACAO.md#pendência-conhecida-ext-pdo_pgsql-no-pipeline-central).

## Commits, PRs e versões

Commits em português do Brasil, no padrão Conventional Commits (`feat:`, `fix:`, `docs:`, `ci:`). Um assunto por commit
e por PR. A descrição do PR segue o template da organização.

As labels do PR definem a versão que sai no merge, e a label `release` publica a estável. Veja
[Versões e release](AUTOMACAO.md#versões-e-release).

---

[README](../README.md) · Veja também: [Automação do repositório](AUTOMACAO.md)
