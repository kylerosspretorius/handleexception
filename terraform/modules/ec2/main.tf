data "aws_ami" "ubuntu" {
  most_recent = true
  owners      = ["099720109477"] # Canonical

  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }
}

resource "aws_key_pair" "this" {
  key_name   = "handleexception-deployer"
  public_key = var.ssh_public_key
}

resource "aws_security_group" "this" {
  name        = "handleexception-invoices"
  description = "HTTP, HTTPS, and restricted SSH for the invoice app"

  ingress {
    description = "SSH"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = [var.ssh_allowed_cidr]
  }

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "HTTPS"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = { Project = "handleexception-dashboard" }
}

resource "aws_instance" "this" {
  ami                    = data.aws_ami.ubuntu.id
  instance_type          = var.instance_type
  key_name               = aws_key_pair.this.key_name
  vpc_security_group_ids = [aws_security_group.this.id]

  root_block_device {
    volume_type = "gp3"
    # Free tier: 30 GB EBS total. 8 GB leaves headroom for snapshots.
    volume_size = 8
  }

  user_data = <<-EOF
    #!/bin/bash
    set -e

    apt-get update -y

    # Docker
    curl -fsSL https://get.docker.com | sh
    usermod -aG docker ubuntu

    # Docker Compose v2
    mkdir -p /usr/local/lib/docker/cli-plugins
    curl -SL https://github.com/docker/compose/releases/latest/download/docker-compose-linux-x86_64 \
      -o /usr/local/lib/docker/cli-plugins/docker-compose
    chmod +x /usr/local/lib/docker/cli-plugins/docker-compose

    # Certbot for TLS
    apt-get install -y git certbot python3-certbot-nginx

    # App directory
    mkdir -p /var/www/handleexception
    chown ubuntu:ubuntu /var/www/handleexception
  EOF

  tags = {
    Name    = "handleexception-dashboard"
    Project = "handleexception-dashboard"
  }
}

resource "aws_eip" "this" {
  instance = aws_instance.this.id
  domain   = "vpc"

  tags = { Project = "handleexception-dashboard" }
}
