output "fqdn" {
  description = "Fully-qualified domain name of the app"
  value       = aws_route53_record.app.fqdn
}
