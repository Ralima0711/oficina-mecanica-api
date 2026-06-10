output "cluster_endpoint" {
  description = "Endpoint do cluster EKS"
  value       = aws_eks_cluster.oficina.endpoint
}

output "cluster_name" {
  description = "Nome do cluster EKS"
  value       = aws_eks_cluster.oficina.name
}

output "db_endpoint" {
  description = "Endpoint do RDS PostgreSQL"
  value       = aws_db_instance.oficina_db.endpoint
  sensitive   = true
}
