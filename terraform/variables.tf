variable "aws_region" {
  description = "AWS region for all resources"
  type        = string
  default     = "eu-west-2"
}

variable "domain" {
  description = "Root domain — a Route 53 hosted zone must already exist for this domain"
  type        = string
  default     = "handleexception.com"
}

variable "s3_bucket_name" {
  description = "S3 bucket name for invoice PDFs and logos — must be globally unique"
  type        = string
  default     = "handleexception-customer-data"
}

variable "ec2_instance_type" {
  description = "EC2 instance type — t2.micro is free-tier eligible (750 hrs/month for 12 months)"
  type        = string
  default     = "t2.micro"
}

variable "ssh_public_key" {
  description = "Contents of your SSH public key (~/.ssh/id_rsa.pub or similar)"
  type        = string
  sensitive   = true
}

variable "ssh_allowed_cidr" {
  description = "CIDR block allowed to SSH into the instance. Restrict to your IP in production."
  type        = string
}
