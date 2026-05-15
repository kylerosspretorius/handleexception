output "app_url" {
  description = "Public URL of the app"
  value       = "https://${var.domain}"
}

output "elastic_ip" {
  description = "Elastic IP — SSH target and DNS A record value"
  value       = module.ec2.public_ip
}

output "ssh_command" {
  description = "SSH command to connect to the instance"
  value       = module.ec2.ssh_command
}

output "s3_bucket_name" {
  description = "S3 bucket name — set AWS_BUCKET to this in .env"
  value       = module.s3.bucket_name
}

output "iam_access_key_id" {
  description = "AWS_ACCESS_KEY_ID for the app .env"
  value       = module.s3.iam_access_key_id
}

output "iam_secret_access_key" {
  description = "AWS_SECRET_ACCESS_KEY for the app .env — retrieve with: terraform output -raw iam_secret_access_key"
  value       = module.s3.iam_secret_access_key
  sensitive   = true
}
