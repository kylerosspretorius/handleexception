terraform {
  required_version = ">= 1.6"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  # Remote state — stored in S3 (no locking needed for a solo project).
  # Create the bucket manually first, then run: terraform init
  backend "s3" {
    bucket  = "handleexception-terraform-state"
    key     = "invoices/terraform.tfstate"
    region  = "eu-west-2"
    encrypt = true
    profile = "handleexception"
  }
}

provider "aws" {
  region  = var.aws_region
  profile = "handleexception"
}

module "ec2" {
  source           = "./modules/ec2"
  instance_type    = var.ec2_instance_type
  ssh_public_key   = var.ssh_public_key
  ssh_allowed_cidr = var.ssh_allowed_cidr
}

module "s3" {
  source      = "./modules/s3"
  bucket_name = var.s3_bucket_name
}

module "dns" {
  source     = "./modules/dns"
  domain     = var.domain
  subdomain  = var.subdomain
  ip_address = module.ec2.public_ip
}
