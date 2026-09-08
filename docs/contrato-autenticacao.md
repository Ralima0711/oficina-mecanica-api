# Contrato de Autenticação — Fase 3

**SOAT Pós-Tech FIAP · Grupo 32**
**Autor: Roberta (Tech Lead / Segurança & Identidade) · 11/08/2026**
**Status: Aprovado para implementação — Johny (Lambda + rotas) e Gustavo (API Gateway) codificam contra este contrato**

> Objetivo deste documento: fixar a interface de autenticação para que a Lambda, o API Gateway e a API Laravel sejam desenvolvidos em paralelo, sem um esperar o outro.

---

## 1. Decisão: algoritmo de assinatura do JWT → **RS256**

| | HS256 (segredo compartilhado) | **RS256 (par de chaves) — ESCOLHIDO** |
|---|---|---|
| Como funciona | Lambda e API usam o mesmo `JWT_SECRET` | Lambda assina com **chave privada**; API valida com **chave pública** |
| Segurança | Segredo precisa existir nos dois serviços (maior superfície) | API nunca vê a chave privada; só a Lambda assina |
| Rotação | Trocar segredo nos dois lugares ao mesmo tempo | Trocar só a chave privada; pública distribuída |
| Veredito | Fallback aceitável se o prazo apertar | **Recomendado** — separa quem emite de quem valida |

**Decisão:** RS256. A Lambda é o **único** emissor de token; a API Laravel apenas valida com a chave pública. Isso alinha com boa prática de IAM (separação emissor/validador) e vira **ADR-0004**.

> **Plano B (se apertar):** HS256 com `JWT_SECRET` compartilhado via K8s Secret + Lambda env. Migração para RS256 depois é transparente para o cliente (o token não muda de formato, só a chave de verificação).

**Gestão de chaves:** par RSA 2048 gerado uma vez. Chave **privada** → AWS SSM Parameter Store (SecureString), lida pela Lambda em runtime. Chave **pública** → K8s Secret montado na API. Nunca commitar nenhuma das duas.

---

## 2. Fluxo de autenticação (visão de sequência)

```
Cliente ──POST /auth {cpf}──▶ API Gateway ──▶ Lambda auth
                                                   │
                                    valida formato do CPF (dígitos verificadores)
                                                   │
                                    consulta cliente no RDS por CPF
                                                   │
                        ┌──────────────────────────┼──────────────────────────┐
                     não existe                  inativo                     ativo
                        │                          │                          │
                     404                         403                assina JWT (RS256) → 200 {token}
                                                                              │
Cliente ──GET /ordens-servico  (Authorization: Bearer <token>)──▶ API Gateway ──▶ API Laravel
                                                                              │
                                                       valida assinatura (chave pública) + exp
                                                                              │
                                                              autorizado → processa requisição
```

---

## 3. Endpoint da Lambda de autenticação

**`POST /auth`** (exposto via API Gateway; rota **pública**)

### Request
```json
{ "cpf": "529.982.247-25" }
```
- Aceitar CPF com ou sem máscara; a Lambda normaliza para 11 dígitos.

### Responses

**200 OK** — cliente existe e está ativo
```json
{
  "token": "<JWT RS256>",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**400 Bad Request** — CPF ausente ou inválido (formato/dígitos verificadores)
```json
{ "error": "cpf_invalido", "message": "CPF inválido." }
```

**404 Not Found** — CPF válido, mas cliente não cadastrado
```json
{ "error": "cliente_nao_encontrado", "message": "Cliente não encontrado." }
```

**403 Forbidden** — cliente existe, mas está inativo/bloqueado
```json
{ "error": "cliente_inativo", "message": "Cliente inativo." }
```

**500** — erro interno (falha ao consultar o banco, etc.) — logar com correlação, não vazar detalhe.

---

## 4. Claims do JWT (payload)

```json
{
  "sub": "<id do cliente>",
  "cpf": "52998224725",
  "client_id": "<id do cliente>",
  "status": "ativo",
  "iss": "oficina-lambda-auth",
  "aud": "oficina-mecanica-api",
  "iat": 1760000000,
  "exp": 1760003600
}
```
- **`exp`**: 1 hora (3600s) após `iat`.
- **`iss`/`aud`**: a API Laravel valida esses dois valores — rejeita token de outro emissor/destino.
- Não colocar dado sensível além de CPF/status (o token é legível por quem o possui).

---

## 5. O que muda na API Laravel (`tymon/jwt-auth`)

1. Configurar `JWT_ALGO=RS256` e apontar `JWT_PUBLIC_KEY` para a chave pública montada via Secret.
2. Middleware valida: **assinatura** (chave pública), **`exp`**, **`iss`** = `oficina-lambda-auth`, **`aud`** = `oficina-mecanica-api`.
3. A API **não emite** mais token (o endpoint de login sai da API e vai para a Lambda). A API só valida.
4. Token inválido/expirado → **401** com corpo padronizado `{ "error": "nao_autorizado" }`.

---

## 6. Rotas a proteger (autenticação obrigatória) x públicas

| Rota | Método | Proteção |
|---|---|---|
| `/auth` | POST | **Pública** (é onde se obtém o token) |
| `/health` | GET | **Pública** (healthcheck do K8s/observabilidade) |
| `/ordens-servico` (listar/criar/atualizar) | GET/POST/PUT | **Protegida** |
| `/clientes`, `/veiculos` | GET/POST/PUT | **Protegida** |
| `/pecas`, `/insumos`, `/estoque` | GET/POST/PUT | **Protegida** |
| Swagger UI (`/api/documentation`) | GET | Pública (ou protegida por rede, a critério) |

> Regra geral: tudo que manipula dados de negócio é protegido; só `/auth` e `/health` ficam abertos.

---

## 7. Variáveis e segredos necessários

| Item | Onde vive | Quem usa |
|---|---|---|
| Chave privada RSA | AWS SSM Parameter Store (SecureString) | Lambda (assina) |
| Chave pública RSA | K8s Secret (`jwt-public-key`) | API Laravel (valida) |
| Credencial de leitura do RDS | Lambda env / IAM role da Lambda | Lambda (consulta CPF) |
| `JWT_ISS`, `JWT_AUD`, `JWT_TTL` | Env compartilhado (mesmos valores) | Lambda e API |

---

## 8. O que cada um pode começar AGORA com este contrato

- **Johny (Lambda):** implementar `POST /auth` seguindo as seções 3, 4 e 7 — pode desenvolver e testar localmente antes mesmo do RDS separado existir (mock da consulta).
- **Johny (API):** ajustar o middleware conforme a seção 5 e marcar as rotas da seção 6.
- **Gustavo (API Gateway):** configurar `/auth` como rota pública e as demais exigindo o Bearer token; roteamento conforme seção 2.
- **Roberta:** transformar a seção 1 em **ADR-0004** e usar as seções 2 e 3 como base do **Diagrama de Sequência** (entregável da Semana 5).

---

*Grupo 32 · FIAP SOAT Pós-Tech · Fase 3 · Contrato de Autenticação · 11/08/2026*
