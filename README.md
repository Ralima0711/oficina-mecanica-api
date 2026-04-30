# Oficina Mecânica API

MVP back-end para sistema de gestão de oficina mecânica.  
**Tech Challenge — SOAT Pós-Tech FIAP — Fase 1**

---

## Stack

| Tecnologia | Versão | Justificativa |
|---|---|---|
| PHP | 8.2 | Versão LTS com suporte a typed properties e enums |
| Laravel | 11 | Framework robusto com ORM Eloquent nativo |
| PostgreSQL | 15 | Banco relacional robusto com suporte nativo ao Eloquent ORM e melhor desempenho em consultas complexas em relação ao MySQL |
| JWT Authentication | tymon/jwt-auth 2.3 | Autenticação stateless para APIs RESTful |
| Swagger | darkaonline/l5-swagger 11.0 | Documentação interativa da API |
| Docker | — | Containerização do ambiente de desenvolvimento |

> **Por que PostgreSQL?** O time avaliou PostgreSQL 15 e MySQL 8 na segunda reunião de projeto. O PostgreSQL foi escolhido pela robustez em consultas complexas, suporte nativo a tipos avançados (JSONB, arrays), melhor conformidade com o padrão SQL e integração nativa com o Eloquent ORM do Laravel — sem necessidade de configurações adicionais.

---

## Arquitetura

Monolito com 4 camadas DDD (Evans, 2003):

```
app/
├── Interface/          ← Controllers, Requests, Resources, Swagger, Auth
├── Application/        ← Services, DTOs, Interfaces
├── Domain/             ← Entidades, Value Objects, Repositórios, Regras de negócio
│   ├── OrdemServico/
│   ├── Cliente/
│   ├── Veiculo/
│   ├── Peca/
│   ├── Insumo/
│   ├── Notificacao/
│   └── Estoque/
└── Infrastructure/     ← Eloquent Models, Repositórios concretos, Providers
```

### Decisões de design

- **Status da OS como Value Object (string):** o status da Ordem de Serviço foi implementado como Value Object tipado, não como tabela separada — decisão justificada pelos princípios DDD (Evans, 2003) e alinhada com o conteúdo da Aula 05 do curso.
- **Repositórios por Aggregate Root:** cada contexto delimitado possui sua própria interface de repositório no Domain e implementação concreta no Infrastructure.
- **JWT stateless:** autenticação sem sessão server-side, adequada para APIs RESTful.
- **Documentação Swagger separada das Controllers:** anotações OpenAPI organizadas em classes dedicadas em `app/Interface/Http/Swagger/`, mantendo as controllers limpas.

---

## Como rodar

### Pré-requisitos

- Docker e Docker Compose instalados

### Setup

```bash
git clone https://github.com/Ralima0711/oficina-mecanica-api.git
cd oficina-mecanica-api

cp .env.example .env

docker-compose up -d

docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### Verificar se está rodando

Acesse `http://localhost:8080/api/documentation` para visualizar os endpoints no Swagger UI.

**Credenciais padrão (admin):**
- Email: configurado no `.env` via `DEFAULT_ADMIN_EMAIL`
- Senha: configurada no `.env` via `DEFAULT_ADMIN_PASSWORD`

---

## Endpoints

Documentação interativa disponível em `http://localhost:8080/api/documentation` após subir o projeto.

### Módulos disponíveis

| Módulo | Descrição | Status |
|---|---|---|
| **Auth** | Login, logout, refresh e dados do usuário autenticado (JWT) | ✅ Disponível |
| **Clientes** | Cadastro e gerenciamento de clientes (pessoa física e jurídica — CPF/CNPJ) | ✅ Disponível |
| **Veículos** | Cadastro e vínculo de veículos aos clientes | ✅ Disponível |
| **Ordens de Serviço** | Criação, diagnóstico, orçamento, aprovação, execução, finalização e entrega | ✅ Disponível |
| **Peças** | Cadastro e controle de estoque de peças | ✅ Disponível |
| **Insumos** | Cadastro e controle de estoque de insumos | ✅ Disponível |
| **Estoque** | Gerenciamento de estoque | 🔄 Em desenvolvimento |
| **Usuários** | Gerenciamento de usuários do sistema | ✅ Disponível |
| **Notificações** | Notificações do usuário autenticado | ✅ Disponível |

---

## Ciclo de vida da Ordem de Serviço

```
Criada → Em Diagnóstico → Aguardando Aprovação → Aprovada → Em Execução → Finalizada → Entregue
                                                ↘ Recusada
```

---

## Banco de dados

10 tabelas criadas via migrations:

`users`, `mecanicos`, `clientes`, `veiculos`, `ordens_servico`,  
`pecas`, `insumos`, `itens_os`, `insumos_os`, `notificacoes`

Diagrama ER disponível em: https://dbdiagram.io/d/69d95a740f7c9ef2c0ccc1ba

### Comandos úteis

```bash
# Rodar migrations
docker-compose exec app php artisan migrate

# Resetar banco
docker-compose exec app php artisan migrate:fresh

# Resetar banco com seeds
docker-compose exec app php artisan migrate:fresh --seed

# Gerar documentação Swagger
docker-compose exec app php artisan l5-swagger:generate
```

---

## Testes

```bash
# Rodar todos os testes
docker-compose exec app php artisan test

# Rodar com relatório de cobertura
docker-compose exec app php artisan test --coverage-clover=coverage.xml
```

Cobertura mínima exigida: **80%**

---

## Análise de Vulnerabilidades

Foram realizadas duas análises complementares de segurança e qualidade de código.

### OWASP ZAP — Segurança em execução

Análise dinâmica realizada com **OWASP ZAP 2.17.0 by Checkmarx** em 23/04/2026.

| Nível de Risco | Quantidade | Descrição |
|---|---|---|
| 🔴 Alto | 0 | Nenhuma vulnerabilidade crítica encontrada |
| 🟡 Médio | 1 | CSP: ausência de diretiva de segurança de conteúdo |
| 🟢 Baixo | 0 | Nenhuma vulnerabilidade de risco baixo |
| ℹ️ Informativo | 3 | Alertas informativos (majoritariamente da interface do ZAP) |

### SonarQube — Qualidade e segurança estática

Análise estática realizada com **SonarQube Community Edition 26.4.0** em 24/04/2026.  
6.700 linhas de código analisadas em 120 arquivos.

| Métrica | Resultado | Nota | Status |
|---|---|---|---|
| Quality Gate | — | — | ✅ **PASSED** |
| Vulnerabilidades | 0 issues | **A** | ✅ Aprovado |
| Confiabilidade | 0 issues | **A** | ✅ Aprovado |
| Manutenibilidade | 120 code smells | **A** | ✅ Aprovado |
| Security Hotspots | 3 (falsos positivos) | E | ⚠️ Revisados |
| Cobertura de Testes | Pendente | — | 🔄 Em implementação |
| Duplicações | 6,3% | — | ✅ Aceitável |

Os 3 Security Hotspots identificados foram avaliados como falsos positivos no contexto de desenvolvimento local — detalhes no relatório completo.

Relatórios completos disponíveis na pasta `/docs`.

---

## Documentação DDD

| Artefato | Ferramenta | Status |
|---|---|---|
| Domain Storytelling | egon.io | ✅ Concluído |
| Event Storming | Miro | ✅ Concluído |
| Linguagem Ubíqua | Notion | ✅ Concluído |
| Diagrama de Agregados | Miro | ✅ Concluído |
| DER | dbdiagram.io | ✅ Concluído |

---

## Time

| Membro | Papel | GitHub |
|---|---|---|
| Roberta Lima | Tech Lead + Infra/QA | [@Ralima0711](https://github.com/Ralima0711) |
| Gustavo Delfino | DDD/Docs | [@GustavoDell](https://github.com/GustavoDell) |
| David Tavares | Dev — Ordens de Serviço + Notificações | [@dvdt101](https://github.com/dvdt101) |
| Johny | Dev — Gestão (clientes/veículos/peças/insumos/estoque) | [@Johnyol](https://github.com/Johnyol) |

---

## Estrutura de branches

| Branch | Finalidade |
|---|---|
| `main` | Código estável, protegida — merge apenas via PR |
| `develop` | Branch de trabalho do time |
| `feature/*` | Branches de funcionalidades individuais |
