# Diagramas de Arquitetura — Fase 3

**Oficina Mecânica · Grupo 183 · FIAP SOAT Pós-Tech**

Documentação arquitetural da Fase 3: diagrama de **componentes** (visão de nuvem) e de **sequência** (autenticação por CPF + abertura de ordem de serviço). Os desenhos refletem as decisões registradas nos [RFCs](rfcs-fase3.md) e [ADRs](adrs-fase3.md) e no [contrato de autenticação](contrato-autenticacao.md).

> Dois **domínios de identidade** — **cliente** (por CPF, token RS256) e **staff** (login + role) — que nunca se cruzam. A cor **teal** marca o domínio cliente e a **violeta** o domínio staff.

---

## 1. Diagrama de Componentes

Visão de nuvem (AWS): API Gateway (Kong no EKS), Lambda de autenticação, aplicação em Kubernetes, banco gerenciado e observabilidade.

![Diagrama de Componentes da arquitetura da Fase 3](diagrama-componentes.png)

O **Kong** roda dentro do EKS e é a única porta de entrada. A **Lambda** assina o JWT com a chave privada do **SSM**; a API valida com a chave pública. Linhas tracejadas representam observabilidade e deploy.

---

## 2. Diagrama de Sequência

Cobre os três fluxos: autenticação do cliente por CPF, o cliente consultando a própria OS (guard cliente + escopo por CPF) e a abertura de OS pelo staff.

![Diagrama de Sequência: autenticação por CPF e abertura de ordem de serviço](diagrama-sequencia.png)

As bandas **teal** são o domínio cliente (token RS256, escopo por CPF); a banda **violeta** é o domínio staff (login + role). Um token de cliente nunca alcança uma operação de staff.

---

## Decisões que sustentam os diagramas

| ADR | Decisão |
|---|---|
| **ADR-0005** | **Cliente × Staff** — dois domínios de identidade independentes; token de cliente só acessa rotas de cliente, com escopo por CPF. |
| **ADR-0004** | **JWT RS256** — a Lambda assina com a chave privada (SSM); a API valida com a pública. |
| **ADR-0003** | **Kong no EKS** — API Gateway open-source rodando no cluster. |
| **ADR-0002** | **HPA 2–10** — escalabilidade automática (CPU 70% / memória 80%). |

---

<details>
<summary><strong>Fonte editável (Mermaid)</strong> — para regenerar ou editar os diagramas</summary>

### Componentes

```mermaid
%%{init: {'theme':'base','themeVariables':{'fontFamily':'ui-sans-serif, system-ui, sans-serif','clusterBkg':'#f1f6fa','clusterBorder':'#9fb3c8','lineColor':'#64748b','primaryColor':'#e8f1f5','primaryBorderColor':'#1c6b8c','primaryTextColor':'#152234','edgeLabelBackground':'#ffffff'},'flowchart':{'nodeSpacing':50,'rankSpacing':70,'curve':'basis'}}}%%
flowchart LR
    cliente["CLIENTE<br/><small>autentica por CPF</small>"]:::cliente
    staff["STAFF<br/><small>admin · atendente · mecânico</small>"]:::staff
    subgraph aws["AWS"]
      direction TB
      subgraph eks["EKS · Kubernetes"]
        kong["Kong<br/><small>API Gateway</small>"]:::infra
        api["API Laravel<br/><small>pods · HPA 2–10</small>"]:::infra
      end
      lambda["Lambda Auth<br/><small>CPF → JWT RS256</small>"]:::infra
      ssm["SSM<br/><small>chave privada RSA</small>"]:::sec
      rds[("RDS<br/>PostgreSQL 15")]:::infra
    end
    nr["New Relic + OTel<br/><small>APM · logs · alertas</small>"]:::obs
    gha["GitHub Actions<br/><small>CI/CD</small>"]:::obs

    cliente -->|"POST /auth · Bearer"| kong
    staff -->|"login"| kong
    kong -->|"/auth"| lambda
    kong -->|"rotas protegidas"| api
    lambda -->|"consulta CPF"| rds
    lambda -.->|"lê chave"| ssm
    api -->|"leitura / escrita"| rds
    api -.->|"métricas · logs"| nr
    gha -.->|"deploy"| aws

    style aws fill:#f1f6fa,stroke:#9fb3c8,stroke-dasharray:5 4
    style eks fill:#eaf1f7,stroke:#1c6b8c
    classDef cliente fill:#e1f3f0,stroke:#0e8a7d,color:#0b3b35;
    classDef staff fill:#efe8fa,stroke:#7c4dbb,color:#3a2560;
    classDef infra fill:#e8f1f5,stroke:#1c6b8c,color:#123049;
    classDef sec fill:#faf1de,stroke:#c47f17,color:#5a3d0a;
    classDef obs fill:#eef2f6,stroke:#5b6b80,color:#2a3543;
```

### Sequência

```mermaid
%%{init: {'theme':'base','themeVariables':{'fontFamily':'ui-sans-serif, system-ui, sans-serif','actorBkg':'#e8f1f5','actorBorder':'#1c6b8c','actorTextColor':'#123049','signalColor':'#43526a','signalTextColor':'#2a3543','labelBoxBkg':'#eef4f7','labelBoxBorderColor':'#1c6b8c','noteBkg':'#faf1de','noteBorderColor':'#c47f17'}}}%%
sequenceDiagram
    autonumber
    actor C as Cliente
    participant K as Kong
    participant L as Lambda
    participant DB as RDS
    participant API as API Laravel
    actor A as Atendente

    rect rgb(225, 243, 240)
    Note over C,DB: Fluxo 1 · Autenticação do cliente por CPF
    C->>K: POST /auth { cpf }
    K->>L: encaminha
    L->>L: valida CPF (dígitos)
    L->>DB: consulta cliente por CPF
    DB-->>L: cliente { id, status }
    alt CPF inválido
        L-->>C: 400 cpf_invalido
    else não encontrado
        L-->>C: 404 cliente_nao_encontrado
    else inativo
        L-->>C: 403 cliente_inativo
    else ativo
        L->>L: assina JWT RS256 (chave SSM)
        L-->>C: 200 { token, Bearer, 3600 }
    end
    end

    rect rgb(226, 244, 241)
    Note over C,DB: Fluxo 2 · Cliente consulta a própria OS
    C->>K: GET /ordens-servico/{id} + Bearer
    K->>API: encaminha
    API->>API: guard cliente valida RS256 (iss·aud·exp·typ)
    API->>API: escopo por CPF
    alt não é a própria OS
        API-->>C: 403 nao_autorizado
    else autorizado
        API->>DB: busca OS
        DB-->>API: OS
        API-->>C: 200 { OS }
    end
    end

    rect rgb(239, 232, 250)
    Note over A,DB: Fluxo 3 · Abertura de OS (staff)
    A->>K: POST /ordens-servico (login staff)
    K->>API: encaminha
    API->>API: auth:api + role (admin·atendente)
    API->>DB: cria ordem de serviço
    DB-->>API: OS criada
    API-->>A: 201 { OS }
    end
```

</details>

---

*Grupo 183 · FIAP SOAT Pós-Tech · Fase 3 · Diagramas · rev.2*
