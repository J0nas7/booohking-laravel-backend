data "scaleway_container_namespace" "laravel_namespace" {
  name   = var.namespace_name
  region = var.region
}

module "laravel_app" {
  source            = "./modules/containers"
  container_name    = var.container_name
  registry_image    = var.registry_image
  namespace_id      = data.scaleway_container_namespace.laravel_namespace.id
  app_key           = var.app_key
  db_url            = var.db_url
  jwt_secret        = var.jwt_secret
  mail_password     = var.mail_password
  mail_host         = var.mail_host
  mail_port         = "587"
  mail_username     = "mail@gmail.com"
  mail_from_address = "mail@gmail.com"
  mail_from_name    = "Booohking"
  session_driver    = var.session_driver
  use_redis         = var.use_redis
  redis_host        = var.redis_host
  redis_password    = var.redis_password

  scaleway_access_key = var.scaleway_access_key
  scaleway_secret_key = var.scaleway_secret_key
  scaleway_project_id = var.scaleway_project_id
  region              = var.region

  providers = {
    scaleway = scaleway
  }
}

terraform {
  required_providers {
    scaleway = {
      source  = "scaleway/scaleway"
      version = "~> 2.9" # pick the latest stable version
    }
  }

  required_version = ">= 1.5.0"
}

provider "scaleway" {
  access_key         = var.scaleway_access_key
  secret_key         = var.scaleway_secret_key
  default_project_id = var.scaleway_project_id
  region             = var.region
}
