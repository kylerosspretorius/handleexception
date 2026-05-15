data "aws_route53_zone" "this" {
  name         = "${var.domain}."
  private_zone = false
}

resource "aws_route53_record" "root" {
  zone_id = data.aws_route53_zone.this.zone_id
  name    = var.domain
  type    = "A"
  ttl     = 300
  records = [var.ip_address]
}

resource "aws_route53_record" "www" {
  zone_id = data.aws_route53_zone.this.zone_id
  name    = "www.${var.domain}"
  type    = "A"
  ttl     = 300
  records = [var.ip_address]
}
