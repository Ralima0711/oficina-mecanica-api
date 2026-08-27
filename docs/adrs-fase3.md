# ADRs — Tech Challenge SOAT Fase 3 (Grupo 183)

> **ADR = Architecture Decision Record.** Registra uma decisão arquitetural **permanente**, com contexto, decisão e consequências. Enquanto o RFC discute, o ADR fixa.
> Formato por registro: Contexto · Decisão · Status · Consequências.
> Autora: Roberta (Tech Lead)

Índice:
- ADR-0001 — Segregação em 4 repositórios
- ADR-0002 — Escalabilidade automática com HPA
- ADR-0003 — API Gateway = AWS API Gateway
- ADR-0004 — Assinatura do JWT do cliente em RS256
- ADR-0005 — Separação de domínios de identidade (cliente × staff)

---

## ADR-0001 — Segregação em 4 repositórios

**Status:** Aceito · **Data:** 18/08/2026

### Contexto
A Fase 2 era um monorepo (app + Terraform + manifests K8s). A Fase 3 exige o projeto organizado em quatro repositórios separados, cada um com CI/CD independente e branch protegida.

### Decisão
Dividir em: `oficina-lambda-auth` (Function serverless), `oficina-infra-k8s` (Terraform EKS + API Gateway), `oficina-infra-database` (Terraform RDS) e `oficina-mecanica-api` (aplicação + manifests). Cada repo com pipeline próprio, `main` protegida por PR e o usuário `soat-architecture` como collaborator.

### Consequências
- (+) Ciclo de vida e deploy independentes por componente; blast radius menor.
- (+) Atende diretamente o requisito do desafio.
- (−) Mais pipelines e READMEs para manter; a extração do Terraform do monorepo exige cuidado para não quebrar o CI/CD existente.

---

## ADR-0002 — Escalabilidade automática com HPA

**Status:** Aceito (herdado da Fase 2) · **Data:** 18/08/2026

### Contexto
A aplicação precisa escalar sob carga mantendo disponibilidade. A Fase 2 já validou o Horizontal Pod Autoscaler com teste de carga (k6).

### Decisão
Manter o **HPA** no EKS com **mín 2 / máx 10 pods**, escalando por **CPU 70%** ou **memória 80%**.

### Consequências
- (+) Alta disponibilidade e elasticidade sem intervenção manual.
- (+) Já validado por teste de carga; baixo risco.
- (−) Custo variável conforme escala; exige métricas de recursos configuradas no cluster.

---

## ADR-0003 — API Gateway = Kong (no EKS)

**Status:** Aceito · **Data:** 18/08/2026

### Contexto
Necessário um gateway para rotear e proteger as rotas com o token da Lambda (RFC-005). A validação contra as aulas da Fase 3 mostrou que o AWS API Gateway não é coberto pelo curso; o **Kong** é (3 aulas).

### Decisão
Adotar o **Kong** como API Gateway, **implantado no cluster EKS**, com `/auth` como rota pública (para obter o token) e as rotas de cliente exigindo Bearer token, integrado à Lambda de autenticação. A validação do JWT pode ficar no plugin de auth do Kong e/ou na aplicação.

### Consequências
- (+) Coberto pelas aulas da Fase 3; open-source; roda no EKS existente sem troca de nuvem.
- (−) Mais um componente para operar no cluster (deploy via Helm/manifests, configuração de plugins).

---

## ADR-0004 — Assinatura do JWT do cliente em RS256

**Status:** Aceito · **Data:** 18/08/2026

### Contexto
A Lambda emite um JWT que a API valida. Discutido na RFC-003. HS256 exigiria compartilhar o mesmo segredo entre Lambda e API.

### Decisão
O JWT do **cliente** é assinado em **RS256**: a Lambda assina com a **chave privada** (armazenada no AWS SSM Parameter Store, SecureString) e a API valida com a **chave pública** (montada via K8s Secret). A API nunca tem acesso à chave privada.

### Consequências
- (+) Separa emissor de validador; reduz a superfície de exposição do segredo.
- (+) Rotação de chave sem redeploy sincronizado dos dois serviços.
- (−) Gestão de par de chaves RSA; setup um pouco maior que HS256 (que fica como plano B se o prazo apertar).

---

## ADR-0005 — Separação de domínios de identidade (cliente × staff)

**Status:** Aceito · **Data:** 18/08/2026

### Contexto
A Fase 3 introduz autenticação por CPF para o **cliente**, mas a aplicação já tinha autorização por **role** para o **staff** (admin/atendente/mecânico), que resolve o usuário na tabela `users`. O token de cliente traz `sub = id do cliente` (não existe em `users`) e não tem `role` — misturar os dois quebraria a autorização e abriria brecha de segurança.

### Decisão
Tratar **cliente** e **staff** como **dois domínios de identidade distintos e independentes**:
- O token de **cliente** (RS256, emitido pela Lambda) autoriza **apenas** rotas de cliente (grupo `public/ordens-servico/*`), com **escopo por CPF** (o cliente só acessa a própria OS). Validado por um **guard novo `cliente`**.
- O token de **staff** (login existente, HS256, guard `auth:api` + `role`) permanece **inalterado**. Um token de cliente nunca acessa operações de staff (abrir OS, submeter diagnóstico, métricas etc.).

### Consequências
- (+) Evita escalação de privilégio (cliente não vira staff) e garante que cliente só vê o próprio dado.
- (+) Menor esforço de implementação: não se altera o `auth:api` nem o middleware `role` das rotas de staff.
- (−) A API passa a manter **dois guards** convivendo; a documentação (contrato, README, diagrama de sequência) precisa deixar os dois fluxos explícitos.

---

*Grupo 183 · FIAP SOAT Pós-Tech · Fase 3 · ADRs · 18/08/2026*
