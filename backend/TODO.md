# Backend TODO

This document tracks pending improvements and planned features for the backend.

---

## High Priority

- [ ] Fix JwtMiddleware to verify tokens using `TokenService`
- [ ] Improve error handling with structured logging
- [ ] Add request validation for authentication endpoints
- [ ] Ensure `storage/logs` directory is automatically created

---

## Authentication Improvements

- [ ] Add logout endpoint
- [ ] Store refresh tokens in database
- [ ] Implement refresh token revocation
- [ ] Add password strength validation
- [ ] Normalize email addresses before storage

---

## API Improvements

- [ ] Add pagination to `/users`
- [ ] Implement additional CRUD endpoints
- [ ] Improve API response consistency
- [ ] Add request validation layer

---

## Security Improvements

- [ ] Improve rate limiting (time-based instead of file counter)
- [ ] Add request size limits
- [ ] Add brute force login protection
- [ ] Add input sanitization layer

---

## Architecture Improvements

- [ ] Add base `Controller` class
- [ ] Improve dependency injection container (support singletons)
- [ ] Improve router parameter parsing
- [ ] Add support for PUT / PATCH / DELETE methods

---

## Documentation

- [ ] Add API documentation page
- [ ] Generate Swagger/OpenAPI specification
- [ ] Add architecture diagrams
- [ ] Document authentication flow

---

## Performance

- [ ] Add Redis caching layer
- [ ] Optimize database queries
- [ ] Implement better rate limiting with Redis

---

## Testing

- [ ] Add unit tests for services
- [ ] Add middleware tests
- [ ] Add authentication tests
- [ ] Add repository tests
