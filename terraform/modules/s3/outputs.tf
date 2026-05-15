output "bucket_name" {
  description = "S3 bucket name — set AWS_BUCKET to this in .env"
  value       = aws_s3_bucket.this.bucket
}

output "bucket_arn" {
  description = "S3 bucket ARN"
  value       = aws_s3_bucket.this.arn
}

output "iam_access_key_id" {
  description = "AWS_ACCESS_KEY_ID for the app .env"
  value       = aws_iam_access_key.app.id
}

output "iam_secret_access_key" {
  description = "AWS_SECRET_ACCESS_KEY for the app .env — shown once"
  value       = aws_iam_access_key.app.secret
  sensitive   = true
}
