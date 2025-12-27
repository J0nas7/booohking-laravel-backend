output "container_url" {
  description = "Public URL of the Laravel container"
  value = scaleway_container.laravel_app.domain_name
}

output "container_id" { value = scaleway_container.laravel_app.id }
output "namespace_id" { value = data.scaleway_container_namespace.laravel_namespace.id }
output "image_tag" { value = scaleway_container.laravel_app.registry_image }
