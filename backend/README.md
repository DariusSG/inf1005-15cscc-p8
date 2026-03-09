# Backend — SITizen Review

Purpose: Holds the PHP (Slim 4) backend for SITizen Review. Implements RESTful endpoints, middleware (authentication, admin, toxicity filter, redaction), and services (AuthService, GhostNameService, SentimentService, FileUploadService, StudyGuideExportService).

Key paths:

- public/index.php — Slim entrypoint
- src/App/Controllers — route controllers
- src/App/Middleware — middleware implementations
- src/App/Services — domain services
- config/ — database.php, jwt.php
- storage/uploads — sanitized uploads (syllabus, rubrics)

Quick TODO

- [ ] Add composer.json and install dependencies
- [ ] Implement AuthService (register/login, JWT)
- [ ] Implement VerifiedSITizenMiddleware and AdminMiddleware
- [ ] Create DB migrations and seeders (database/schema.sql)
- [ ] Wire file upload sanitization and redaction flow
