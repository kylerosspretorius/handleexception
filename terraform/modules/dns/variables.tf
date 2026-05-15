variable "domain" {
  description = "Root domain — a Route 53 hosted zone must already exist for this domain"
  type        = string
}

variable "subdomain" {
  description = "Subdomain prefix (e.g. 'invoices' → invoices.handleexception.com)"
  type        = string
}

variable "ip_address" {
  description = "IP address the A record should point at"
  type        = string
}
