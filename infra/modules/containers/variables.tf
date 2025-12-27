variable "container_name" {     type = string }
variable "registry_image" {     type = string }
variable "namespace_id" {       type = string }
variable "app_key" {            type = string, sensitive = true }
variable "db_url" {             type = string, sensitive = true }
variable "jwt_secret" {         type = string, sensitive = true }
variable "mail_password" {      type = string, sensitive = true }
variable "mail_host" {          type = string }
variable "mail_port" {          type = string, default = "587" }
variable "mail_username" {      type = string }
variable "mail_from_address" {  type = string }
variable "mail_from_name" {     type = string }
variable "session_driver" {     type = string, default = "database" }
variable "use_redis" {          type = bool, default = false }
variable "redis_host" {         type = string, default = null }
variable "redis_password" {     type = string, sensitive = true, default = null }
variable "min_scale" {          type = number, default = 0 }
variable "max_scale" {          type = number, default = 10 }
variable "memory_limit" {       type = number, default = 256 }
variable "cpu_limit" {          type = number, default = 140 }
