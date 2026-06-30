# Infraestrutura como Codigo (IaC) - Oficina Mecanica API

Scripts Terraform para provisionar a infraestrutura da aplicacao na AWS:

- **Cluster Kubernetes (EKS)**
- **Banco de dados PostgreSQL (RDS)**
- **Security Groups e subnet group** para comunicacao segura

---

## Recursos criados

| Recurso | Descricao |
|---------|-----------|
| `aws_eks_cluster.oficina` | Cluster Kubernetes gerenciado (EKS) |
| `aws_eks_node_group.oficina_nodes` | Node group com 1-4 instancias `t3.medium` |
| `aws_db_subnet_group.oficina` | Subnet group do RDS nas subnets informadas |
| `aws_security_group.rds_sg` | Security group do RDS (porta 5432 a partir da VPC) |
| `aws_db_instance.oficina_db` | PostgreSQL 15 (`db.t3.micro`, 20 GB, banco `oficina_mecanica`) |

---

## Pre-requisitos

- [Terraform](https://www.terraform.io/downloads) >= 1.3.0
- [AWS CLI](https://aws.amazon.com/cli/) configurado
- Acesso a conta AWS (ex.: AWS Academy Lab)
- `kubectl` (para validar o cluster apos o provisionamento)

---

## Como aplicar

### 1. Configurar variaveis

```bash
cd terraform
cp terraform.tfvars.example terraform.tfvars
```

Edite `terraform.tfvars` com os valores do seu ambiente:

```hcl
aws_region   = "us-east-1"
cluster_name = "oficina-mecanica-cluster"
lab_role_arn = "arn:aws:iam::SEU_ACCOUNT:role/LabRole"
subnet_ids   = ["subnet-xxxxx", "subnet-yyyyy"]
db_username  = "admin"
db_password  = "SenhaForte123!@#"
```

> No AWS Academy: copie o ARN da role `LabRole` (IAM) e os IDs das subnets da VPC do laboratorio.

Nao faca commit de `terraform.tfvars` (contem credenciais).

### 2. Inicializar e validar

```bash
terraform init
terraform validate
```

### 3. Planejar e aplicar

```bash
terraform plan -out=tfplan
terraform apply tfplan
```

O provisionamento leva cerca de 10-15 minutos.

### 4. Verificar outputs

```bash
terraform output
```

Serao exibidos endpoint do cluster, ARN e dados de conexao do banco.

---

## Como testar

### Testar o cluster Kubernetes

```bash
aws eks update-kubeconfig --region us-east-1 --name oficina-mecanica-cluster
kubectl get nodes
kubectl cluster-info
```

Esperado: nodes em status `Ready`.

### Testar o banco de dados

Com um pod temporario no cluster:

```bash
kubectl run pg-test --rm -it --image=postgres:15-alpine --restart=Never -- \
  psql -h $(terraform output -raw db_address) -U admin -d oficina_mecanica
```

Informe a senha definida em `terraform.tfvars`. Conexao bem-sucedida confirma que o RDS esta acessivel a partir da VPC.

### Testar conexao local (opcional)

Se tiver acesso de rede a VPC (VPN ou bastion), use:

```bash
psql -h $(terraform output -raw db_address) -U admin -d oficina_mecanica
```

---

## Destruir a infraestrutura

```bash
terraform destroy
```

Remove cluster, nodes, RDS e demais recursos criados pelo Terraform.

---

## Estrutura de arquivos

```
terraform/
├── main.tf                      # EKS, RDS, security groups
├── variables.tf                 # Variaveis de entrada
├── outputs.tf                   # Endpoints e credenciais de saida
├── terraform.tfvars.example     # Exemplo de configuracao
└── README.md                    # Este arquivo
```
