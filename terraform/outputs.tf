output "cluster_endpoint" {
  description = "Endpoint do cluster EKS"
  value       = aws_eks_cluster.oficina.endpoint
}

output "cluster_name" {
  description = "Nome do cluster EKS"
  value       = aws_eks_cluster.oficina.name
}

output "cluster_arn" {
  description = "ARN do cluster EKS"
  value       = aws_eks_cluster.oficina.arn
}

output "db_endpoint" {
  description = "Endpoint do RDS PostgreSQL (host:porta)"
  value       = aws_db_instance.oficina_db.endpoint
  sensitive   = true
}

output "db_address" {
  description = "Endereco do RDS PostgreSQL"
  value       = aws_db_instance.oficina_db.address
  sensitive   = true
}

output "db_name" {
  description = "Nome do banco de dados"
  value       = aws_db_instance.oficina_db.db_name
}

output "db_username" {
  description = "Usuario do banco de dados"
  value       = var.db_username
  sensitive   = true
}
