output "vpc_id" {
  value = aws_vpc.nimbus_vpc.id
}

output "public_subnet_a_id" {
  value = aws_subnet.public_subnet_a.id
}

output "public_subnet_b_id" {
  value = aws_subnet.public_subnet_b.id
}

output "private_subnet_a_id" {
  value = aws_subnet.private_subnet_a.id
}

output "private_subnet_b_id" {
  value = aws_subnet.private_subnet_b.id
}

output "internet_gateway_id" {
  value = aws_internet_gateway.nimbus_igw.id
}

output "public_route_table_id" {
  value = aws_route_table.public_route_table.id
}

output "alb_security_group_id" {
  value = aws_security_group.alb_sg.id
}

output "ecs_security_group_id" {
  value = aws_security_group.ecs_sg.id
}

output "alb_dns_name" {
  value = aws_lb.nimbus_alb.dns_name
}

output "alb_arn" {
  value = aws_lb.nimbus_alb.arn
}

output "target_group_arn" {
  value = aws_lb_target_group.nimbus_tg.arn
}

output "frontend_bucket_name" {
  value = aws_s3_bucket.frontend_bucket.bucket
}

output "cloudfront_domain_name" {
  value = aws_cloudfront_distribution.frontend_cdn.domain_name
}

output "dynamodb_articles_table" {
  value = aws_dynamodb_table.articles.name
}

output "ecr_repository_url" {
  value = aws_ecr_repository.nimbus_backend.repository_url
}

output "sns_topic_arn" {
  value = aws_sns_topic.alerts.arn
}

output "cloudtrail_bucket" {
  value = aws_s3_bucket.cloudtrail_logs.bucket
}