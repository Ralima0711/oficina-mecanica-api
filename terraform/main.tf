terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
  required_version = ">= 1.3.0"
}

provider "aws" {
  region = var.aws_region
}

# ── EKS Cluster ────────────────────────────────────────────────────

resource "aws_eks_cluster" "oficina" {
  name     = var.cluster_name
  role_arn = var.lab_role_arn

  vpc_config {
    subnet_ids = var.subnet_ids
  }

  tags = {
    Project = "oficina-mecanica"
    Phase   = "2"
  }
}

resource "aws_eks_node_group" "oficina_nodes" {
  cluster_name    = aws_eks_cluster.oficina.name
  node_group_name = "${var.cluster_name}-nodes"
  node_role_arn   = var.lab_role_arn
  subnet_ids      = var.subnet_ids

  scaling_config {
    desired_size = 2
    min_size     = 1
    max_size     = 4
  }

  instance_types = ["t3.medium"]
}

# ── RDS PostgreSQL ─────────────────────────────────────────────────

resource "aws_db_instance" "oficina_db" {
  identifier        = "${var.cluster_name}-db"
  engine            = "postgres"
  engine_version    = "15"
  instance_class    = "db.t3.micro"
  allocated_storage = 20

  db_name  = "oficina_mecanica"
  username = var.db_username
  password = var.db_password

  skip_final_snapshot = true

  tags = {
    Project = "oficina-mecanica"
  }
}
