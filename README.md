# Oficina Mecânica API

MVP back-end para sistema de gestão de oficina mecânica.
**Tech Challenge — SOAT Pós-Tech FIAP — Fase 1**

## Stack
- PHP 8.2 + Laravel 11
- PostgreSQL 15
- JWT Authentication (tymon/jwt-auth 2.3)
- Swagger (darkaonline/l5-swagger 11.0)

## Arquitetura

Monolito com 4 camadas DDD (Evans, 2003):

app/
├── Interface/          ← Controllers, Requests, Resources, Auth
├── Application/        ← Services, DTOs, Interfaces
├── Domain/             ← Entidades, Value Objects, Repositórios, Regras de negócio
│   ├── OrdemServico/
│   ├── Cliente/
│   ├── Veiculo/
│   └── Estoque/
└── Infrastructure/     ← Eloquent Models, Repositórios concretos, Providers

## Como rodar
```bash
git clone https://github.com/Ralima0711/oficina-mecanica-api.git
cd oficina-mecanica-api
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
docker-compose up -d
php artisan migrate
```

## Endpoints

Documentação disponível em `/api/documentation` após subir o projeto.

## Testes
```bash
php artisan test --coverage
```

Cobertura mínima exigida: **80%**

## Time

| Membro | Papel | GitHub |
|--------|-------|--------|
| Roberta Lima | Tech Lead + Infra/QA | @Ralima0711 |
| Gustavo Delfino | DDD/Docs | @GustavoDell |
| David Tavares | Dev - Ordens de Serviço | @dvdt101 |
| Johny | Dev - Gestão | — |

## Banco de dados

10 tabelas criadas via migrations:
`users`, `mecanicos`, `clientes`, `veiculos`, `ordens_servico`,
`pecas`, `insumos`, `itens_os`, `insumos_os`, `notificacoes`

Para rodar as migrations:
```bash
docker exec -it oficina_app php artisan migrate
```

Para resetar o banco:
```bash
docker exec -it oficina_app php artisan migrate:fresh