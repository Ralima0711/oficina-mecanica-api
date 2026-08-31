# RFCs — Tech Challenge SOAT Fase 3 (Grupo 183)

> **RFC = Request for Comments.** Documenta uma decisão técnica relevante com contexto, alternativas e trade-offs, para discussão do time. Uma vez fechada, a decisão permanente vira um **ADR**.
> Autora: Roberta (Tech Lead) · Revisão do time: Gustavo, David, Johny

Índice:
- RFC-001 — Escolha da nuvem
- RFC-002 — Escolha do banco de dados
- RFC-003 — Estratégia de autenticação
- RFC-004 — Ferramenta de observabilidade
- RFC-005 — API Gateway

---

## RFC-001 — Escolha da nuvem

**Status:** Proposta · **Data:** 18/08/2026

### Contexto
A Fase 3 exige infraestrutura como código, cluster Kubernetes gerenciado, banco gerenciado, Function serverless e API Gateway. Precisamos de uma nuvem que atenda os quatro com bom suporte de Terraform e que minimize retrabalho em relação à Fase 2.

### Decisão proposta
Adotar **AWS**. A Fase 2 já provisiona **EKS** (Kubernetes) e **RDS PostgreSQL** via Terraform na AWS; manter a nuvem evita reescrever infraestrutura e aproveita o conhecimento já acumulado pelo time de infra.

### Alternativas consideradas
- **Azure** — o curso tem aula de API Management do Azure; viável, mas exigiria migrar EKS/RDS já prontos, sem ganho para o escopo.
- **GCP** — fora do conteúdo do curso e sem base instalada.

### Consequências / trade-offs
- (+) Reaproveita EKS + RDS + pipelines da Fase 2; menor risco e menor tempo.
- (+) Integração nativa entre API Gateway, Lambda e EKS.
- (−) Lock-in AWS; atenção a custos (usar free tier e `terraform destroy` fora das janelas de teste).

### Referências
Roadmap da Fase 3; consolidado da Fase 2 (EKS + RDS via Terraform).

---

## RFC-002 — Escolha do banco de dados

**Status:** Proposta · **Data:** 18/08/2026

### Contexto
O desafio pede um **banco de dados gerenciado** e uma justificativa formal da escolha, além de ajustes no modelo relacional. A Fase 2 já usa PostgreSQL 15.

### Decisão proposta
Manter **PostgreSQL 15 gerenciado (AWS RDS)**. Escolhido na Fase 2 sobre o MySQL 8 pela robustez em consultas complexas, suporte a tipos avançados (JSONB, arrays), conformidade com o padrão SQL e integração nativa com o Eloquent ORM. O RDS gerenciado agrega backups automáticos, alta disponibilidade e patching sem operação manual.

### Alternativas consideradas
- **MySQL 8 gerenciado** — atende, mas sem os ganhos de JSONB/consultas complexas já explorados.
- **SQL Server** — custo de licença e sem aderência ao stack Laravel/Eloquent do projeto.

### Consequências / trade-offs
- (+) Zero migração de dados/ORM; modelo relacional da Fase 2 reaproveitado.
- (+) Recursos gerenciados (backup, HA) reduzem operação.
- (−) Custo do RDS; instância `db.t3.micro` no free tier para o projeto acadêmico.

### Referências
README da Fase 2 (seção "Por que PostgreSQL"); diagrama ER (dbdiagram.io).

---

## RFC-003 — Estratégia de autenticação

**Status:** Proposta · **Data:** 18/08/2026

### Contexto
O desafio exige proteger rotas sensíveis com **autenticação via CPF** e uma **Function serverless** que valida o CPF, consulta o cliente e devolve um JWT. A aplicação já tinha JWT (HS256) para o staff.

### Decisão proposta
- **API Gateway + Lambda serverless** para autenticar o **cliente** por CPF, emitindo um **JWT assinado em RS256** (a Lambda assina com a chave privada; a API valida com a pública).
- Preservar a autenticação de **staff** (admin/atendente/mecânico) existente. São **dois domínios de identidade distintos** (detalhado no contrato de autenticação e no ADR-0005).

### Alternativas consideradas
- **Autenticação dentro da própria aplicação (sem serverless)** — não cumpre o requisito de Function serverless.
- **AWS Cognito** — poderoso, mas overhead alto para o escopo; o desafio pede uma function própria de validação de CPF.
- **HS256 com segredo compartilhado** — mais simples, mas espalha o segredo entre Lambda e API (maior superfície de ataque). Fica como plano B.

### Consequências / trade-offs
- (+) Emissor (Lambda) separado do validador (API); a API nunca vê a chave privada.
- (+) Escalável e alinhado ao requisito serverless.
- (−) Gestão de par de chaves RSA (SSM Parameter Store + K8s Secret); um pouco mais de setup que HS256.

### Referências
`docs/contrato-autenticacao.md` (rev.2); ADR-0004 (RS256); ADR-0005 (separação cliente × staff).

---

## RFC-004 — Ferramenta de observabilidade

**Status:** Proposta · **Data:** 18/08/2026

### Contexto
O desafio pede monitorar latência das APIs, consumo de CPU/memória do Kubernetes, healthchecks/uptime, alertas para falhas em ordens de serviço, logs estruturados JSON com correlação e dashboards. O conteúdo do curso cobre Zabbix, Prometheus+Grafana, OpenTelemetry, Datadog e New Relic.

### Decisão proposta
Adotar **New Relic (APM) + OpenTelemetry** para instrumentação. O New Relic cobre num só lugar latência, infraestrutura K8s, uptime, logs JSON com correlação, alertas e dashboards; o OpenTelemetry mantém a instrumentação **vendor-neutral** (traces/métricas/logs), reduzindo lock-in. Todos cobertos pelas aulas da fase.

### Alternativas consideradas
- **Datadog** — equivalente ao New Relic e citado no desafio; free tier mais restrito para o escopo.
- **Prometheus + Grafana (+ Zabbix)** — self-hosted, sem custo de SaaS, mas exige operar o stack no cluster e montar dashboards do zero (mais esforço na Semana 3).

### Consequências / trade-offs
- (+) New Relic free tier generoso (sem cartão) cobre todos os requisitos de monitoramento de uma vez.
- (+) OTel evita amarrar a app ao fornecedor.
- (−) Dependência de SaaS externo; atenção ao volume de ingestão no free tier.

### Referências
PDF do desafio (Monitoramento e Observabilidade); aulas de OpenTelemetry, New Relic e Datadog do curso.

---

## RFC-005 — API Gateway

**Status:** Proposta · **Data:** 18/08/2026 (revisada após validação do conteúdo das aulas)

### Contexto
É necessário um **API Gateway** para roteamento e para proteger as rotas com o token emitido pela Lambda. Validando contra as aulas da Fase 3, os gateways **ensinados** são o **Kong** (3 aulas: Conhecendo o Kong, Serviços e Rotas, Consumers) e o **Azure API Management**. O **AWS API Gateway não é coberto** por nenhuma fase do curso.

### Decisão proposta
Adotar **Kong**, **implantado no próprio cluster EKS**. É open-source, ensinado na Fase 3 e roda sobre o Kubernetes que já usamos — mantém todo o stack na AWS (RFC-001) sem depender de um serviço de gateway fora do conteúdo do curso.

### Alternativas consideradas
- **AWS API Gateway** — boa integração com Lambda, mas **não é ensinado em nenhuma fase**; descartado por não estar coberto pelas aulas.
- **Azure API Management** — ensinado na Fase 3, mas implicaria usar Azure, contrariando a RFC-001.

### Consequências / trade-offs
- (+) Coberto pelas aulas da Fase 3; controle total e portabilidade (open-source).
- (+) Roda no EKS existente — sem serviço gerenciado adicional nem troca de nuvem.
- (−) Mais um componente para implantar/operar no cluster (Helm ou manifests, plugin de auth JWT); a validação do JWT pode ficar no plugin do Kong e/ou na aplicação.

### Referências
ADR-0003 (registro da decisão); contrato de autenticação (fluxo de roteamento); aulas "Conhecendo o Kong / Criando Serviços e Rotas / Consumers" (Fase 3).

---

*Grupo 183 · FIAP SOAT Pós-Tech · Fase 3 · RFCs · 18/08/2026*
