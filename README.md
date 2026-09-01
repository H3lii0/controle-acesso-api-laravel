# Controle de Acesso - API Laravel

API backend do sistema de controle de acesso biometrico escolar.

## Responsabilidades

- Autenticacao e autorizacao
- Alunos e responsaveis
- Funcionarios e permissoes
- Estrutura escolar
- Terminais biometricos
- Eventos de entrada e saida
- Historico, alertas, notificacoes, relatorios e auditoria

## Stack

- Laravel
- PostgreSQL
- Redis
- PHP
- Laravel Sanctum

## Multi-tenant

A API ja nasce preparada para multi-tenant em nivel inicial:

- `tenants`: clientes/escolas do sistema;
- `tenant_user`: vinculo entre usuario e tenant;
- `users.current_tenant_id`: tenant ativo do usuario;
- `employees.tenant_id`: funcionario vinculado a um tenant.

Os seeders de sistema criam apenas permissoes e perfis genericos. Dados de desenvolvimento ficam separados no `DevelopmentTenantSeeder`.

## Setup local

1. Suba a infraestrutura pelo repositorio `controle-acesso-infra`:

```bash
docker compose up -d postgres redis mailpit
```

2. Instale dependencias:

```bash
composer install
```

3. Configure o ambiente:

```bash
copy .env.example .env
php artisan key:generate
```

4. Rode migrations e seeds:

```bash
php artisan migrate --seed
```

5. Suba a API:

```bash
php artisan serve
```

URL local padrao:

```txt
http://localhost:8000
```

## Endpoints iniciais

```txt
GET  /api/health
POST /api/auth/login
GET  /api/auth/me
POST /api/auth/logout
```

Usuario seed para desenvolvimento:

```txt
Tenant: Example School
E-mail: admin@example.test
Senha:  password
```

## Documentacao

A especificacao funcional e tecnica fica no repositorio `controle-acesso-docs`.
