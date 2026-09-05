# Infraestrutura AWS (Fase 3)

O Terraform deste repositório foi **extraído** para dois repositórios independentes, conforme o Tech Challenge da Fase 3:

| Stack | Repositório | Conteúdo |
|---|---|---|
| Banco gerenciado | [oficina-infra-database](https://github.com/Ralima0711/oficina-infra-database) | RDS PostgreSQL 15, subnet group, security group |
| Cluster + API Gateway | [oficina-infra-k8s](https://github.com/Ralima0711/oficina-infra-k8s) | EKS, node group, Kong (Helm) e Ingress |

Os manifestos Kubernetes da **aplicação** continuam em `../k8s/` (Deployment, Service, HPA, ConfigMap, Secret).

Se ainda existir `terraform.tfstate` nesta pasta (ambiente antigo da Fase 2), **não apague** até migrar o state ou recriar os recursos nos novos repos. Não versione esse arquivo.
