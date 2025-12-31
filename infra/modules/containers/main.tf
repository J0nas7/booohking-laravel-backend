resource "scaleway_container" "laravel_app" {
  name           = "booohking-laravel-backend"
  registry_image = var.registry_image
  namespace_id   = var.namespace_id
  port           = 8080
  min_scale      = var.min_scale
  max_scale      = var.max_scale
  memory_limit   = var.memory_limit
  cpu_limit      = var.cpu_limit
}

terraform {
  required_providers {
    scaleway = {
      source  = "scaleway/scaleway"
      version = "~> 2.9"
    }
  }
}

