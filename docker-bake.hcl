variable "FORGEJO_REGISTRY" {}
variable "FORGEJO_IMAGE" {}
variable "TAG" {}
variable "IMAGE_PREFIX" {
  default = "${FORGEJO_REGISTRY}/${FORGEJO_IMAGE}"
}
variable "DOCKER_PLATFORM" {
  default = "linux/amd64"
}

group "default" {
  targets = ["backend", "frontend"]
}

target "common" {
  platforms = ["${DOCKER_PLATFORM}"]
  provenance = false
  sbom = false
  pull   = true
}

target "backend" {
  inherits = ["common"]
  context    = "."
  dockerfile = "backend/Dockerfile"

  tags = [
    "${IMAGE_PREFIX}/backend:${TAG}",
    "${IMAGE_PREFIX}/backend:latest"
  ]

  cache-from = [
    "type=registry,ref=${IMAGE_PREFIX}/backend:latest"
  ]
  cache-to = [
    "type=inline"
  ]
}

target "frontend" {
  inherits = ["common"]
  context    = "."
  dockerfile = "frontend/Dockerfile"

  tags = [
    "${IMAGE_PREFIX}/frontend:${TAG}",
    "${IMAGE_PREFIX}/frontend:latest"
  ]

  cache-from = [
    "type=registry,ref=${IMAGE_PREFIX}/frontend:latest"
  ]
  cache-to = [
    "type=registry,ref=${IMAGE_PREFIX}/frontend:cache,mode=max"
  ]
}