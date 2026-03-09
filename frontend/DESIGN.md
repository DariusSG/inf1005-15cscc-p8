# SITizen Review — Webpage Design

## Purpose

Design system and page-level design guidance for the SITizen Review SPA (mobile-first, PWA-ready). This document captures color tokens, typography, layout grid, component specs, interaction patterns, and accessibility requirements so frontend engineers and designers implement consistent, performant UI.

---

## Brand & Visual Tokens

- Base background (dark): #121212 (sit-dark)
- Primary (accent): #D62B2B (sit-red)
- Surface: #1E1E1E
- Border: #2A2A2A
- Muted text: #9CA3AF
- Success: #10B981
- Warning: #F59E0B
- Error: #EF4444
- Typography: Inter (system-fallback: sans-serif)
- Monospace: JetBrains Mono (for code/snippets)
- Corner radius: 10px (cards), 6px (controls)
- Elevation: subtle tonal differences + box-shadow for modals

---

## Grid & Breakpoints

- Mobile-first responsive rules (Tailwind defaults):
  - xs (default) <640px — single-column
  - sm ≥640px — single-column wide cards
  - md ≥768px — two-column templates (profile/detail)
  - lg ≥1024px — three-column layout (dashboard)
- Container max-widths: md:720px; lg:1024px; xl:1280px
- Gutter: 16px mobile, 24px desktop

---

## Page Templates

1. Home / Trending
   - Hero search bar at top (sticky), trending modules list (card grid), bottom nav on mobile
2. Module Search / Results
   - Search field + filters drawer (mobile FAB), results list with Pacer mini-gauge per item
3. Professor Profile
   - Header: Professor name, cluster, ghost-friendly summary
   - Left pane: PacerGauge + ProfessorSkillRadar
   - Middle pane: Recent reviews feed with weekly timeline entries
   - Right pane (md+): IWSP skills, Trending modules, Study Guide export
4. Review Form
   - Multipart upload zone (drag + browse), structured Pacer metric inputs (1–5 sliders), weekly notes collapsed/expandable
5. WarRoom (compare)
   - Split pane (stacked on mobile) with differential badges and side-by-side radars
6. Admin Dashboard
   - Moderation queue table, user management, audit-log search

---

## Core Component Specs

### PacerGauge (SVG speedometer)

- Data: velocityScore (1–5) float
- Visual: 240° arc, 5 color segments (green→red), needle pivot at center-bottom
- Sizes: responsive viewBox; min 120×80 mobile; 240×160 desktop
- Motion: smooth rotate transition; follow prefers-reduced-motion
- Labels: 1: Chill, 5: No Sleep
- Accessibility: provide aria-label with numeric value and textual summary (e.g., "Velocity: 4 — fast")

### ProfessorSkillRadar (hexagonal spider)

- Axes: Approachability, Industry Exp, Grading Fairness, Feedback Speed, Technical Depth, Pacing
- Data: 6 values 1–5; support two datasets for comparison
- Render: responsive SVG, tooltips on tap/focus, polygon fill with 0.2 alpha
- Min interaction size: 280×280 (collapsed to 220×220 on very small screens)

### WeeklyTimeline (13 nodes)

- Data: weekly_breakdown JSON map keys 1–13 → string summary or intensity score
- Behavior: horizontal on mobile with scroll-snap; clickable nodes show modal/popover with comments
- Touch target: 44×44px; color intensity mapped to pain-level

### WarRoom (split-pane compare)

- Desktop: grid-cols-2; mobile: stacked with tab toggle
- Differential badges: show delta numeric + color (green positive for student experience)
- Provide CSV export of compared metrics (admin only)

### Review Form

- Fields: module selector (autocomplete), offering selector (trimester/year), pacer sliders, weekly notes, optional grade, vibe tags (multi-select), file upload (PDF/IMG)
- Upload: show sanitized preview, filename, and redaction status
- Validation: pacer fields required; review_body min 30 chars
- Submission UX: optimistic UI with local pending state; show moderation flag if toxicity detected

### DarkModeToggle

- Persist to localStorage key `sit-theme` ('dark'|'light')
- Toggle applies `class="dark"` on document element
- Animate icon only if not reduced-motion

### BottomNavBar & MobileDrawer

- BottomNavBar: 4 icon buttons (Home, Search, My Reviews, Profile), visible ≤md
- MobileDrawer: slide-in panel from bottom, traps focus while open; close on backdrop tap

---

## Interaction Patterns

- All list-item navigation should be instant (client-side routing) with skeleton loaders for API fetches
- Optimistic updates: when user posts review, show it in UI with `pending` badge and reconcile on server acknowledgement
- Moderation flow: flagged = hidden from public until admin approves; user notified by in-app message and email (if verified)

---

## Accessibility & Internationalisation

- WCAG 2.1 AA minimum
- Color contrast: text should meet AA contrast on dark surfaces
- All interactive elements keyboard-focusable; use `:focus-visible` styles
- Localize copy via simple key/value JSON bundles; timestamps show `Asia/Singapore` locale by default
- Provide alt text for uploaded images and generated thumbnails

---

## Performance & PWA Notes

- Keep JS bundles small; build with Vite and enable code-splitting for routes
- Cache static assets via service worker (CacheFirst); API results use StaleWhileRevalidate where safe
- Service worker should not cache POST responses; queue offline submissions in IndexedDB (deferred sync)

---

## API Hooks (component → endpoint)

- Module search: GET /api/modules/search?q={query}
- Trending modules: GET /api/modules/trending
- Review submit: POST /api/reviews (multipart/form-data)
- Professor profile: GET /api/professors/{id}
- Sentiment trend: GET /api/professors/{id}/sentiment-trend
- WarRoom compare: GET /api/war-room/compare?module={code}&prof_a={id}&prof_b={id}&year={year}
- Admin: GET /api/admin/reviews/flagged, PATCH /api/admin/reviews/{id}/publish

---

## Assets & Icons

- Provide maskable icons in 192/512 sizes for PWA
- Use SVG icons for UI (Heroicons or custom SIT set), keep them monochrome and accented with `sit-red` on active state
- Store screenshots and sample uploads under `/frontend/public/screenshots/`

---

## Deliverables & Handoff

- Deliver a living `frontend/DESIGN.md` (this file)
- Provide component sketches (SVG wireframes) in `frontend/design/` before implementation
- Maintain a small token-based component library (vanilla JS modules + CSS classes) to reuse PacerGauge, Radar, Timeline

---

Version: 1.0 — created for initial Sprint 1 implementation
