# Backend — SITizen Review

This repository contains the backend API for **SITizen Review**, implemented using a **custom lightweight PHP micro-framework** with a modular MVC-style architecture.

The system provides **JWT-based authentication**, middleware support, a layered service architecture, and database interaction through **Eloquent ORM**.

---

## Architecture Overview

The backend follows a **layered architecture**:

Client  
↓  
Router  
↓  
Middleware  
↓  
Controller  
↓  
Service Layer  
↓  
Repository  
↓  
Eloquent Model  
↓  
Database  

### Key Characteristics

- Custom PHP router (no full framework)
- Middleware pipeline
- Dependency Injection container
- JWT authentication (access + refresh tokens)
- Eloquent ORM (Illuminate Database)
- Migration system
- OpenAPI documentation via Swagger annotations
- Environment-based configuration

---

## Technology Stack

- **PHP 8+**
- **Eloquent ORM** (`illuminate/database`)
- **JWT Authentication** (`firebase/php-jwt`)
- **Environment Configuration** (`vlucas/phpdotenv`)
- **Logging** (`monolog`)
- **OpenAPI Documentation** (`swagger-php`)

---

## Project Structure

```
backend
│
├ app
│  ├ Config
│  │  ├ database.php
│  │  └ jwt.php
│  │
│  ├ Controllers
│  │  ├ AuthController.php
│  │  └ UserController.php
│  │
│  ├ Core
│  │  ├ Container.php
│  │  ├ ErrorHandler.php
│  │  ├ Logger.php
│  │  ├ Request.php
│  │  ├ Response.php
│  │  └ Router.php
│  │
│  ├ Middleware
│  │  ├ CorsMiddleware.php
│  │  ├ JwtMiddleware.php
│  │  └ RateLimitMiddleware.php
│  │
│  ├ Models
│  │  └ User.php
│  │
│  ├ Providers
│  │  └ AppServiceProvider.php
│  │
│  ├ Repositories
│  │  └ UserRepository.php
│  │
│  └ Services
│     ├ AuthService.php
│     ├ TokenService.php
│     └ UserService.php
│
├ database
│  ├ migrations
│  │  └ 001_create_users_table.php
│  └ migrate.php
│
├ md-docs
│  └ Stack.md
│
├ storage
│  └ logs
│
├ composer.json
└ README.md
```

---

## Authentication System

The backend implements **JWT authentication with dual tokens**.

### Access Token

- Used for API requests
- Short lifetime (15 minutes)

### Refresh Token

- Used to obtain new access tokens
- Long lifetime (7 days)

### Authentication Flow

1. User logs in with email/password
2. Server validates credentials
3. Server returns: `access_token,refresh_token,token_type,expires_in`
4. Client sends `Authorization: Bearer <token>` for protected endpoints
5. `JwtMiddleware` validates the token

---

## API Endpoints

### Authentication

| Method | Endpoint | Description |
| ------ | -------- | ----------- |
| POST | `/auth/register` | Register new user |
| POST | `/auth/login` | Login and receive tokens |
| POST | `/auth/refresh` | Refresh access token |
| GET | `/auth/me` | Get authenticated user |

---

### Users

| Method | Endpoint | Description |
| ------ | -------- | ----------- |
| GET | `/users` | Get all users |
| GET | `/users/{id}` | Get user by ID |

---

## Setup Instructions

### 1. Install Dependencies

```bash
composer install
```

---

### 2. Create `.env`

Example:

```env
DB_HOST=localhost
DB_NAME=sitizen
DB_USER=root
DB_PASS=

JWT_SECRET=super_secret_key
JWT_EXPIRE=3600
```

---

### 3. Run Database Migrations

```bash
php database/migrate.php
```

---

### 4. Start Development Server

Example:

```bash
php -S localhost:8000 -t public
```

---

## Middleware

The system includes several middleware components.

### CORS Middleware

Handles cross-origin requests.

### JWT Middleware

Validates authentication tokens.

### Rate Limit Middleware

Basic IP-based request limiting.

---

## Documentation

Additional architecture documentation is available in: `md-docs/Stack.md`

This includes:

- JWT authentication flow
- Request lifecycle
- Component dependencies
- High-level architecture diagrams

---

## Logging

Logs are written using **Monolog**.

Location: `storage/logs/app.log`

---

## Future Improvements

Planned improvements include:

- Request validation layer
- Refresh token persistence
- Redis caching
- Pagination support
- Enhanced rate limiting
- Automated API documentation generation

---

## License

Internal project for SITizen Review.
