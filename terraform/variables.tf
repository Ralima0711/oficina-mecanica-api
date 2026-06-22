variable "aws_region" {
  description = "Região AWS"
  type        = string
  default     = "us-east-1"
}

variable "cluster_name" {
  description = "Nome do cluster EKS"
  type        = string
  default     = "oficina-mecanica-cluster"
}

variable "lab_role_arn" {
  description = "ARN da role do AWS Academy Lab"
  type        = string
  # Exemplo: "arn:aws:iam::123456789012:role/LabRole"
}

variable "subnet_ids" {
  description = "Lista de subnet IDs para o cluster"
  type        = list(string)
}

variable "db_username" {
  description = "Usuário do banco de dados"
  type        = string
  sensitive   = true
}

variable "db_password" {
  description = "Senha do banco de dados"
  type        = string
  sensitive   = true
}
