variable "instance_type" {
  description = "EC2 instance type — t2.micro is free-tier eligible (750 hrs/month)"
  type        = string
  default     = "t2.micro"
}

variable "ssh_public_key" {
  description = "Contents of your SSH public key (~/.ssh/id_rsa.pub)"
  type        = string
  sensitive   = true
}

variable "ssh_allowed_cidr" {
  description = "CIDR block allowed to SSH. Restrict to your IP in production."
  type        = string
}
