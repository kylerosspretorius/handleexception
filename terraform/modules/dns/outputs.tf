output "fqdn" {
  description = "Root domain of the app"
  value       = aws_route53_record.root.fqdn
}
