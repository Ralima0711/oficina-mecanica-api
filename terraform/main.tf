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

data "aws_subnet" "selected" {
  id = var.subnet_ids[0]
}

data "aws_vpc" "selected" {
  id = data.aws_subnet.selected.vpc_id
}

# ── EKS Cluster ────────────────────────────────────────────────────

resource "aws_eks_cluster" "oficina" {
  name     = var.cluster_name
  role_arn = var.lab_role_arn

  vpc_config {
    subnet_ids              = var.subnet_ids
    endpoint_public_access  = true
    endpoint_private_access = false
  }

  tags = {
    Project = "oficina-mecanica"
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

  depends_on = [aws_eks_cluster.oficina]
}

# ── Security Group para RDS ────────────────────────────────────────

resource "aws_security_group" "rds_sg" {
  name        = "${var.cluster_name}-db-sg"
  description = "Security Group para RDS PostgreSQL"
  vpc_id      = data.aws_vpc.selected.id

  ingress {
    description = "PostgreSQL a partir da VPC do cluster"
    from_port   = 5432
    to_port     = 5432
    protocol    = "tcp"
    cidr_blocks = [data.aws_vpc.selected.cidr_block]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Project = "oficina-mecanica"
  }
}

# ── RDS PostgreSQL ─────────────────────────────────────────────────

resource "aws_db_subnet_group" "oficina" {
  name       = "${var.cluster_name}-db-subnet"
  subnet_ids = var.subnet_ids

  tags = {
    Project = "oficina-mecanica"
  }
}

resource "aws_db_instance" "oficina_db" {
  identifier           = "${var.cluster_name}-db"
  engine               = "postgres"
  engine_version       = "15"
  instance_class       = "db.t3.micro"
  allocated_storage    = 20
  storage_encrypted    = true
  multi_az             = false
  publicly_accessible  = false
  skip_final_snapshot  = true

  db_name  = "oficina_mecanica"
  username = var.db_username
  password = var.db_password

  db_subnet_group_name   = aws_db_subnet_group.oficina.name
  vpc_security_group_ids = [aws_security_group.rds_sg.id]

  tags = {
    Project = "oficina-mecanica"
  }
}
