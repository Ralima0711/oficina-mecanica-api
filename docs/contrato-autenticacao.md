# Contrato de Autenticação — Fase 3

**SOAT Pós-Tech FIAP · Grupo 32**
**Autor: Roberta (Tech Lead / Segurança & Identidade)**
**rev.1 — 11/08/2026 · rev.2 — 18/08/2026 (separação cliente × staff)**
**Status: Aprovado para implementação — Johny (Lambda + rotas) e Gustavo (API Gateway) codificam contra este contrato**

> Objetivo: fixar a interface de autenticação para que Lambda, API Gateway e API Laravel sejam desenvolvidos em paralelo, sem um esperar o outro.

---

## 0. Modelo de identidade — DOIS domínios (ler primeiro)

A aplicação tem **dois tipos de ator distintos**, e eles NÃO se misturam:

| Domínio | Quem é | Como autentica | Token |
|---|---|---|---|
| **Cliente** | O dono do veículo (pessoa física) | **CPF** → Lambda | JWT **RS256** (novo, Fase 3) |
| **Staff** | admin / atendente / mecânico | login e-mail+senha (Fase 2) | JWT HS256 (`auth:api`, existente) |

Regra de ouro: **o token de cliente autoriza apenas rotas de cliente; o token de staff autoriza as rotas de staff.** Um token de cliente **nunca** acessa operação de staff (abrir OS, submeter diagnóstico, métricas etc.) — isso seria escalação de privilégio. Os dois guards **coexistem**; não se substitui um pelo outro.

---

## 1. Decisão: algoritmo de assinatura do JWT do cliente → **RS256**

| | HS256 (segredo compartilhado) | **RS256 (par de chaves) — ESCOLHIDO** |
|---|---|---|
| Como funciona | Lambda e API usam o mesmo segredo | Lambda assina com **chave privada**; API valida com **chave pública** |
| Segurança | Segredo em dois serviços (maior superfície) | API nunca vê a chave privada; só a Lambda assina |
| Veredito | Fallback aceitável se apertar | **Recomendado** — separa emissor de validador |

**Decisão:** RS256 para o token de cliente. A Lambda é o único emissor; a API valida com a chave pública. Vira **ADR-0004**. O token de staff (`auth:api`) continua como está na Fase 2 — **não** é alterado.

**Gestão de chaves:** par RSA 2048. Chave **privada** → AWS SSM Parameter Store (SecureString), lida pela Lambda. Chave **pública** → K8s Secret, montada na API. Nunca commitar nenhuma das duas.

---

## 2. Fluxo de autenticação do cliente (visão de sequência)

```
Cliente ──POST /auth {cpf}──▶ API Gateway ──▶ Lambda auth
                                                   │
                                    valida CPF (dígitos verificadores)
                                                   │
                                    consulta cliente no RDS por CPF
                                                   │
                        ┌──────────────────────────┼──────────────────────────┐
                     não existe                  inativo                     ativo
                        │                          │                          │
                     404                         403                assina JWT (RS256) → 200 {token}
                                                                              │
Cliente ──GET /public/ordens-servico/{id} (Authorization: Bearer <token>)──▶ API
                                                                              │
                                    valida assinatura (chave pública) + exp/iss/aud
                                                                              │
                                    escopo: a OS pertence a ESTE cliente? (claim cpf/client_id)
                                                                              │
                                                              autorizado → responde
```

---

## 3. Endpoint da Lambda de autenticação

**`POST /auth`** (exposto via API Gateway; rota **pública**)

### Request
```json
{ "cpf": "529.982.247-25" }
```
Aceitar com ou sem máscara; normalizar para 11 dígitos.

### Responses

| Situação | Status | Corpo |
|---|---|---|
| CPF válido + cliente ativo | 200 | `{ "token": "<JWT RS256>", "token_type": "Bearer", "expires_in": 3600 }` |
| CPF ausente/inválido | 400 | `{ "error": "cpf_invalido", "message": "CPF inválido." }` |
| Cliente não cadastrado | 404 | `{ "error": "cliente_nao_encontrado", "message": "Cliente não encontrado." }` |
| Cliente inativo | 403 | `{ "error": "cliente_inativo", "message": "Cliente inativo." }` |
| Erro interno | 500 | logar com correlação, não vazar detalhe |

---

## 4. Claims do JWT do cliente

```json
{
  "sub": "<id do cliente>",
  "cpf": "52998224725",
  "client_id": "<id do cliente>",
  "typ": "cliente",
  "status": "ativo",
  "iss": "oficina-lambda-auth",
  "aud": "oficina-mecanica-api",
  "iat": 1760000000,
  "exp": 1760003600
}
```
- `exp`: 1 hora (3600s).
- `typ": "cliente"` deixa explícito o domínio do token (facilita a API distinguir de um token de staff).
- `iss`/`aud` são validados pela API.
- Não colocar dado sensível além de CPF/status.

---

## 5. O que muda na API Laravel — **guard novo, sem tocar no staff**

A Fase 2 já tem o guard `auth:api` (tymon, HS256) para **staff**. Ele **permanece intacto**. A Fase 3 adiciona um **guard novo** só para o token de cliente:

1. Criar um guard/middleware **`cliente`** que valida o JWT **RS256** com a chave pública: assinatura, `exp`, `iss` = `oficina-lambda-auth`, `aud` = `oficina-mecanica-api` e `typ` = `cliente`.
2. Esse guard **não** consulta a tabela `users` (o `sub` é id de cliente, não de usuário). A identidade do cliente vem dos próprios claims (`client_id`/`cpf`).
3. Token inválido/expirado → **401** `{ "error": "nao_autorizado" }`.
4. **NÃO** migrar `auth:api` para RS256 e **NÃO** remover o middleware `role:...` de nenhuma rota de staff. Os dois guards coexistem.

> Correção em relação à rev.1: a rev.1 sugeria migrar o `auth:api` para RS256 — **isso está cancelado**. O staff continua HS256; o cliente é um guard separado.

---

## 6. Rotas — quem protege o quê (substitui a versão genérica da rev.1)

| Grupo de rotas | Domínio | Proteção |
|---|---|---|
| `POST /auth` (na Lambda), `GET /health` | — | **Pública** |
| `GET /public/ordens-servico/{id}` (consultar) | **Cliente** | Guard `cliente` (RS256) **+ escopo pelo CPF** do token |
| `GET /public/ordens-servico/{id}/aprovar/{token}` | **Cliente** | Guard `cliente` (RS256) + escopo pelo CPF |
| `GET /public/ordens-servico/{id}/reprovar/{token}` | **Cliente** | Guard `cliente` (RS256) + escopo pelo CPF |
| `ordens-servico` (index/store/show/update/entregar/status) | **Staff** | `auth:api` + `role:...` — **inalterado** |
| `usuarios`, `clientes`, `veiculos`, `pecas`, `insumos`, `servicos`, `notificacoes` | **Staff** | `auth:api` — **inalterado** |
| `ordens-servico/metricas/tempo-medio` | **Staff** | `auth:api` + `role:admin` — **inalterado** |

**Escopo obrigatório nas rotas de cliente:** antes de responder, comparar o cliente dono da OS com o `client_id`/`cpf` do token. Se não bater → **403**. Um cliente só acessa a **própria** OS.

> As rotas `public/ordens-servico/*` deixam de ser abertas (hoje dependem só de um token na URL) e passam a exigir o JWT de cliente. É exatamente essa transformação que cumpre o requisito "proteger rotas sensíveis com autenticação via CPF".

---

## 7. Variáveis e segredos

| Item | Onde vive | Quem usa |
|---|---|---|
| Chave privada RSA | AWS SSM Parameter Store (SecureString) | Lambda (assina) |
| Chave pública RSA | K8s Secret (`jwt-public-key`) | API (guard `cliente`) |
| Credencial de leitura do RDS | IAM role da Lambda | Lambda (consulta CPF) |
| `JWT_ISS`, `JWT_AUD`, `JWT_TTL` | Env compartilhado (mesmos valores) | Lambda e API |

---

## 8. O que cada um faz com este contrato

- **Johny (Lambda):** `POST /auth` conforme seções 3, 4 e 7 — pode desenvolver com mock da consulta antes do RDS existir.
- **Johny (API):** criar o guard `cliente` (seção 5) e aplicá-lo **só** ao grupo `public/ordens-servico/*` com escopo por CPF (seção 6). Não encostar nas rotas de staff.
- **Gustavo (API Gateway):** `/auth` pública; rotas de cliente exigindo Bearer token; roteamento conforme seção 2.
- **Roberta:** transformar as seções 0/1 em ADR-0004 (RS256) e ADR-0005 (separação cliente × staff); usar seções 2 e 3 no Diagrama de Sequência.

---

*Grupo 32 · FIAP SOAT Pós-Tech · Fase 3 · Contrato de Autenticação · rev.2 · 18/08/2026*
