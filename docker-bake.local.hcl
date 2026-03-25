target "backend" {
  # disable registry cache export locally
  cache-to = ["type=local,dest=./.build-cache/backend,mode=max"]
  cache-from = ["type=local,src=./.build-cache/backend"]
}

target "frontend" {
  cache-to = ["type=local,dest=./.build-cache/frontend,mode=max"]
  cache-from = ["type=local,src=./.build-cache/frontend"]
}