# Database

Purpose: Store SQL DDL and seed data for MySQL 8.0.

Files:

- schema.sql — canonical DDL for all tables
- seeds/ — CSV/SQL seed files for initial modules/professors

Quick TODO

- [ ] Finalize `schema.sql` (users, modules, reviews, audit_logs)
- [ ] Create seed files for clusters, joint_degree_partners, iwsp_skills
- [ ] Provide migration scripts compatible with Docker init (docker-entrypoint-initdb.d)
