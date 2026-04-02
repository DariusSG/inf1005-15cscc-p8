# Project Report Revision TODO

This file contains the full checklist for revising `INF1005 Project Report (Team P8).pdf`.

## High-priority

- [x] Read and extract full report text from [`docs/INF1005 Project Report (Team P8).pdf`](docs/INF1005 Project Report (Team P8).pdf:1)
- [x] Identify missing content (abstract, org details, goals, admin perms, testing credentials, individual contributions)
- [x] Provide annotated review (delivered)
- [ ] Add Abstract (3–5 sentences)
- [ ] Add Executive Summary (1 paragraph)
- [ ] Replace unresolved placeholders with concrete text or assign TODOs with owners and deadlines
- [ ] Fill Individual Contributions (role, responsibilities, hours, deliverables) for each team member
- [ ] Populate Testing Credentials and instructions to run test fixtures

## Technical content

- [ ] Add architecture diagram (frontend, API, auth, DB)
- [ ] Write concise Data Flow paragraph and sequence diagram
- [ ] Add ER diagram and table listing key tables and columns
- [ ] Document JWT lifetimes and refresh-token handling (15m access / 7d refresh as used)
- [ ] Document server-side sanitization and validation points (VerificationService)
- [ ] Add explicit Site Moderation permissions and workflows

## Appendices and references

- [ ] Add API endpoints appendix (path, method, auth, description) with sample curl commands
- [ ] Add list of files referenced in the report (backend and frontend entry points)
- [ ] Add screenshots or UI wireframes for major pages (Home, Module Detail, Help Request)

## Formatting and polishing

- [ ] Standardize headings and numbering; update Table of Contents
- [ ] Remove colloquial language and replace with formal tone
- [ ] Add page numbers and finalize layout
- [ ] Proofread for grammar and clarity

## Finalization

- [ ] Peer review for technical accuracy
- [ ] Export revised PDF to `docs/INF1005 Project Report (Team P8).pdf`
- [ ] Create a changelog summarizing edits and rationale (to include in repo)

## Notes

- Files to reference while writing: [`backend/public/index.php`](backend/public/index.php:1), [`backend/app/Services/VerificationService.php`](backend/app/Services/VerificationService.php:1), [`frontend/src/main.jsx`](frontend/src/main.jsx:1)
- For diagrams, generate simple block diagrams and ER diagrams and include as images in the report.
