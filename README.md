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

## Escopo atual

O MVP atende uma unica escola. Contas de funcionarios recebem permissoes individuais e contas de responsaveis acessam somente os alunos vinculados. Nao existe estrutura multi-tenant nesta versao.

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
E-mail: admin@example.test
Senha:  password
```

## Documentacao

A especificacao funcional e tecnica fica no repositorio `controle-acesso-docs`.

