# Root module variables for GitHub Actions deployment

variable "container_name" {
  type        = string
  description = "Name of the Scaleway container"
}

variable "registry_image" {
  type        = string
  description = "Docker image to deploy"
}

variable "app_key" {
  type        = string
  description = "Laravel APP_KEY"
  sensitive   = true
}

variable "db_url" {
  type        = string
  description = "Database connection URL"
  sensitive   = true
}

variable "jwt_secret" {
  type        = string
  description = "JWT secret key"
  sensitive   = true
}

variable "mail_host" {
  type        = string
  description = "SMTP host"
}

variable "mail_port" {
  type        = string
  description = "SMTP port"
  default     = "587"
}

variable "mail_username" {
  type        = string
  description = "SMTP username"
}

variable "mail_password" {
  type        = string
  description = "SMTP password"
  sensitive   = true
}

variable "mail_from_address" {
  type        = string
  description = "Mail sender address"
}

variable "mail_from_name" {
  type        = string
  description = "Mail sender name"
}

variable "session_driver" {
  type        = string
  description = "Laravel session driver"
  default     = "database"
}

variable "use_redis" {
  type        = bool
  description = "Whether to enable Redis for cache and queues"
  default     = false
}

variable "redis_host" {
  type        = string
  description = "Redis host"
  default     = null
}

variable "redis_password" {
  type        = string
  description = "Redis password"
  sensitive   = true
  default     = null
}

variable "scaleway_access_key" {
  type        = string
  description = "Scaleway API access key"
  sensitive   = true
}

variable "scaleway_secret_key" {
  type        = string
  description = "Scaleway API secret key"
  sensitive   = true
}

variable "scaleway_project_id" {
  type        = string
  description = "Scaleway project ID"
}

variable "region" {
  type        = string
  description = "Scaleway region"
  default     = "fr-par"
}

variable "namespace_name" {
  type        = string
  description = "Scaleway container namespace name"
}
