# Oficina Mecânica API

Sistema de gestão de oficina mecânica — back-end RESTful desenvolvido como  
**Tech Challenge · SOAT Pós-Tech FIAP · Fase 2**

---

## Objetivos da Fase 2

A Fase 2 evoluiu o MVP da Fase 1 para um sistema **pronto para produção**, adicionando:

- **Clean Architecture** com separação explícita em 4 camadas (Domain, Application, Infrastructure, Interface)
- **Containerização e orquestração** com Docker multi-stage e Kubernetes (EKS na AWS)
- **Infraestrutura como código** com Terraform (EKS + RDS PostgreSQL na AWS)
- **Pipeline CI/CD** automatizada com GitHub Actions (testes → build → deploy)
- **Escalabilidade automática** com HPA (Horizontal Pod Autoscaler)
- **Documentação completa** da API com Swagger e collection Postman
- **Cobertura de testes** acima de 80% nos domínios críticos (261 testes automatizados)

---

## Stack

| Tecnologia | Versão | Papel |
|---|---|---|
| PHP | 8.2 | Runtime da aplicação |
| Laravel | 11 | Framework web |
| PostgreSQL | 15 | Banco de dados relacional |
| JWT | tymon/jwt-auth 2.3 | Autenticação stateless |
| Swagger | darkaonline/l5-swagger 11.0 | Documentação interativa da API |
| Docker | — | Containerização multi-stage (nginx + php-fpm) |
| Kubernetes | — | Orquestração — EKS na AWS |
| Terraform | ≥ 1.3.0 | Provisionamento da infraestrutura AWS |
| GitHub Actions | — | Pipeline CI/CD |

> **Por que PostgreSQL?** Avaliamos PostgreSQL 15 e MySQL 8 na segunda reunião de projeto. O PostgreSQL foi escolhido pela robustez em consultas complexas, suporte a tipos avançados (JSONB, arrays), melhor conformidade com o padrão SQL e integração nativa com o Eloquent ORM — sem configurações adicionais.

---

## Arquitetura

### Camadas da aplicação

Clean Architecture com 4 camadas seguindo os princípios de DDD (Evans, 2003):

```
app/
├── Domain/             ← Entidades, Value Objects, interfaces de repositório, regras de negócio
│   ├── OrdemServico/   ← Aggregate root principal (StatusOrdem como Value Object)
│   ├── Cliente/        ← CPF/CNPJ como Value Objects tipados
│   ├── Veiculo/        ← Placa como Value Object
│   ├── Peca/
│   ├── Insumo/
│   ├── Notificacao/
│   └── Estoque/        ← Serviço de domínio com eventos de alerta
├── Application/        ← Services, DTOs, orquestração de casos de uso
├── Infrastructure/     ← Eloquent Models, repositórios concretos, Providers, e-mail
└── Interface/          ← Controllers, Requests, Resources, Swagger, Middleware
```

### Decisões de design

- **Status da OS como Value Object:** o status da Ordem de Serviço é um Value Object tipado (`StatusOrdem`), não uma tabela separada — decisão alinhada com DDD (Evans, 2003) e com o conteúdo da Aula 05 do curso.
- **Repository Pattern:** cada Aggregate Root possui interface de repositório no Domain e implementação concreta no Infrastructure, desacoplando a aplicação do ORM.
- **Injeção de dependência via AppServiceProvider:** os repositórios são vinculados às suas interfaces no `AppServiceProvider`, sem acoplar as camadas superiores ao Eloquent.
- **Swagger desacoplado dos Controllers:** anotações OpenAPI em classes dedicadas em `app/Interface/Http/Swagger/`, mantendo os controllers limpos.
- **JWT stateless:** autenticação sem sessão server-side, adequada para APIs RESTful e escalabilidade horizontal.

### Infraestrutura e fluxo de deploy

```
┌─────────────────────────────────────────────────────────────┐
│                        GitHub                               │
│  push → GitHub Actions CI/CD                                │
│          ├── Job 1: Build & Tests (PHPUnit)                 │
│          ├── Job 2: Build Docker Image → Docker Hub         │
│          └── Job 3: Deploy → kubectl apply (EKS)            │
└────────────────────────────┬────────────────────────────────┘
                             │
                    ┌────────▼────────┐
                    │   AWS EKS       │
                    │                 │
                    │  ┌───────────┐  │
                    │  │ API Pod 1 │  │  ← HPA: mín 2 / máx 10 pods
                    │  │ API Pod 2 │  │     CPU > 70% ou Mem > 80%
                    │  └─────┬─────┘  │
                    │        │        │
                    │  ┌─────▼─────┐  │
                    │  │ Postgres  │  │  ← emptyDir (cluster local)
                    │  │   Pod     │  │     RDS via Terraform (AWS)
                    │  └───────────┘  │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │   AWS RDS       │
                    │ PostgreSQL 15   │  ← Provisionado via Terraform (/infra)
                    │  db.t3.micro    │
                    └─────────────────┘
```

**Manifestos Kubernetes (`k8s/`):**

| Manifesto | Descrição |
|---|---|
| `api-deployment.yaml` | Deployment da API (imagem: `dtavares99/oficina-mecanica-api`) |
| `api-service.yaml` | Service LoadBalancer na porta 80 |
| `postgres-deployment.yaml` | Deployment do PostgreSQL com volume emptyDir |
| `postgres-service.yaml` | Service ClusterIP para o banco |
| `hpa.yaml` | HPA — mín 2 / máx 10 réplicas, CPU 70% / memória 80% |
| `configmap.yaml` | Variáveis de ambiente não-sensíveis |
| `secret.yaml` | Credenciais (não versionado — ver `secret.example.yaml`) |

---

## Como rodar localmente

### Pré-requisitos

- Docker e Docker Compose

### Setup

```bash
git clone https://github.com/Ralima0711/oficina-mecanica-api.git
cd oficina-mecanica-api

cp .env.example .env

docker compose up -d

docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

### Verificar

Acesse `http://localhost:8080/api/documentation` para o Swagger UI.

**Credenciais padrão (admin):**
- Email: `admin@oficina.local`
- Senha: `admin123`

---

## Como fazer deploy no Kubernetes

### Pré-requisitos

- `kubectl` configurado com acesso ao cluster
- Imagem disponível no Docker Hub (`dtavares99/oficina-mecanica-api`)

### Deploy

1. Copie e preencha o arquivo de secrets com os valores codificados em **base64**:

```bash
cp k8s/secret.example.yaml k8s/secret.yaml
# edite k8s/secret.yaml com APP_KEY, DB_PASSWORD, JWT_SECRET e MAIL_PASSWORD
```

> Alternativamente, crie o secret via CLI para não manter o arquivo localmente:
> ```bash
> kubectl create secret generic oficina-api-secret \
>   --from-literal=APP_KEY=<valor> \
>   --from-literal=DB_PASSWORD=<valor> \
>   --from-literal=JWT_SECRET=<valor> \
>   --from-literal=MAIL_PASSWORD=<valor>
> ```

2. Aplique todos os manifestos:

```bash
kubectl apply -f k8s/
```

3. Acompanhe a inicialização (a API executa as migrations automaticamente):

```bash
kubectl get pods -w
```

4. Para acessar localmente via Minikube:

```bash
minikube service oficina-api-service
```

O Swagger estará disponível em `/api/documentation`.

### Remover o deploy

```bash
kubectl delete -f k8s/
```

### Escalabilidade automática

O HPA está configurado para escalar automaticamente com base em CPU e memória:

```bash
kubectl get hpa     # ver estado atual
kubectl top pods    # ver consumo de CPU/memória
```

| Métrica | Threshold | Mínimo | Máximo |
|---|---|---|---|
| CPU | 70% | 2 pods | 10 pods |
| Memória | 80% | 2 pods | 10 pods |

---

## Como provisionar a infraestrutura com Terraform

Os scripts Terraform estão em `/infra` e provisionam o ambiente completo na AWS:

| Recurso | Descrição |
|---|---|
| `aws_eks_cluster` | Cluster Kubernetes gerenciado (EKS) |
| `aws_eks_node_group` | Node group com 1–4 instâncias `t3.medium` |
| `aws_db_instance` | PostgreSQL 15 no RDS (`db.t3.micro`, 20 GB) |
| `aws_security_group` | Security group do RDS (porta 5432 restrita à VPC) |

Consulte [`infra/README.md`](infra/README.md) para instruções completas.

```bash
cd infra
cp terraform.tfvars.example terraform.tfvars
# edite terraform.tfvars com region, cluster_name, lab_role_arn, subnet_ids, db_password

terraform init
terraform plan -out=tfplan
terraform apply tfplan
```

> **AWS Academy:** copie o ARN da role `LabRole` e os IDs das subnets da VPC do laboratório. Nunca faça commit de `terraform.tfvars`.

---

## Pipeline CI/CD

O pipeline roda automaticamente a cada push em `develop` ou `main`:

```
push
  └── Job 1: Build & Tests
        ├── PHP 8.2 + extensões
        ├── PostgreSQL 15 (service container)
        └── PHPUnit — 261 testes (falha bloqueia os próximos jobs)
  └── Job 2: Build Docker Image
        ├── Docker multi-stage (nginx + php-fpm)
        └── Push para Docker Hub (dtavares99/oficina-mecanica-api)
  └── Job 3: Deploy to K8s
        ├── Configura KUBECONFIG (AWS EKS)
        └── kubectl apply -f k8s/
```

---

## Collection de APIs

| Ferramenta | Acesso |
|---|---|
| **Swagger UI** | `http://localhost:8080/api/documentation` (após subir o projeto) |
| **Postman** | [`docs/postman/oficina-mecanica-collection.json`](docs/postman/oficina-mecanica-collection.json) |

A collection Postman cobre 28 endpoints organizados em 6 pastas (Auth, Clientes, Veículos, Ordem de Serviço, Métricas, Notificações) com variáveis `{{baseUrl}}` e `{{token}}` e script de auto-populate do token no login.

---

## Como autenticar

### Usuários disponíveis após o seed

**Admin**

| Email | Senha |
|---|---|
| `admin@oficina.local` | `admin123` |

**Mecânicos**

| Nome | Email | Senha | Especialidade |
|---|---|---|---|
| João Silva | `joao.silva@oficina.local` | `mecanico123` | Motor |
| Carlos Santos | `carlos.santos@oficina.local` | `mecanico123` | Suspensão e Freios |
| Pedro Oliveira | `pedro.oliveira@oficina.local` | `mecanico123` | Elétrica |

**Atendentes**

| Nome | Email | Senha |
|---|---|---|
| Maria Costa | `maria.costa@oficina.local` | `atendente123` |
| Ana Ferreira | `ana.ferreira@oficina.local` | `atendente123` |
| Juliana Martins | `juliana.martins@oficina.local` | `atendente123` |

### Fluxo de autenticação

```bash
# 1. Login
POST /api/auth/login
{ "email": "joao.silva@oficina.local", "password": "mecanico123" }

# Resposta
{ "access_token": "eyJ...", "token_type": "bearer", "expires_in": 3600 }

# 2. Usar o token nas requisições subsequentes
Authorization: Bearer eyJ...

# 3. Endpoints de suporte
GET  /api/auth/me       ← dados do usuário autenticado
POST /api/auth/refresh  ← renovar token
POST /api/auth/logout   ← logout
```

---

## Endpoints

Documentação interativa completa em `http://localhost:8080/api/documentation`.

| Módulo | Descrição |
|---|---|
| **Auth** | Login, logout, refresh, dados do usuário |
| **Clientes** | CRUD de clientes (CPF/CNPJ como Value Objects) |
| **Veículos** | CRUD de veículos vinculados a clientes |
| **Ordens de Serviço** | Ciclo completo — abertura, diagnóstico, orçamento, aprovação, execução, entrega |
| **Peças / Insumos / Estoque** | Cadastro e controle de estoque com alertas de mínimo |
| **Usuários** | Gerenciamento de usuários do sistema |
| **Notificações** | Notificações do usuário autenticado |
| **Métricas** | Tempo médio de execução das OS (admin) |
| **Público** | Consulta de OS pelo cliente sem autenticação |

### Ciclo de vida da Ordem de Serviço

```
RECEBIDA → EM_DIAGNOSTICO → AGUARDANDO_APROVACAO → APROVADA → EM_EXECUCAO → FINALIZADA → ENTREGUE
                                                  ↘ FINALIZADA (orçamento recusado)
```

---

## Testes

```bash
# Rodar todos os testes
docker compose exec app php artisan test

# Com relatório de cobertura (requer Xdebug — já incluído no Dockerfile)
docker compose exec app bash -c "XDEBUG_MODE=coverage php artisan test --coverage"
```

**261 testes** — 246 unitários + 15 de integração.

| Camada | Cobertura |
|---|---|
| Domain/OrdemServico | 84–85% |
| Domain/Estoque | 87–100% |
| Domain/Cliente, Veiculo, Peca, Insumo | 100% |
| Application/Services | 100% |

> Cobertura acima de 80% nos domínios críticos ✅

---

## Análise de vulnerabilidades

### OWASP ZAP — Análise dinâmica

Realizada com **OWASP ZAP 2.17.0** em 23/04/2026.

| Risco | Quantidade |
|---|---|
| 🔴 Alto | 0 |
| 🟡 Médio | 1 (ausência de CSP) |
| 🟢 Baixo | 0 |

### SonarQube — Análise estática

Realizada com **SonarQube Community Edition 26.4.0** em 24/04/2026.  
6.700 linhas de código · 120 arquivos.

| Métrica | Resultado | Nota |
|---|---|---|
| Quality Gate | PASSED | ✅ |
| Vulnerabilidades | 0 | A |
| Confiabilidade | 0 bugs | A |
| Manutenibilidade | 120 code smells | A |
| Duplicações | 6,3% | ✅ |

Relatórios completos em `/docs`.

---

## Banco de dados

10 tabelas criadas via migrations:

`users`, `mecanicos`, `clientes`, `veiculos`, `ordens_servico`, `pecas`, `insumos`, `itens_os`, `insumos_os`, `notificacoes`

Diagrama ER: https://dbdiagram.io/d/69d95a740f7c9ef2c0ccc1ba

```bash
docker compose exec app php artisan migrate               # rodar migrations
docker compose exec app php artisan migrate:fresh         # resetar banco
docker compose exec app php artisan migrate:fresh --seed  # resetar + seeds
docker compose exec app php artisan l5-swagger:generate   # regenerar Swagger
```

---

## Documentação DDD

| Artefato | Ferramenta | Status |
|---|---|---|
| Domain Storytelling | egon.io | ✅ |
| Event Storming | Miro | ✅ |
| Linguagem Ubíqua | Notion | ✅ |
| Diagrama de Agregados | Miro | ✅ |
| DER | dbdiagram.io | ✅ |

---

## Vídeo demonstrativo

> 🎬 Link do vídeo: **[a publicar]**

O vídeo demonstra o ambiente em execução, cobrindo:
1. Visão geral da arquitetura
2. Deploy com `kubectl apply` — pods em execução
3. Pipeline CI/CD passando no GitHub Actions
4. Consumo das APIs — fluxo completo de Ordem de Serviço
5. Escalabilidade automática — HPA escalando pods sob carga

---

## Estrutura de branches

| Branch | Finalidade |
|---|---|
| `main` | Código estável, protegida — merge apenas via PR |
| `develop` | Branch de trabalho do time |
| `feature/*` | Branches de funcionalidades individuais |

---

## Time

| Membro | Papel | GitHub |
|---|---|---|
| Roberta Lima | Tech Lead + Arquitetura + QA | [@Ralima0711](https://github.com/Ralima0711) |
| Gustavo Delfino | DDD + Documentação | [@GustavoDell](https://github.com/GustavoDell) |
| David Tavares | Infra + CI/CD + Ordens de Serviço | [@dvdt101](https://github.com/dvdt101) |
| Johny | APIs + Swagger + Postman | [@Johnyol](https://github.com/Johnyol) |
