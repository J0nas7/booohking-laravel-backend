locals {
  image_hash = substr(sha1(var.registry_image), 0, 8)
}

resource "scaleway_container" "laravel_app" {
  # new container each deploy
  name = "${var.container_name}-${local.image_hash}"
  registry_image = var.registry_image
  namespace_id   = var.namespace_id
  port           = 8080
  min_scale      = var.min_scale
  max_scale      = var.max_scale
  memory_limit   = var.memory_limit
  cpu_limit      = var.cpu_limit

  environment_variables = merge(
    {
      APP_NAME    = "Booohking"
      APP_ENV     = "production"
      APP_DEBUG   = "false"
      APP_URL     = "https://nsbooohkinglaravelban2j1epl7-booohking-laravel-backend.functions.fnc.fr-par.scw.cloud"
      LOG_CHANNEL = "stderr"

      SESSION_DRIVER    = var.session_driver
      QUEUE_CONNECTION  = var.use_redis ? "redis" : "sync"
      CACHE_STORE       = var.use_redis ? "redis" : "database"

      MAIL_MAILER       = "smtp"
      MAIL_HOST         = var.mail_host
      MAIL_PORT         = var.mail_port
      MAIL_USERNAME     = var.mail_username
      MAIL_ENCRYPTION   = "tls"
      MAIL_FROM_ADDRESS = var.mail_from_address
      MAIL_FROM_NAME    = var.mail_from_name
    },
    var.use_redis ? {
      REDIS_CLIENT = "phpredis"
      REDIS_HOST   = var.redis_host
      REDIS_PORT   = "6379"
    } : {}
  )

  secret_environment_variables = merge(
    {
      APP_KEY       = var.app_key
      DB_URL        = var.db_url
      JWT_SECRET    = var.jwt_secret
      MAIL_PASSWORD = var.mail_password
    },
    var.use_redis ? {
      REDIS_PASSWORD = var.redis_password
    } : {}
  )
}

terraform {
  required_providers {
    scaleway = {
      source  = "scaleway/scaleway"
      version = "~> 2.9"
    }
  }
}

