data "aws_caller_identity" "current" {}

resource "aws_vpc" "nimbus_vpc" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name = "Nimbus-VPC"
  }
}

resource "aws_subnet" "public_subnet_a" {
  vpc_id                  = aws_vpc.nimbus_vpc.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "us-east-1a"
  map_public_ip_on_launch = true

  tags = {
    Name = "Nimbus-Public-Subnet-A"
  }
}

resource "aws_subnet" "public_subnet_b" {
  vpc_id                  = aws_vpc.nimbus_vpc.id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = "us-east-1b"
  map_public_ip_on_launch = true

  tags = {
    Name = "Nimbus-Public-Subnet-B"
  }
}

resource "aws_subnet" "private_subnet_a" {
  vpc_id            = aws_vpc.nimbus_vpc.id
  cidr_block        = "10.0.11.0/24"
  availability_zone = "us-east-1a"

  tags = {
    Name = "Nimbus-Private-Subnet-A"
  }
}

resource "aws_subnet" "private_subnet_b" {
  vpc_id            = aws_vpc.nimbus_vpc.id
  cidr_block        = "10.0.12.0/24"
  availability_zone = "us-east-1b"

  tags = {
    Name = "Nimbus-Private-Subnet-B"
  }
}

resource "aws_internet_gateway" "nimbus_igw" {
  vpc_id = aws_vpc.nimbus_vpc.id

  tags = {
    Name = "Nimbus-Internet-Gateway"
  }
}

resource "aws_route_table" "public_route_table" {
  vpc_id = aws_vpc.nimbus_vpc.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.nimbus_igw.id
  }

  tags = {
    Name = "Nimbus-Public-Route-Table"
  }
}

resource "aws_route_table_association" "public_a" {
  subnet_id      = aws_subnet.public_subnet_a.id
  route_table_id = aws_route_table.public_route_table.id
}

resource "aws_route_table_association" "public_b" {
  subnet_id      = aws_subnet.public_subnet_b.id
  route_table_id = aws_route_table.public_route_table.id
}

resource "aws_security_group" "alb_sg" {
  name        = "nimbus-alb-sg"
  description = "Security group for Application Load Balancer"
  vpc_id      = aws_vpc.nimbus_vpc.id

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

  tags = {
    Name = "Nimbus-ALB-SG"
  }
}

resource "aws_security_group" "ecs_sg" {
  name        = "nimbus-ecs-sg"
  description = "Security group for ECS tasks"
  vpc_id      = aws_vpc.nimbus_vpc.id

  ingress {
    description     = "Traffic from ALB"
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb_sg.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "Nimbus-ECS-SG"
  }
}

resource "aws_lb" "nimbus_alb" {
  name               = "nimbus-alb"
  internal           = false
  load_balancer_type = "application"

  security_groups = [
    aws_security_group.alb_sg.id
  ]

  subnets = [
    aws_subnet.public_subnet_a.id,
    aws_subnet.public_subnet_b.id
  ]

  tags = {
    Name = "Nimbus-ALB"
  }
}

resource "aws_lb_target_group" "nimbus_tg" {
  name        = "nimbus-target-group"
  port        = 80
  protocol    = "HTTP"
  target_type = "ip"

  vpc_id = aws_vpc.nimbus_vpc.id

  health_check {
    enabled             = true
    healthy_threshold   = 2
    unhealthy_threshold = 2
    interval            = 30
    path                = "/"
    protocol            = "HTTP"
    matcher             = "200"
  }

  tags = {
    Name = "Nimbus-Target-Group"
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.nimbus_alb.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.nimbus_tg.arn
  }
}

resource "aws_s3_bucket" "frontend_bucket" {
  bucket = "nimbus-frontend-files"

  tags = {
    Name = "Nimbus Frontend"
  }
}

resource "aws_s3_bucket_versioning" "frontend_versioning" {
  bucket = aws_s3_bucket.frontend_bucket.id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_public_access_block" "frontend_block" {
  bucket = aws_s3_bucket.frontend_bucket.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_cloudfront_origin_access_control" "frontend_oac" {
  name                              = "nimbus-oac"
  description                       = "Nimbus CloudFront OAC"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_cloudfront_distribution" "frontend_cdn" {

  enabled             = true
  default_root_object = "index.html"

  origin {
    domain_name              = aws_s3_bucket.frontend_bucket.bucket_regional_domain_name
    origin_id                = "nimbus-s3-origin"
    origin_access_control_id = aws_cloudfront_origin_access_control.frontend_oac.id
  }

  default_cache_behavior {

    target_origin_id       = "nimbus-s3-origin"
    viewer_protocol_policy = "redirect-to-https"

    allowed_methods = [
      "GET",
      "HEAD"
    ]

    cached_methods = [
      "GET",
      "HEAD"
    ]

    forwarded_values {
      query_string = false

      cookies {
        forward = "none"
      }
    }
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    cloudfront_default_certificate = true
  }

  tags = {
    Name = "Nimbus CloudFront"
  }
}

resource "aws_s3_bucket_policy" "frontend_policy" {

  bucket = aws_s3_bucket.frontend_bucket.id

  policy = jsonencode({
    Version = "2012-10-17"

    Statement = [
      {
        Sid    = "AllowCloudFront"
        Effect = "Allow"

        Principal = {
          Service = "cloudfront.amazonaws.com"
        }

        Action = "s3:GetObject"

        Resource = "${aws_s3_bucket.frontend_bucket.arn}/*"

        Condition = {
          StringEquals = {
            "AWS:SourceArn" = aws_cloudfront_distribution.frontend_cdn.arn
          }
        }
      }
    ]
  })
}

resource "aws_dynamodb_table" "articles" {

  name         = "articles"
  billing_mode = "PAY_PER_REQUEST"

  hash_key = "id"

  attribute {
    name = "id"
    type = "S"
  }

  tags = {
    Name = "Nimbus Articles Table"
  }
}

resource "aws_ecr_repository" "nimbus_backend" {

  name = "nimbus-backend"

  image_scanning_configuration {
    scan_on_push = true
  }

  force_delete = true

  tags = {
    Name = "Nimbus Backend Repository"
  }
}

resource "aws_secretsmanager_secret" "nimbus_app_secret" {

  name = "nimbus-app-secrets"

  description = "Nimbus application secrets"

  recovery_window_in_days = 0
}

resource "aws_secretsmanager_secret_version" "nimbus_app_secret_value" {

  secret_id = aws_secretsmanager_secret.nimbus_app_secret.id

  secret_string = jsonencode({
    DYNAMODB_TABLE_ARTICLES = "articles"
  })
}

resource "aws_cloudwatch_log_group" "ecs_logs" {

  name              = "/ecs/nimbus"
  retention_in_days = 14

  tags = {
    Name = "Nimbus ECS Logs"
  }
}
resource "aws_sns_topic" "alerts" {

  name = "nimbus-alerts"

  tags = {
    Name = "Nimbus Alerts"
  }
}

resource "aws_s3_bucket" "cloudtrail_logs" {

  bucket        = "nimbus-cloudtrail-logs-icu"
  force_destroy = true

  tags = {
    Name = "Nimbus CloudTrail Logs"
  }
}

resource "aws_cloudtrail" "nimbus_trail" {

  depends_on = [
    aws_s3_bucket_policy.cloudtrail_policy
  ]

  name                          = "nimbus-trail"
  s3_bucket_name                = aws_s3_bucket.cloudtrail_logs.bucket
  include_global_service_events = true
  is_multi_region_trail         = true
  enable_logging                = true
}

resource "aws_s3_bucket_policy" "cloudtrail_policy" {

  bucket = aws_s3_bucket.cloudtrail_logs.id

  policy = jsonencode({
    Version = "2012-10-17"

    Statement = [

      {
        Sid    = "AWSCloudTrailAclCheck"
        Effect = "Allow"

        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }

        Action = "s3:GetBucketAcl"

        Resource = aws_s3_bucket.cloudtrail_logs.arn
      },

      {
        Sid    = "AWSCloudTrailWrite"
        Effect = "Allow"

        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }

        Action = "s3:PutObject"

        Resource = "${aws_s3_bucket.cloudtrail_logs.arn}/AWSLogs/${data.aws_caller_identity.current.account_id}/*"

        Condition = {
          StringEquals = {
            "s3:x-amz-acl" = "bucket-owner-full-control"
          }
        }
      }
    ]
  })
}

resource "aws_iam_role" "flow_logs_role" {

  name = "nimbus-flowlogs-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"

    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"

        Principal = {
          Service = "vpc-flow-logs.amazonaws.com"
        }
      }
    ]
  })
}

resource "aws_iam_role_policy" "flow_logs_policy" {

  name = "nimbus-flowlogs-policy"
  role = aws_iam_role.flow_logs_role.id

  policy = jsonencode({
    Version = "2012-10-17"

    Statement = [
      {
        Effect = "Allow"

        Action = [
          "logs:CreateLogGroup",
          "logs:CreateLogStream",
          "logs:PutLogEvents",
          "logs:DescribeLogGroups",
          "logs:DescribeLogStreams"
        ]

        Resource = "*"
      }
    ]
  })
}

resource "aws_flow_log" "vpc_flow_logs" {

  iam_role_arn = aws_iam_role.flow_logs_role.arn

  log_destination_type = "cloud-watch-logs"

  log_destination = aws_cloudwatch_log_group.ecs_logs.arn

  traffic_type = "ALL"

  vpc_id = aws_vpc.nimbus_vpc.id
}

resource "aws_cognito_user_pool" "nimbus_users" {
  name = "placeholder-import"
}

resource "aws_cognito_user_pool_client" "nimbus_client" {
  name         = "placeholder-import"
  user_pool_id = aws_cognito_user_pool.nimbus_users.id
}


