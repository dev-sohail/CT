# The CTLabs Personal Software Ecosystem
## Final Master Plan v2.0 (Updated 2026-08-16)
### A 10–20 Year Build Plan for PHP/Laravel + Next.js

> **Codename:** CTLabs
> **Date:** August 2026
> **Status:** v2.0 — living document, updated as modules ship
> **Objective:** Build an integrated, offline-first, modular personal software platform that compounds in value over 10–20 years.

---

## 0. Current Implementation Status (as of 2026-08-16)

CTLabs has moved from planning to a working monorepo. The foundation and the first fully functional domain module are **built, tested, and verified end-to-end**. Progress is tracked in Section 3 (catalog Status column) and Section 5 (roadmap).

### 0.1 What Exists Now

**Monorepo:** `/opt/lampp/htdocs/personal_platform/ctlab/`

```
ctlab/
├── apps/
│   ├── api/                          # ONE Laravel modular monolith (PHP 8.2 / Laravel 10)
│   │   ├── app/Domains/              # 106 domain folders scaffolded, module-prefixed
│   │   │   ├── 01_CoreIdentityAndAccessKernel   ✅ IMPLEMENTED
│   │   │   ├── 02_SharedRESTApiGateway          ✅ IMPLEMENTED
│   │   │   └── 26_SecondBrainPersonalWiki       ✅ IMPLEMENTED
│   │   │   └── (all others = empty scaffolds, ◻ Planned)
│   │   └── database/migrations/      # 13 wiki migrations on 2026-08-16
│   └── web/                          # ONE Next.js 14 app, static export (out/), route groups
│       └── app/(wiki)/               # Second Brain UI — 12 exported routes
├── packages/                         # (empty for now)
├── infra/                            # (empty — using XAMPP/Apache locally)
└── docs/
```

**Implemented domains (3 of 106):**

| Domain | What it does | Status |
|---|---|---|
| #1 Core Identity & Access Kernel | Registration, login, Sanctum SPA auth (Bearer token in localStorage `ctlab_token`), RBAC roles/permissions, model policies | ✅ Done + verified |
| #2 Shared REST API Gateway | `/api/v1` conventions, JSON envelope `{data, meta, errors}`, pagination, global exception handler, eager-loading conventions | ✅ Done + verified |
| #26 Second Brain / Personal Wiki | Workspaces → notebooks → sections → pages hierarchy, block-based editor (autosave), read mode, favorites, page versions, backlinks, templates, trash, projects/questions/references/tasks/reviews | ✅ Done + verified |

**Verified end-to-end (Playwright, 2026-08-16):** 25/25 E2E checks pass with **zero** JS console or page errors — login, navigation, block editing, autosave persistence via API, favorites toggle, version snapshot, templates, trash, and all five engines (projects, questions, references, tasks, reviews).

**Build/CI state:** `npm run build` (Next.js static export) passes; all 12 wiki routes exported and served by Apache at `http://lab.ct.local` (HTTP 200). Backend typecheck passes (`tsc --noEmit --incremental false`).

### 0.2 Environment & Runbook

| Item | Value |
|---|---|
| Local web root | `/opt/lampp/htdocs/personal_platform/ctlab` (XAMPP/LAMPP, Apache vhost `lab.ct.local`) |
| API app | `ctlab/apps/api` — Laravel 10, PHP 8.2 (`/opt/lampp/bin/php`), MySQL 8 on 127.0.0.1, DB `ctlab` |
| Frontend | `ctlab/apps/web` — Next.js 14.2, TypeScript strict, Tailwind, static export to `out/` |
| Owner login | `owner@lab.ct.local` / `admin123` (changed 2026-08-16) |
| API auth | `POST /api/v1/login` → Bearer token → stored in localStorage `ctlab_token` |
| Build | `sudo -H env PATH="/home/dev-so/.local/bin:/usr/bin:/bin" bash -c "cd .../apps/web && npm run build"` |
| Backend | All controllers return `{data, meta, errors}`; routes under `/api/v1/` |

### 0.3 History Since v1.0

- **2026-08-16 — Domain module layout created:** all 106 catalog projects scaffolded as DDD folders under `app/Domains/`, named `NN_Name` to preserve catalog numbering.
- **2026-08-16 — Second Brain (#26) ported and shipped:** the previous standalone "Personal Education OS" app (Vue 3 / Laravel, Dockerized) was **ported into CTLabs natively** as domain #26 (Laravel DDD backend + React/Next.js frontend), verified 25/25 E2E, and the old standalone project was **removed from the platform** (Docker stack stopped; legacy data dump preserved at `archives/education_os_dump_2026-08-16.sql`).
- **2026-08-16 — Test data wiped:** all 14 `wiki_*` tables truncated after verification so the app starts clean. Owner password reset to `admin123` (original seed hash had stopped matching).
- **Frontend note:** editor logic lives in client bundles (slash menu, blocks/sync); wiki pages are query-param routed (`/wiki/pages?id=N`).

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Architecture Overview](#2-architecture-overview)
3. [Complete Project Catalog (107 Projects)](#3-complete-project-catalog-107-projects)
4. [Top 20 Deep Dive (Full Specification)](#4-top-20-deep-dive-full-specification)
5. [Build Roadmap](#5-build-roadmap)
6. [Integration Model](#6-integration-model)
7. [Technology Stack Reference](#7-technology-stack-reference)
8. [Getting Started: Immediate Action Items](#8-getting-started-immediate-action-items)
9. [Appendix A: Ranking Methodology](#appendix-a-ranking-methodology)
10. [Appendix B: Excluded Project Types](#appendix-b-excluded-project-types)

---


## 1. Executive Summary

This document defines the complete architecture, project catalog, and construction roadmap for **CTLabs** — a personal software ecosystem designed to be built and used over 10–20 years. Unlike a portfolio of disconnected apps, CTLabs is a single, cohesive platform where every module shares common infrastructure, design patterns, and data layers.

### Core Philosophy
- **One platform, many capabilities.** Not 100 apps — one modular monolith with 100 domain modules.
- **Offline-first.** Your data lives with you. Connectivity is optional, not mandatory.
- **Rule-based, not AI-based.** Every automation is deterministic, explainable, and fully under your control.
- **Compounding value.** The longer you use it, the more data you accumulate, and the more valuable the cross-module insights become.
- **Built to be extracted.** Any module can be cut out along clean domain seams and turned into a SaaS or open-source product later.

### Target Developer Profile
- **Stack:** PHP 8+ / Laravel backend, Next.js (React) frontend, MySQL/PostgreSQL, Docker, REST APIs.
- **Goals:** Master Laravel, Clean Architecture, DDD, API design, DevOps, and scalable backend patterns.
- **Context:** Solo developer, building primarily for personal use, with optional future commercialization.

### Key Metrics
- **107 total projects** (including 15 kernel infrastructure projects).
- **Top 20 projects** identified for highest lifetime ROI.
- **7 build phases** from foundation to analytics layer.
- **15 kernel services** that make every downstream project 3–5x faster to build.

---

## 2. Architecture Overview

### 2.1 The Modular Monolith Decision

**Decision:** Build one Laravel application as a **modular monolith**, not microservices.

**Rationale:**  
For a solo developer maintaining a platform for 20 years, true microservices are a tax you cannot afford. Every deployable unit requires service discovery, distributed tracing, inter-service authentication, and independent deployment pipelines. Alone, you would spend more time operating infrastructure than building software that improves your life.

**Instead:**
- Each "project" in the catalog is a **domain module** inside a single Laravel app — its own models, migrations, services, controllers, policies, and tests, organized under `app/Domains/*`.
- All modules share **one database, one auth system, one deployment pipeline**.
- Domain boundaries are clean enough that **any module can be extracted later** if it becomes a SaaS product.
- Frontend follows the same logic: **one Next.js app**, route-grouped by domain, sharing one component library, one auth session, and one design system.

**Result:** The difference between "100 apps" and "one platform with 100 capabilities." The latter compounds in value; the former fragments it.

### 2.2 The Kernel Services

These are not "projects" you build for their own sake — they are infrastructure every other module consumes. Build these first, and everything downstream becomes cheaper.

| # | Kernel Service | What It Does | Used By |
|---|----------------|--------------|---------|
| 1 | **Identity & Access** | Auth (Sanctum), sessions, RBAC/policies | Everything |
| 2 | **Shared REST API Gateway** | Versioned API conventions, response envelope, rate limiting | Everything |
| 3 | **Notification Hub + Event Bus** | In-app, email, web-push notifications; domain events | Habits, Finance, Home Maintenance, CRM, etc. |
| 4 | **File & Document Vault** | Versioned file storage abstraction (local now, S3 later) | Documents, Medical, Tax, Receipts, Knowledge |
| 5 | **Unified Search Engine** | Rule-based inverted index across all modules | Second Brain, Documents, CRM, Bookmarks |
| 6 | **Calendar & Scheduling Kernel** | Recurring events, conflict detection, iCal import/export | Planner, Habits, Home Maintenance, Medical |
| 7 | **Contacts & Relationship Graph** | Single source of truth for people + relationship metadata | CRM, Family Records, Gift Planner, Emergency Info |
| 8 | **Tags & Categorization Engine** | Polymorphic tagging usable by any model | Nearly everything |
| 9 | **Audit Trail / Activity Timeline** | Append-only event log per entity | Everything with history value |
| 10 | **Settings & Preferences Service** | Per-user, per-module config with sane defaults | Everything |
| 11 | **Rule Engine & Workflow Builder** | Trigger → Condition → Action primitives (IFTTT-style) | Habits, Finance alerts, Home Maintenance |
| 12 | **Dashboard & Widget Framework** | Any module registers a widget; Life OS Home renders them | Personal Dashboard, every tracker |
| 13 | **Import/Export Service** | CSV/JSON import-export contract per module | Everything (data portability, backups) |
| 14 | **Offline Sync Engine** | Timestamp/dirty-flag conflict resolution for PWA modules | Any offline-first module |
| 15 | **Plugin/Module Registry System** | Module auto-discovery, manifest system | Future extensibility |

### 2.3 Monorepo Structure

```
ctlab/
├── apps/
│   ├── api/                          # ONE Laravel modular monolith
│   │   ├── app/
│   │   │   ├── Domains/
│   │   │   │   ├── Identity/         # kernel
│   │   │   │   ├── Notifications/    # kernel
│   │   │   │   ├── Files/            # kernel
│   │   │   │   ├── Search/           # kernel
│   │   │   │   ├── Tags/             # kernel
│   │   │   │   ├── Calendar/         # kernel
│   │   │   │   ├── Contacts/         # kernel
│   │   │   │   ├── Audit/            # kernel
│   │   │   │   ├── Settings/         # kernel
│   │   │   │   ├── Rules/            # kernel
│   │   │   │   ├── Planner/          # domain module
│   │   │   │   ├── Habits/           # domain module
│   │   │   │   ├── Finance/          # domain module
│   │   │   │   ├── Health/           # domain module
│   │   │   │   ├── Knowledge/        # domain module
│   │   │   │   ├── CRM/              # domain module
│   │   │   │   ├── DevOps/           # domain module
│   │   │   │   └── ...               # one folder per catalog project
│   │   │   └── Support/              # shared kernel base classes
│   │   ├── routes/api.php            # versioned REST surface (/api/v1/...)
│   │   └── database/migrations/      # one schema, module-prefixed tables
│   └── web/                          # ONE Next.js app
│       ├── app/(dashboard)/
│       ├── app/(planner)/
│       ├── app/(finance)/
│       └── app/(crm)/...
├── packages/                         # Independently versioned, extractable
│   ├── php/
│   │   └── ctlab-support/            # DDD building blocks
│   └── js/
│       ├── ctlab-ui/                 # shared React component library
│       ├── ctlab-theme/              # design tokens / theme system
│       ├── ctlab-widgets/            # dashboard widget contracts
│       └── ctlab-api-client/         # typed client, generated from OpenAPI
├── infra/
│   ├── docker/                       # single docker-compose
│   ├── nginx/
│   └── ci/                           # GitHub Actions
└── docs/
    └── adr/                          # architecture decision records
```

**Why one schema with module-prefixed tables:** Cross-module joins (e.g., "show me expenses tagged with a Habit") are trivial and fast. Database-per-module only pays off at a scale you will not reach as one user.

### 2.4 Reusable Packages to Build First

Build these *before* your first tracker app. They are boring, but they are the reason project #30 takes 4 days instead of 4 weeks.

1. **`ctlab-support` (PHP)** — Base `Repository`, `Service`, and `Action` classes; DTO base with array/JSON casting; standard API Resource + pagination envelope; Policy base class; `HasTags`, `HasAudit`, `Taggable` traits. Every domain module extends this. This is where you practice Clean Architecture once, correctly — then never think about boilerplate again.

2. **`ctlab-auth`** — Sanctum-based SSO across the single Next.js app, RBAC with cached permission resolution, policy registration per domain.

3. **`ctlab-rules`** — The automation kernel: `Trigger`, `Condition`, `Action` interfaces plus `RuleEngine::evaluate()`. Habits, Finance alerts, Home Maintenance, and Notifications all consume this.

4. **`ctlab-widgets`** — A `WidgetProvider` interface any domain module implements to register a card on the Life OS Home dashboard. Build the Personal Dashboard against this contract from day one.

5. **`ctlab-ui` + `ctlab-theme` (React/Tailwind)** — Button, Input, Modal, Table, DataGrid, Card, Toast, Form primitives. Fork or extend your existing CyberTirah design system into `ctlab-theme`.

6. **`ctlab-search`** — Deterministic inverted-index table (`searchable_type`, `searchable_id`, `token`, `weight`) populated via model observers, queried with ranked SQL — no ML, fully explainable.

7. **`ctlab-sync`** — Dirty-flag + `updated_at` conflict resolution contract for PWA offline-first modules (service worker + IndexedDB queue).


---

## 3. Complete Project Catalog (107 Projects)

**Legend:** ★ = Top 20 (fully spec'd in Section 4). Difficulty and time assume solo development reusing kernel services as they come online. Estimates drop sharply after Phase 1.

### 3.1 Ecosystem Kernel (15)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 1★ | Core Identity & Access Kernel | Auth, SSO, RBAC for the whole platform | Advanced | 4–6 wks | — | ✅ DONE |
| 2★ | Shared REST API Gateway | Versioned API conventions, response envelope, rate limiting | Intermediate | 2–3 wks | ctlab-support | ✅ DONE |
| 3★ | Notification Hub & Event Bus | In-app/email/push notifications, domain event dispatch | Advanced | 3–4 wks | Identity | ◻ Planned |
| 4★ | File & Document Vault | Versioned file storage abstraction | Intermediate | 2–3 wks | Identity | ◻ Planned |
| 5★ | Unified Search Engine | Rule-based cross-module search | Advanced | 4–5 wks | Tags | ◻ Planned |
| 6★ | Calendar & Scheduling Kernel | Recurring events, conflict detection, iCal | Advanced | 4–6 wks | Identity, Notifications | ◻ Planned |
| 7★ | Contacts & Relationship Graph Kernel | Canonical person/relationship store | Intermediate | 3 wks | Identity, Tags | ◻ Planned |
| 8 | Tagging & Categorization Engine | Polymorphic tags/categories | Beginner | 1–2 wks | — | ◻ Planned |
| 9 | Audit Trail & Activity Timeline | Append-only per-entity event log | Intermediate | 2 wks | Identity | ◻ Planned |
| 10 | Settings & Preferences Service | Per-user/module config store | Beginner | 1 wk | Identity | ◻ Planned |
| 11 | Import/Export & Data Portability Service | Universal CSV/JSON contract | Intermediate | 2 wks | File Vault | ◻ Planned |
| 12★ | Dashboard & Widget Framework | Widget registry + render pipeline | Advanced | 3–4 wks | Identity | ◻ Planned |
| 13 | UI Component Library & Theme System | Shared React/Tailwind design system | Intermediate | 3–4 wks | — | ◻ Planned |
| 14 | Plugin/Module Registry System | Module auto-discovery, manifest system | Advanced | 3 wks | ctlab-support | ◻ Planned |
| 15 | Offline Sync Engine | PWA conflict resolution protocol | Advanced | 4–5 wks | Identity, API Gateway | ◻ Planned |

### 3.2 Automation Core (3)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 16★ | Rule Engine & Workflow Builder | Trigger→condition→action automation kernel | Advanced | 5–6 wks | Event Bus, Notifications | ◻ Planned |
| 17 | Scheduled Task & Cron Manager | Visual crontab across all modules | Intermediate | 2 wks | Rule Engine | ◻ Planned |
| 18 | File Automation Watcher | Watch/organize files by rule sets | Intermediate | 2 wks | File Vault, Rule Engine | ◻ Planned |

### 3.3 Productivity & Time Management (7)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 19★ | Unified Planner Engine | Daily→weekly→monthly→quarterly→annual views over one task/event model | Advanced | 4–5 wks | Calendar, Tags | ◻ Planned |
| 20★ | Goal Hierarchy & OKR Tracker | Life goal → yearly → quarterly → weekly → daily task tree | Advanced | 3–4 wks | Planner, Audit | ◻ Planned |
| 21 | Time Audit & Time-Block Analyzer | Passive time logging + pattern analytics | Intermediate | 3 wks | Calendar | ◻ Planned |
| 22★ | Personal Dashboard / Life OS Home | Single home screen aggregating every widget | Intermediate | 2–3 wks | Widget Framework | ◻ Planned |
| 23 | Work Shift & Schedule Manager | Freelance/hourly shift planning, conflict detection | Intermediate | 2 wks | Calendar | ◻ Planned |
| 24 | Project Roadmap Planner | Gantt-style personal & client project tracking | Advanced | 3–4 wks | Planner, Tags | ◻ Planned |
| 25 | Productivity Insights Engine | Rule-based completion-pattern analytics | Intermediate | 3 wks | Planner, Audit | ◻ Planned |

### 3.4 Knowledge & Learning (8)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 26★ | Second Brain / Personal Wiki | Linked notes with backlink graph | Advanced | 4–5 wks | Search, Tags | ✅ DONE |
| 27 | Study & Course Tracker | Curricula, SM-2 spaced-repetition scheduling | Intermediate | 3 wks | Calendar | ◻ Planned |
| 28 | Certification & Skill Roadmap Tracker | Skill trees, expiry/renewal tracking | Beginner | 1–2 wks | Calendar | ◻ Planned |
| 29 | Research & PDF Annotation Manager | Paper library with highlight/annotation storage | Intermediate | 3 wks | File Vault | ◻ Planned |
| 30 | Flashcard Engine (SM-2) | Rule-based spaced repetition | Intermediate | 2–3 wks | — | ◻ Planned |
| 31 | Bookmark & Read-Later Archive | Saved links with full-text local archive | Intermediate | 2 wks | Search, Tags | ◻ Planned |
| 32 | Code Snippet Manager & Personal Package Index | Private snippet + Composer/npm package catalog | Beginner | 2 wks | Search, Tags | ◻ Planned |
| 33 | Learning Analytics Dashboard | Aggregates study time, retention curves | Intermediate | 2 wks | Study Tracker, Widgets | ◻ Planned |

### 3.5 Personal Organization (6)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 34 | Document Archive & Version Vault | Versioned personal document storage | Intermediate | 2 wks | File Vault | ◻ Planned |
| 35 | Digital Media Library | Books/movies/music metadata catalog | Beginner | 2 wks | Tags | ◻ Planned |
| 36 | QR Asset Tagging System | Physical-item QR tags linked to inventory records | Intermediate | 2 wks | File Vault | ◻ Planned |
| 37 | Secrets, Password & 2FA Vault | Self-hosted encrypted credential store | Advanced | 3–4 wks | Identity | ◻ Planned |
| 38 | Subscription & Recurring Payment Tracker | Renewal dates, cost trend, cancellation reminders | Beginner | 1–2 wks | Calendar, Notifications | ◻ Planned |
| 39 | Receipt & Warranty Archive | Scanned receipts + warranty expiry alerts | Beginner | 2 wks | File Vault, Notifications | ◻ Planned |

### 3.6 Goals, Habits & Self-Improvement (6)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 40★ | Habit Tracking Engine | Streaks, rule-based reminders, correlation analytics | Intermediate | 3 wks | Rule Engine, Calendar | ◻ Planned |
| 41★ | Journal & Structured Review System | Daily/weekly/monthly/annual review templates | Intermediate | 2–3 wks | Calendar, Tags | ◻ Planned |
| 42 | Decision Journal | Structured decision logs with scheduled outcome review | Beginner | 1–2 wks | Calendar, Notifications | ◻ Planned |
| 43 | Personal Scorecard / Life KPI Tracker | Cross-domain KPI rollup | Intermediate | 2 wks | Analytics kernel | ◻ Planned |
| 44 | Vision Board & Bucket List Manager | Long-horizon aspiration tracking | Beginner | 1 wk | Tags | ◻ Planned |
| 45 | Life Timeline & History Archive | Major life-event milestone graph | Beginner | 2 wks | Calendar, File Vault | ◻ Planned |

### 3.7 Health & Wellness (7)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 46 | Workout & Training Planner | Periodized training plans, progressive-overload rules | Intermediate | 3 wks | Calendar, Rule Engine | ◻ Planned |
| 47 | Nutrition & Meal Planner | Macro/calorie rule engine, recipe DB | Intermediate | 3 wks | — | ◻ Planned |
| 48 | Sleep & Recovery Tracker | Manual sleep logs, debt calculation | Beginner | 1–2 wks | — | ◻ Planned |
| 49 | Medication & Prescription Tracker | Refill reminders, static interaction warnings | Intermediate | 2 wks | Notifications, Calendar | ◻ Planned |
| 50 | Medical History & Visit Log | Appointments, providers, visit notes | Beginner | 2 wks | Calendar, File Vault | ◻ Planned |
| 51 | Symptom & Body Metrics Tracker | Logging + threshold-based flags (not diagnostic) | Intermediate | 2 wks | Rule Engine | ◻ Planned |
| 52 | Hydration & Movement Reminder Engine | Water intake + stretch-break reminders | Beginner | 1 wk | Rule Engine, Notifications | ◻ Planned |

### 3.8 Finance (8)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 53★ | Expense Tracking & Budgeting Engine | Envelope-budgeting rule engine | Advanced | 4 wks | Rule Engine, Tags | ◻ Planned |
| 54 | Bill Payment & Reminder Engine | Due-date tracking, escalating reminders | Beginner | 1–2 wks | Notifications, Calendar | ◻ Planned |
| 55 | Savings & Financial Goal Planner | Target-based savings with projection math | Beginner | 2 wks | Expense Engine | ◻ Planned |
| 56 | Investment & Net Worth Tracker | Manual entry + optional market-data API | Intermediate | 3 wks | Expense Engine | ◻ Planned |
| 57 | Loan & Debt Payoff Planner | Avalanche/snowball payoff rule engine | Intermediate | 2 wks | Expense Engine | ◻ Planned |
| 58 | Insurance Policy Manager | Policy inventory, renewal tracking | Beginner | 1–2 wks | Calendar, File Vault | ◻ Planned |
| 59 | Tax Document Archive | Year-organized tax record vault | Beginner | 1 wk | File Vault | ◻ Planned |
| 60 | Purchase History & Price Tracker | Manual price-history logging, drop alerts | Intermediate | 2 wks | Rule Engine | ◻ Planned |

### 3.9 Home & Property (6)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 61 | Home Maintenance Scheduler | Recurring maintenance by season/interval rules | Intermediate | 2–3 wks | Rule Engine, Calendar | ◻ Planned |
| 62 | Home Improvement Project Planner | Renovation project tracking with budgets | Intermediate | 2 wks | Project Roadmap | ◻ Planned |
| 63 | Appliance & Warranty Inventory | Asset registry with warranty alerts | Beginner | 1 wk | Notifications | ◻ Planned |
| 64 | Utility Usage Tracker | Manual/API meter readings, cost trend charts | Intermediate | 2 wks | Analytics kernel | ◻ Planned |
| 65 | Garden & Plant Care Manager | Watering/fertilizing rule-based schedule | Beginner | 1–2 wks | Rule Engine | ◻ Planned |
| 66 | Household Inventory Manager | Generic asset/possession registry | Beginner | 1–2 wks | QR Tagging | ◻ Planned |

### 3.10 Travel & Transportation (5)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 67 | Trip Planner & Itinerary Builder | Day-by-day itinerary with map API integration | Intermediate | 3 wks | Calendar | ◻ Planned |
| 68 | Travel Journal & Expense Tracker | Trip-scoped journal + expense rollups | Beginner | 2 wks | Journal, Expense Engine | ◻ Planned |
| 69 | Visa & Travel Document Tracker | Expiry tracking for passports/visas | Beginner | 1 wk | Notifications | ◻ Planned |
| 70 | Vehicle & Equipment Maintenance Log | Service history for cars, bikes, equipment | Intermediate | 2 wks | Rule Engine, Notifications | ◻ Planned |
| 71 | Personal Route Planner & Ride Log | Saved routes via map API, distance/elevation stats | Intermediate | 2–3 wks | Trip Planner | ◻ Planned |

### 3.11 Career & Professional Development (5)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 72 | Resume & Portfolio Version Manager | Versioned resume/portfolio content, tailored exports | Intermediate | 2 wks | File Vault | ◻ Planned |
| 73 | Job Application & Interview Tracker | Pipeline stages, follow-up reminders | Beginner | 2 wks | CRM Kernel, Notifications | ◻ Planned |
| 74 | Salary & Achievement Log | Compensation history, brag-document entries | Beginner | 1 wk | Timeline | ◻ Planned |
| 75 | Meeting Notes & Action Item System | Notes linked to action items with owners/due dates | Intermediate | 2–3 wks | Contacts, Calendar | ◻ Planned |
| 76★ | Freelance Client & Invoice Tracker | Client pipeline, time tracking, invoice generation | Advanced | 3–4 wks | CRM Kernel, Contacts | ◻ Planned |

### 3.12 Business & Relationships (6)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 77★ | Personal CRM & Relationship Health Tracker | Contact-frequency rules, reach-out reminders | Advanced | 3–4 wks | Contacts Kernel, Rule Engine | ◻ Planned |
| 78 | Email Follow-up Organizer | IMAP-based rule triage, follow-up flags | Advanced | 3 wks | Contacts, Rule Engine | ◻ Planned |
| 79 | Template & Snippet Manager | Reusable emails/messages/contracts library | Beginner | 1 wk | Search, Tags | ◻ Planned |
| 80 | Birthday, Anniversary & Important Dates Engine | Recurring date reminders | Beginner | 1 wk | Contacts, Calendar | ◻ Planned |
| 81 | Gift Planner | Occasion-linked gift idea tracking with budget | Beginner | 1 wk | Contacts, Important Dates | ◻ Planned |
| 82 | Family Records & Emergency Info Vault | Genealogy + emergency contact/medical info | Intermediate | 2 wks | Contacts, File Vault | ◻ Planned |

### 3.13 Entertainment & Lifestyle (5)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 83 | Unified Media Watch Tracker | Movies/TV/anime — one polymorphic watch-status model | Intermediate | 2–3 wks | Tags | ◻ Planned |
| 84 | Reading Tracker | Book progress, ratings, want-to-read queue | Beginner | 1 wk | Media Tracker | ◻ Planned |
| 85 | Music & Podcast Library | Catalog + episode/listen tracking | Beginner | 1–2 wks | Media Tracker | ◻ Planned |
| 86 | Chess Game Log & Repertoire Tracker | PGN storage, opening repertoire notes | Intermediate | 2 wks | — | ◻ Planned |
| 87 | Wishlist Manager | Cross-category want list (gifts, books, purchases) | Beginner | 1 wk | Tags | ◻ Planned |

### 3.14 Writing & Content (3)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 88 | Writing Project Tracker | Draft versions, word-count goals | Beginner | 1–2 wks | File Vault | ◻ Planned |
| 89 | Blog & Content Publishing Manager | Drafts, publishing calendar, SEO checklist | Intermediate | 2–3 wks | Calendar, Search | ◻ Planned |
| 90 | Idea Inbox & Development Pipeline | Capture → triage → develop workflow | Beginner | 1 wk | Rule Engine | ◻ Planned |

### 3.15 Developer Tools (6)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 91 | API Testing & Documentation Platform | Self-hosted Postman-style tool | Advanced | 4 wks | Identity, File Vault | ◻ Planned |
| 92 | Project Scaffolding Generator | CLI to spin up new Laravel/Next.js modules on your conventions | Intermediate | 2 wks | ctlab-support | ◻ Planned |
| 93 | Database Schema Visualizer | ER-diagram generation from migrations | Intermediate | 2 wks | — | ◻ Planned |
| 94 | Personal Package Registry | Private Composer/npm registry | Advanced | 3 wks | Identity | ◻ Planned |
| 95 | Local Dev Environment Dashboard | Docker container status, ports, logs, one screen | Intermediate | 2–3 wks | DevOps kernel | ◻ Planned |

### 3.16 DevOps & Infrastructure (6)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 96 | Deployment Manager | Git-based one-click deploy pipeline UI | Advanced | 3–4 wks | Server Inventory | ◻ Planned |
| 97 | Docker Environment & Container Dashboard | Live container/resource status | Intermediate | 2 wks | Server Inventory | ◻ Planned |
| 98★ | Server & Homelab Inventory | Asset registry for your homelab hardware/services | Beginner | 1–2 wks | Tags, Audit | ◻ Planned |
| 99 | Server Monitoring Dashboard | Uptime, resource usage, threshold alerts | Advanced | 3–4 wks | Server Inventory, Rule Engine | ◻ Planned |
| 100 | Backup Manager & Scheduler | Automated backup jobs + verification checks | Intermediate | 2–3 wks | Rule Engine, Notifications | ◻ Planned |
| 101 | Domain & SSL Certificate Tracker | Renewal reminders across domains/certs | Beginner | 1 wk | Notifications | ◻ Planned |

### 3.17 Security (2)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 102 | Security Audit Dashboard | Expired certs, stale permissions, weak-password flags across the platform | Advanced | 3 wks | Identity, Rule Engine | ◻ Planned |
| 103 | Device Inventory & Login History | Session/device tracking, anomaly flags | Intermediate | 2 wks | Identity, Audit | ◻ Planned |

### 3.18 Analytics & Insights (3)

| # | Project | Purpose | Difficulty | Est. Time | Reusable Modules | Status |
|---|---------|---------|------------|-----------|------------------|-----------|
| 104★ | Unified Analytics & KPI Dashboard | Cross-project metric aggregation | Advanced | 4 wks | Widgets, every domain | ◻ Planned |
| 105 | Life Statistics Engine | Lifetime totals across every tracked domain | Intermediate | 2 wks | Analytics kernel | ◻ Planned |
| 106 | Custom Report Builder | Drag-drop report designer over any module's data | Advanced | 4 wks | Analytics kernel, Reporting service | ◻ Planned |


---

## 4. Top 20 Deep Dive (Full Specification)

Ordered by build sequence — this order *is* the dependency chain. Each project includes all 23 required fields.

---

### 1. Core Identity & Access Kernel

1. **Project Name:** Core Identity & Access Kernel  
2. **Purpose:** Single sign-on, session management, and role/permission resolution for every app in the ecosystem.  
3. **Problem It Solves:** Without this, every future project reinvents login, and you end up with twenty different passwords for your own software.  
4. **Why It Provides Long-Term Value:** The one piece of infrastructure literally everything else depends on — get it right once, never touch it again except to add features like device management.  
5. **Target Users:** You; future collaborators or SaaS customers if any module is spun out.  
6. **Core Features:** Registration/login, Sanctum SPA auth, password reset, role/permission model, policy registration per domain.  
7. **Advanced Features:** Device/session management, permission caching (Redis), impersonation mode for debugging.  
8. **Future Expansion Ideas:** WebAuthn/passkey support, OAuth provider mode so extracted SaaS modules can use "Login with CTLabs."  
9. **Recommended Architecture:** Clean Architecture — `AuthService` behind an interface, policies per domain, no domain module touches the `users` table directly.  
10. **Recommended Technology Stack:** Laravel Sanctum, Spatie Laravel-Permission (or hand-rolled RBAC for learning), Redis for permission cache.  
11. **Database Design Suggestions:** `users`, `roles`, `permissions`, `role_permission`, `model_has_roles`, `sessions`, `devices`.  
12. **Offline Strategy:** N/A (must be online for auth); cache last-known permission set client-side for read-only offline views.  
13. **Security Considerations:** Argon2id hashing, rate-limited login, CSRF via Sanctum SPA mode, mandatory 2FA option, audit every permission change.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 4–6 weeks.  
16. **Skills You Will Learn:** Auth architecture, RBAC design, security hardening.  
17. **Reusable Components:** Everything else imports this. Nothing imports from it downward.  
18. **Integration With Other Projects:** All 106 other projects.  
19. **Maintenance Effort:** Low once stable — this is the part of the system that should change least.  
20. **Scalability Potential:** Trivially scales to any number of domain modules; the bottleneck is never here.  
21. **SaaS Potential (Optional):** High if extracted as a standalone "auth-as-a-service" for future products.  
22. **Open Source Potential (Optional):** High — a clean Sanctum + RBAC starter kit is genuinely useful to the Laravel community.  
23. **Why It Should Be Built Before Other Projects:** Nothing else can be built without it. Full stop.

---

### 2. Shared REST API Gateway

1. **Project Name:** Shared REST API Gateway  
2. **Purpose:** Consistent API conventions — response envelopes, pagination, versioning, rate limiting — used by every domain module.  
3. **Problem It Solves:** Without a shared convention, every module's API ends up shaped differently, which makes the shared Next.js client (and your own sanity) miserable.  
4. **Why It Provides Long-Term Value:** A stable, versioned contract lets frontend and backend evolve independently for two decades.  
5. **Target Users:** You (as API consumer via Next.js), any future third-party integrator.  
6. **Core Features:** `/api/v1` namespace, standard JSON:API-ish envelope, pagination, sorting/filtering conventions, global exception handler.  
7. **Advanced Features:** Rate limiting per user/route, request/response logging, OpenAPI spec auto-generation.  
8. **Future Expansion Ideas:** GraphQL gateway layer if cross-module queries get complex enough to justify it.  
9. **Recommended Architecture:** Middleware pipeline + API Resource base classes from `ctlab-support`.  
10. **Recommended Technology Stack:** Laravel, `spatie/laravel-query-builder` for filtering conventions, `dedoc/scramble` or similar for OpenAPI generation.  
11. **Database Design Suggestions:** N/A (cross-cutting concern, no dedicated tables beyond request logs).  
12. **Offline Strategy:** Version headers let the offline sync engine detect schema drift safely.  
13. **Security Considerations:** Rate limiting, request validation at the edge, consistent error responses that don't leak stack traces.  
14. **Difficulty:** Intermediate.  
15. **Estimated Development Time:** 2–3 weeks.  
16. **Skills You Will Learn:** API design, versioning strategy, middleware architecture.  
17. **Reusable Components:** Every module's controllers extend this.  
18. **Integration With Other Projects:** All modules; `ctlab-api-client` (typed frontend client) is generated directly from this.  
19. **Maintenance Effort:** Low; touch only when adding cross-cutting concerns.  
20. **Scalability Potential:** High — stateless, horizontally scalable if it ever needs to be.  
21. **SaaS Potential (Optional):** N/A (infrastructure, not a product).  
22. **Open Source Potential (Optional):** Medium — the conventions package is reusable across your other client projects too.  
23. **Why It Should Be Built Before Other Projects:** Every domain module's controllers depend on these conventions; retrofitting them later means touching every module twice.

---

### 3. Notification Hub & Event Bus

1. **Project Name:** Notification Hub & Event Bus  
2. **Purpose:** Central place where domain events get dispatched and turned into in-app, email, or push notifications.  
3. **Problem It Solves:** Without this, "remind me when a habit streak breaks" and "alert me when a bill is due" each get bespoke, duplicated notification code.  
4. **Why It Provides Long-Term Value:** Every future tracker just fires an event; it never has to know *how* you want to be reminded.  
5. **Target Users:** You, across every device.  
6. **Core Features:** Event dispatch, notification channels (database, mail, web push), per-user channel preferences.  
7. **Advanced Features:** Digest/batching rules (don't get 15 separate pings at 9am), do-not-disturb windows, priority levels.  
8. **Future Expansion Ideas:** SMS channel via a provider API, Slack/Discord webhook channel for yourself.  
9. **Recommended Architecture:** Laravel's native event/listener + notification system, wrapped in a `NotificationDispatcher` service so domain modules never call channels directly.  
10. **Recommended Technology Stack:** Laravel Events, Notifications, Queues (Redis), Web Push (VAPID).  
11. **Database Design Suggestions:** `notifications`, `notification_preferences`, `events_log`.  
12. **Offline Strategy:** Queued notifications persist and deliver on reconnect; web push works even when the PWA is closed.  
13. **Security Considerations:** Per-user opt-in/out enforced server-side, signed unsubscribe links for email.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 3–4 weeks.  
16. **Skills You Will Learn:** Event-driven architecture, queues, background jobs.  
17. **Reusable Components:** Consumed by Habits, Finance, Home Maintenance, Bills, almost every tracker.  
18. **Integration With Other Projects:** Rule Engine (rules fire events; events become notifications).  
19. **Maintenance Effort:** Low-medium; occasional new channel additions.  
20. **Scalability Potential:** Queue-backed, scales horizontally with worker count.  
21. **SaaS Potential (Optional):** Medium, as an internal service.  
22. **Open Source Potential (Optional):** Medium — a "domain event → multi-channel notification" package is a common need.  
23. **Why It Should Be Built Before Other Projects:** Habits, Finance alerts, and Home Maintenance all need this on day one; build it before the first tracker, not after.

---

### 4. File & Document Vault

1. **Project Name:** File & Document Vault  
2. **Purpose:** One abstraction for storing, versioning, and retrieving files across every module.  
3. **Problem It Solves:** Documents, receipts, medical records, and research PDFs all need the same underlying capability — don't build it five times.  
4. **Why It Provides Long-Term Value:** As your document count grows across 20 years, having one consistent storage/versioning layer means one backup strategy, one security model, one place to search.  
5. **Target Users:** You, across all modules that handle documents.  
6. **Core Features:** Upload, versioning, folder/tag association, soft-delete with retention.  
7. **Advanced Features:** Automatic thumbnail generation for images/PDFs, checksum-based dedup.  
8. **Future Expansion Ideas:** S3-compatible remote storage swap-in (MinIO on your homelab, or real S3) without touching consumer code.  
9. **Recommended Architecture:** Laravel Filesystem abstraction behind a `DocumentRepository` interface — local disk today, swappable driver later.  
10. **Recommended Technology Stack:** Laravel Filesystem, Intervention Image for thumbnails.  
11. **Database Design Suggestions:** `documents`, `document_versions`, `document_tags`.  
12. **Offline Strategy:** Metadata cached client-side; actual file bytes fetched on demand or pre-cached for PWA modules that need them offline (e.g. Medical records).  
13. **Security Considerations:** Per-file access policy tied to Identity kernel, encrypted-at-rest option for sensitive documents (Tax, Medical).  
14. **Difficulty:** Intermediate.  
15. **Estimated Development Time:** 2–3 weeks.  
16. **Skills You Will Learn:** File handling, storage abstraction, versioning patterns.  
17. **Reusable Components:** Documents, Medical, Tax, Receipts, Research, Resume modules all consume this directly.  
18. **Integration With Other Projects:** Search kernel (indexes file metadata/text), Tags kernel.  
19. **Maintenance Effort:** Low; mainly storage capacity monitoring.  
20. **Scalability Potential:** High — disk-bound, trivially upgradeable.  
21. **SaaS Potential (Optional):** Low standalone; high as embedded infrastructure.  
22. **Open Source Potential (Optional):** Medium.  
23. **Why It Should Be Built Before Other Projects:** Half a dozen Phase 1–2 modules need it immediately.

---

### 5. Unified Search Engine

1. **Project Name:** Unified Search Engine  
2. **Purpose:** One search box that finds anything across every module — notes, documents, contacts, tasks — with fully deterministic ranking.  
3. **Problem It Solves:** As data accumulates over years, "where did I put that" becomes the single biggest time sink without cross-module search.  
4. **Why It Provides Long-Term Value:** Search quality *increases* as more modules index into it — this is the clearest example of a project that gets more valuable with age.  
5. **Target Users:** You, as the primary searcher across your own data.  
6. **Core Features:** Rule-based inverted index (`searchable_type`, `searchable_id`, `token`, `weight`), ranked query endpoint.  
7. **Advanced Features:** Faceted filtering by module/tag/date, saved searches.  
8. **Future Expansion Ideas:** Full-text search via MySQL FULLTEXT or Postgres `tsvector` if the naive index outgrows itself.  
9. **Recommended Architecture:** Observer pattern — each model that wants to be searchable implements a `Searchable` contract; an observer re-indexes on save.  
10. **Recommended Technology Stack:** Laravel model observers, MySQL/Postgres full-text indexes.  
11. **Database Design Suggestions:** `search_index (searchable_type, searchable_id, token, weight)`.  
12. **Offline Strategy:** Cache last N searches client-side; full search requires connectivity.  
13. **Security Considerations:** Search results filtered through the same policies as the underlying records — no leaking data across permission boundaries.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 4–5 weeks.  
16. **Skills You Will Learn:** Indexing strategies, query optimization, observer pattern.  
17. **Reusable Components:** Second Brain, Documents, CRM, Bookmarks all register as searchable.  
18. **Integration With Other Projects:** Tags kernel (tokens can be tag-weighted).  
19. **Maintenance Effort:** Low-medium; re-index performance to watch as data grows.  
20. **Scalability Potential:** Good to hundreds of thousands of records; revisit indexing strategy well beyond that.  
21. **SaaS Potential (Optional):** Low standalone.  
22. **Open Source Potential (Optional):** Medium — "deterministic multi-model search for Laravel" fills a real gap between doing nothing and pulling in Elasticsearch.  
23. **Why It Should Be Built Before Other Projects:** Needed as soon as Second Brain and Documents exist, otherwise those modules are write-only.

---

### 6. Calendar & Scheduling Kernel

1. **Project Name:** Calendar & Scheduling Kernel  
2. **Purpose:** One recurring-event and scheduling engine that every time-based module builds on.  
3. **Problem It Solves:** Planner, Habits, Home Maintenance, Medical, and Bills all need "recurring thing on a schedule with conflict detection" — building it once avoids five buggy reimplementations of RRULE logic.  
4. **Why It Provides Long-Term Value:** Centralized calendar logic means one place to fix timezone bugs, one place to add iCal sync, one place to manage recurring complexity.  
5. **Target Users:** You, via every module that touches dates.  
6. **Core Features:** Events, recurrence rules (RRULE-based), conflict detection, iCal import/export.  
7. **Advanced Features:** Multi-calendar overlay (work/personal/health), free-busy queries.  
8. **Future Expansion Ideas:** Two-way sync with Google/Outlook calendars via their APIs.  
9. **Recommended Architecture:** `CalendarEvent` as a first-class kernel entity; domain modules attach polymorphically rather than each owning their own date logic.  
10. **Recommended Technology Stack:** `spatie/calendar-links` or a dedicated RRULE library, Laravel.  
11. **Database Design Suggestions:** `calendar_events`, `recurrence_rules`, `event_attendees` (future).  
12. **Offline Strategy:** Full offline read/write with sync-engine reconciliation — this is one of the highest-value offline targets.  
13. **Security Considerations:** Standard Identity-kernel policies; calendar sharing (future) needs explicit ACLs.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 4–6 weeks.  
16. **Skills You Will Learn:** Recurrence-rule algorithms, complex date/time handling, sync design.  
17. **Reusable Components:** Planner, Habits, Home Maintenance, Medical, Career all depend on this.  
18. **Integration With Other Projects:** Notifications (event reminders), Offline Sync Engine.  
19. **Maintenance Effort:** Medium — date/timezone edge cases are the classic long-tail bug source.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Low standalone.  
22. **Open Source Potential (Optional):** Medium.  
23. **Why It Should Be Built Before Other Projects:** The Unified Planner (project #19) cannot exist without it.

---

### 7. Contacts & Relationship Graph Kernel

1. **Project Name:** Contacts & Relationship Graph Kernel  
2. **Purpose:** One canonical store of people and their relationships to you.  
3. **Problem It Solves:** Without this, Personal CRM, Family Records, Gift Planner, and Emergency Info each maintain their own duplicate, drifting contact lists.  
4. **Why It Provides Long-Term Value:** One source of truth for people means merge/dedup happens once, relationship history accumulates in one place, and every module references the same person record.  
5. **Target Users:** You, and any module that references people.  
6. **Core Features:** Person records, relationship types, contact methods, relationship-to-you metadata.  
7. **Advanced Features:** Relationship graph queries ("who do I know that also knows X"), interaction frequency tracking.  
8. **Future Expansion Ideas:** vCard import/export, merge/dedup tooling.  
9. **Recommended Architecture:** `Person` as a kernel entity; CRM, Family Records, and Gift Planner all reference it rather than owning their own contact rows.  
10. **Recommended Technology Stack:** Laravel, standard Eloquent relationships.  
11. **Database Design Suggestions:** `people`, `relationships`, `contact_methods`.  
12. **Offline Strategy:** Full offline-capable — contacts are exactly the kind of data you want available with no connection.  
13. **Security Considerations:** Standard policies; extra care since this is inherently sensitive personal data about third parties.  
14. **Difficulty:** Intermediate.  
15. **Estimated Development Time:** 3 weeks.  
16. **Skills You Will Learn:** Graph-shaped relational modeling.  
17. **Reusable Components:** CRM, Family Records, Gift Planner, Job Application Tracker (recruiter contacts), Meeting Notes.  
18. **Integration With Other Projects:** Calendar (birthdays), Notifications (reach-out reminders).  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Low standalone (it's infrastructure).  
22. **Open Source Potential (Optional):** Low-medium.  
23. **Why It Should Be Built Before Other Projects:** Personal CRM (#77) and several Phase-3 modules depend on it existing cleanly first.


---

### 12. Dashboard & Widget Framework

1. **Project Name:** Dashboard & Widget Framework  
2. **Purpose:** A registry contract any module implements to appear on your home screen — the payoff for building the kernel first.  
3. **Problem It Solves:** Without this, "one screen showing everything that matters today" means hand-wiring N different modules into one view, over and over as N grows.  
4. **Why It Provides Long-Term Value:** This is the project that makes the whole ecosystem *feel* like one platform instead of a folder of apps.  
5. **Target Users:** You, as the daily user of the Life OS Home screen.  
6. **Core Features:** `WidgetProvider` interface, widget registry, grid-based render pipeline.  
7. **Advanced Features:** User-configurable layout, per-widget refresh intervals, widget-level permissions.  
8. **Future Expansion Ideas:** Public/shareable read-only dashboard views (e.g. a "life stats" page you can show someone).  
9. **Recommended Architecture:** Interface-driven registry pattern — a module implements `provideWidget(): WidgetData` and is auto-discovered.  
10. **Recommended Technology Stack:** Laravel service container binding + tagged services; Next.js grid layout (`react-grid-layout`).  
11. **Database Design Suggestions:** `widget_registrations`, `user_widget_layout`.  
12. **Offline Strategy:** Widgets cache their last payload for offline display.  
13. **Security Considerations:** Widget data respects the same per-module policies as the underlying data.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 3–4 weeks.  
16. **Skills You Will Learn:** Plugin/registry architecture, dependency injection patterns.  
17. **Reusable Components:** Every tracker module implements this once.  
18. **Integration With Other Projects:** Personal Dashboard (#22), which is just this framework's default view.  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High — adding a widget never touches existing widgets.  
21. **SaaS Potential (Optional):** Low standalone.  
22. **Open Source Potential (Optional):** Medium — a generic "dashboard widget registry for Laravel + Next.js" is a nice reusable OSS package.  
23. **Why It Should Be Built Before Other Projects:** Every tracker built afterward becomes "implement one interface, appear on your home screen for free" — huge compounding payoff the earlier it lands.

---

### 16. Rule Engine & Workflow Builder

1. **Project Name:** Rule Engine & Workflow Builder  
2. **Purpose:** A deterministic trigger → condition → action engine that powers automation across the whole ecosystem.  
3. **Problem It Solves:** "Remind me if I haven't logged a workout in 3 days," "flag if a subscription price increases," "alert if a server's disk is 90% full" — all the same underlying pattern, otherwise reimplemented per-module.  
4. **Why It Provides Long-Term Value:** This is your automation kernel — it's what turns a collection of trackers into something that actively works for you instead of something you have to remember to check.  
5. **Target Users:** You, as the rule author across all domains.  
6. **Core Features:** `Trigger`, `Condition`, `Action` interfaces; rule CRUD; evaluation scheduler.  
7. **Advanced Features:** Composable AND/OR condition chains, rule templates per module, rule execution history/debugging.  
8. **Future Expansion Ideas:** A visual workflow builder UI (drag-and-drop) once the underlying engine is proven.  
9. **Recommended Architecture:** Strategy pattern for conditions/actions; rules stored as structured JSON, evaluated by a scheduled job.  
10. **Recommended Technology Stack:** Laravel Task Scheduling, Queues, a small JSON-logic evaluator (hand-rolled — good DDD practice).  
11. **Database Design Suggestions:** `rules`, `rule_conditions`, `rule_actions`, `rule_execution_log`.  
12. **Offline Strategy:** Rules evaluate server-side only; results sync to offline clients as notifications.  
13. **Security Considerations:** Rules run with the owning user's permission scope only — no cross-user rule execution.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 5–6 weeks.  
16. **Skills You Will Learn:** Rule engine design, the strategy pattern, scheduled job architecture — genuinely meaty CS content.  
17. **Reusable Components:** Habits, Finance alerts, Home Maintenance, Server Monitoring all consume this instead of hardcoding their own "if X then Y" logic.  
18. **Integration With Other Projects:** Notification Hub (rules fire events that become notifications).  
19. **Maintenance Effort:** Medium — this is a piece worth investing in test coverage for, since bugs here cascade.  
20. **Scalability Potential:** Good; evaluation frequency is the main scaling lever.  
21. **SaaS Potential (Optional):** Medium-high — a personal-automation product is a real market (see: IFTTT, Zapier, but self-hosted and rule-based).  
22. **Open Source Potential (Optional):** High.  
23. **Why It Should Be Built Before Other Projects:** Habits (#40) and Expense alerts (#53) are both meaningfully worse without it — build the engine before the first consumer, not bolted on after.

---

### 19. Unified Planner Engine

1. **Project Name:** Unified Planner Engine  
2. **Purpose:** One task/event model with daily, weekly, monthly, quarterly, and annual *views* over it — not five separate planner apps.  
3. **Problem It Solves:** Your original list treated daily/weekly/monthly/annual planners as separate projects; that's five overlapping CRUD apps. One well-designed hierarchical model with rollup views is the actual engineering answer.  
4. **Why It Provides Long-Term Value:** One model means one sync protocol, one offline strategy, one search index — and views that become more powerful as data accumulates.  
5. **Target Users:** You, for daily/weekly/monthly/quarterly/annual planning.  
6. **Core Features:** Tasks with due dates, recurrence, priority, parent/child hierarchy (annual → quarterly → weekly → daily), view-switching UI.  
7. **Advanced Features:** Rollover rules for incomplete tasks, time-blocking against the Calendar kernel.  
8. **Future Expansion Ideas:** Templates for recurring planning rituals (e.g. a standard "Monday weekly review" checklist).  
9. **Recommended Architecture:** A single `PlannerItem` model with a `scope` enum (day/week/month/quarter/year) and `parent_id` self-reference for hierarchy.  
10. **Recommended Technology Stack:** Laravel, Next.js with a calendar/agenda component from `ctlab-ui`.  
11. **Database Design Suggestions:** `planner_items (id, scope, parent_id, due_at, status, ...)`.  
12. **Offline Strategy:** Full offline-first — you'll want to add tasks on your phone with no signal.  
13. **Security Considerations:** Standard per-user policies.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 4–5 weeks.  
16. **Skills You Will Learn:** Hierarchical data modeling, recursive queries, view composition.  
17. **Reusable Components:** Goal Hierarchy (#20) is essentially this model with a different lens.  
18. **Integration With Other Projects:** Calendar kernel, Dashboard widget, Productivity Insights (#25).  
19. **Maintenance Effort:** Low-medium.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Medium.  
22. **Open Source Potential (Optional):** Medium.  
23. **Why It Should Be Built Before Other Projects:** It's the single highest daily-touch app in the entire catalog — build it as soon as the kernel supports it.

---

### 20. Goal Hierarchy & OKR Tracker

1. **Project Name:** Goal Hierarchy & OKR Tracker  
2. **Purpose:** Life goal → yearly → quarterly → weekly → daily task tree, with explicit dependency tracking.  
3. **Problem It Solves:** Daily tasks that don't ladder up to anything are just busywork; this makes the "why" explicit and reviewable.  
4. **Why It Provides Long-Term Value:** A living goal tree that spans years becomes a personal strategic archive — you can look back and see exactly how your priorities evolved.  
5. **Target Users:** You, for personal strategic planning.  
6. **Core Features:** Goal tree, OKR-style objective/key-result pairs, progress rollup from child to parent.  
7. **Advanced Features:** Goal review reminders (ties to Journal #41), historical goal-completion analytics.  
8. **Future Expansion Ideas:** Goal templates (career, health, financial goal patterns you reuse yearly).  
9. **Recommended Architecture:** Adjacency-list tree over the same `Support` base classes as the Planner.  
10. **Recommended Technology Stack:** Laravel, recursive CTE queries (Postgres) or recursive Eloquent (MySQL) for rollups.  
11. **Database Design Suggestions:** `goals (id, parent_id, level, progress, ...)`, `key_results`.  
12. **Offline Strategy:** Read/write offline; rollup calculation on reconnect.  
13. **Security Considerations:** Standard policies.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 3–4 weeks.  
16. **Skills You Will Learn:** Tree data structures, progress-rollup algorithms.  
17. **Reusable Components:** Shares its base model pattern with the Planner Engine.  
18. **Integration With Other Projects:** Planner (#19), Journal reviews (#41), Personal Scorecard (#43).  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Medium.  
22. **Open Source Potential (Optional):** Low-medium.  
23. **Why It Should Be Built Before Other Projects:** Right after the Planner, while the hierarchical-modeling patterns are fresh.

---

### 22. Personal Dashboard / Life OS Home

1. **Project Name:** Personal Dashboard / Life OS Home  
2. **Purpose:** The single screen you open first every day — everything that matters, one glance.  
3. **Problem It Solves:** Without a real home screen, "check on everything" means opening ten apps.  
4. **Why It Provides Long-Term Value:** This is the daily "operating system" interface for your life — the compounding value is in how many modules it can surface without additional work.  
5. **Target Users:** You, every morning.  
6. **Core Features:** Widget grid rendering every registered module (habits due today, budget status, upcoming events, unread notifications).  
7. **Advanced Features:** Configurable layout, "focus mode" that hides everything but today's priorities.  
8. **Future Expansion Ideas:** A read-only "weekly digest" email generated from the same widget data.  
9. **Recommended Architecture:** Thin consumer of the Widget Framework (#12) — deliberately has almost no logic of its own.  
10. **Recommended Technology Stack:** Next.js, `react-grid-layout`, `ctlab-widgets`.  
11. **Database Design Suggestions:** `user_widget_layout` (shared with #12).  
12. **Offline Strategy:** Renders last-cached widget payloads offline.  
13. **Security Considerations:** Inherits per-widget policies.  
14. **Difficulty:** Intermediate.  
15. **Estimated Development Time:** 2–3 weeks.  
16. **Skills You Will Learn:** Frontend composition architecture, dashboard UX design.  
17. **Reusable Components:** N/A — it's the consumer, not the provider.  
18. **Integration With Other Projects:** Every module that registers a widget.  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Low standalone.  
22. **Open Source Potential (Optional):** Low.  
23. **Why It Should Be Built Before Other Projects:** Immediate, visible payoff for the kernel work you've done so far — good morale checkpoint before Phase 2's deeper modules.

---

### 26. Second Brain / Personal Wiki

1. **Project Name:** Second Brain / Personal Wiki  
2. **Purpose:** Linked, backlinked notes — a Zettelkasten-style knowledge base that's genuinely yours.  
3. **Problem It Solves:** Notes scattered across apps, docs, and memory lose their connections; a linked wiki preserves and surfaces them.  
4. **Why It Provides Long-Term Value:** A 20-year-old personal wiki with backlinked entries is an irreplaceable intellectual asset — it captures not just what you knew, but how your thinking connected.  
5. **Target Users:** You, for knowledge work, research, and long-term memory.  
6. **Core Features:** Markdown notes, `[[wiki-links]]` parsed into a backlink graph, tag-based browsing.  
7. **Advanced Features:** Graph visualization of note connections, orphan-note detection (notes nothing links to).  
8. **Future Expansion Ideas:** Public-publish mode for select notes (a personal digital garden).  
9. **Recommended Architecture:** Notes as first-class searchable entities; a link-parsing service builds the `note_links` graph on save.  
10. **Recommended Technology Stack:** Laravel, a Markdown parser (CommonMark), Next.js with a graph-visualization library (`d3` or `recharts`-adjacent).  
11. **Database Design Suggestions:** `notes`, `note_links (from_note_id, to_note_id)`.  
12. **Offline Strategy:** Prime offline-first candidate — full local read/write, sync on reconnect.  
13. **Security Considerations:** Standard policies; consider encryption-at-rest for genuinely private notes.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 4–5 weeks.  
16. **Skills You Will Learn:** Graph data modeling, Markdown parsing, PWA offline patterns.  
17. **Reusable Components:** Feeds the Search kernel directly.  
18. **Integration With Other Projects:** Search (#5), Tags, Bookmark Archive (#31), Research Manager (#29).  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High — this is a project that gets *better* the longer you use it, which is exactly the compounding value your brief asked for.  
21. **SaaS Potential (Optional):** Low (deeply personal by nature).  
22. **Open Source Potential (Optional):** Medium.  
23. **Why It Should Be Built Before Other Projects:** High daily-use value, and it's the proving ground for the Search kernel.


---

### 40. Habit Tracking Engine

1. **Project Name:** Habit Tracking Engine  
2. **Purpose:** Streaks, completion logging, and rule-based reminders for recurring behaviors.  
3. **Problem It Solves:** Paper habit trackers get lost; commercial apps lock your data and charge subscriptions. You need your data, your rules, integrated with your calendar and goals.  
4. **Why It Provides Long-Term Value:** Years of habit data reveal patterns (e.g., "I meditate more consistently when I sleep 7+ hours") that no generic app can surface across your own custom metrics.  
5. **Target Users:** You, for daily self-improvement tracking.  
6. **Core Features:** Habit definitions (frequency, target), daily check-off, streak calculation.  
7. **Advanced Features:** Correlation analytics (does sleep quality track with workout completion?), flexible schedules (e.g. "3x per week," not just daily).  
8. **Future Expansion Ideas:** Habit "chains" (bundle related habits into a morning routine with one check-off).  
9. **Recommended Architecture:** `Habit` + `HabitLog` with a `RuleEngine`-backed reminder ("remind me if not logged by 8pm").  
10. **Recommended Technology Stack:** Laravel, Rule Engine (#16).  
11. **Database Design Suggestions:** `habits`, `habit_logs`, `habit_streaks` (materialized for fast reads).  
12. **Offline Strategy:** Full offline check-off with sync reconciliation — you want to log a habit with no signal.  
13. **Security Considerations:** Standard policies.  
14. **Difficulty:** Intermediate.  
15. **Estimated Development Time:** 3 weeks.  
16. **Skills You Will Learn:** Streak/time-series calculation, rule engine consumption patterns.  
17. **Reusable Components:** Correlation logic reusable by Symptom Tracker, Sleep Tracker.  
18. **Integration With Other Projects:** Rule Engine, Notifications, Personal Scorecard.  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Medium — habit trackers are a proven consumer category.  
22. **Open Source Potential (Optional):** Medium.  
23. **Why It Should Be Built Before Other Projects:** First real proof that Rule Engine + Notifications work end-to-end in a daily-use app.

---

### 41. Journal & Structured Review System

1. **Project Name:** Journal & Structured Review System  
2. **Purpose:** Daily, weekly, monthly, and annual reflection with structured prompts — not a blank text box.  
3. **Problem It Solves:** Blank journals lead to inconsistency; structured prompts ensure you actually reflect on what matters (habits, goals, decisions) rather than just venting.  
4. **Why It Provides Long-Term Value:** A decade of structured reviews becomes a searchable personal history and decision-quality audit trail.  
5. **Target Users:** You, for structured reflection.  
6. **Core Features:** Templated entries per review cadence, prompt library, historical entry browsing.  
7. **Advanced Features:** Review-linking (weekly review auto-surfaces the week's habit/goal data for reflection).  
8. **Future Expansion Ideas:** Yearly "wrapped"-style summary generated from a full year of entries.  
9. **Recommended Architecture:** `JournalEntry` polymorphic by cadence, template engine for prompts.  
10. **Recommended Technology Stack:** Laravel, Markdown storage, Next.js rich-text-lite editor.  
11. **Database Design Suggestions:** `journal_entries`, `journal_templates`.  
12. **Offline Strategy:** Full offline-first — journaling is exactly the kind of thing you don't want blocked by connectivity.  
13. **Security Considerations:** Strong candidate for encryption-at-rest given the personal nature of content.  
14. **Difficulty:** Intermediate.  
15. **Estimated Development Time:** 2–3 weeks.  
16. **Skills You Will Learn:** Template engine design, encryption-at-rest implementation.  
17. **Reusable Components:** Template engine reusable by Decision Journal (#42).  
18. **Integration With Other Projects:** Calendar, Goal Hierarchy (reviews reference goal progress).  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Low-medium.  
22. **Open Source Potential (Optional):** Low (personal by design).  
23. **Why It Should Be Built Before Other Projects:** Pairs naturally with Habits — both are daily-touch, both prove the offline-sync engine works.

---

### 53. Expense Tracking & Budgeting Engine

1. **Project Name:** Expense Tracking & Budgeting Engine  
2. **Purpose:** Envelope-style budgeting with rule-based alerts, not just a transaction log.  
3. **Problem It Solves:** Commercial finance apps mine your data, charge subscriptions, and don't integrate with your goal system. You need a rule-based envelope budget that connects to your actual life goals.  
4. **Why It Provides Long-Term Value:** 20 years of categorized financial data, tied to your goals and habits, generates insights no bank app can match (e.g., "my entertainment spending drops when my reading habit is active").  
5. **Target Users:** You, for personal financial control.  
6. **Core Features:** Accounts, transactions, category-based envelopes, monthly budget vs. actual.  
7. **Advanced Features:** Rule-based overspend alerts, recurring-transaction detection, multi-currency support.  
8. **Future Expansion Ideas:** Bank statement import (CSV/OFX) with rule-based auto-categorization (pattern matching, not ML).  
9. **Recommended Architecture:** Double-entry-inspired ledger internally, envelope budgeting as a view over it.  
10. **Recommended Technology Stack:** Laravel, Rule Engine (#16), `moneyphp/money` for currency-safe arithmetic (never use floats for money).  
11. **Database Design Suggestions:** `accounts`, `transactions`, `envelopes`, `budget_periods`.  
12. **Offline Strategy:** Offline transaction entry with sync reconciliation; balance recalculation on reconnect.  
13. **Security Considerations:** This is sensitive financial data — encryption-at-rest strongly recommended, strict audit logging on every mutation.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 4 weeks.  
16. **Skills You Will Learn:** Ledger design, currency-safe arithmetic, financial data modeling.  
17. **Reusable Components:** Underpins Savings Planner (#55), Net Worth Tracker (#56), Debt Payoff Planner (#57).  
18. **Integration With Other Projects:** Rule Engine, Notifications, Bill Reminder.  
19. **Maintenance Effort:** Medium — tax-year boundaries and currency edge cases need occasional attention.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Medium — personal finance is a crowded but proven category; your rule-based, no-ML angle is a genuine differentiator against subscription apps that mine your data.  
22. **Open Source Potential (Optional):** Medium.  
23. **Why It Should Be Built Before Other Projects:** Highest-value Phase 2 module outside productivity/knowledge — and it's the foundation four other Finance-category projects build on.

---

### 76. Freelance Client & Invoice Tracker

1. **Project Name:** Freelance Client & Invoice Tracker  
2. **Purpose:** Client pipeline, time tracking, and invoice generation — directly useful for your freelance work today, not just "someday personal" value.  
3. **Problem It Solves:** Spreadsheets for client work break under volume; commercial invoicing tools charge fees and don't integrate with your personal CRM or goal system.  
4. **Why It Provides Long-Term Value:** A unified client history, time logs, and invoice archive becomes a complete business record — invaluable for tax time, rate negotiation, and portfolio proof.  
5. **Target Users:** You, for freelance business operations.  
6. **Core Features:** Client records, project/engagement tracking, time entries, invoice generation (PDF).  
7. **Advanced Features:** Rate cards per client, automatic invoice numbering, payment status tracking with overdue rule-based reminders.  
8. **Future Expansion Ideas:** Recurring retainer billing, proposal/quote templates.  
9. **Recommended Architecture:** Built on the Contacts kernel (clients are people/orgs) and the CRM kernel (#77) for pipeline stages.  
10. **Recommended Technology Stack:** Laravel, `barryvdh/laravel-dompdf` or similar for invoice PDFs.  
11. **Database Design Suggestions:** `clients`, `engagements`, `time_entries`, `invoices`, `invoice_line_items`.  
12. **Offline Strategy:** Time tracking should work offline (log hours with no connection, sync later).  
13. **Security Considerations:** Financial data — same care as Expense Tracker; invoice PDFs stored in the File Vault with access control.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 3–4 weeks.  
16. **Skills You Will Learn:** PDF generation, invoicing/billing domain modeling, real client-facing feature design.  
17. **Reusable Components:** File Vault (PDF storage), Contacts kernel, Notification Hub (overdue alerts).  
18. **Integration With Other Projects:** Personal CRM (#77), Expense Tracker (income side).  
19. **Maintenance Effort:** Low-medium.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** High — this is genuinely close to a sellable freelancer product, and you already have a warm audience to validate it with.  
22. **Open Source Potential (Optional):** Medium.  
23. **Why It Should Be Built Before Other Projects:** Unlike most of this catalog, this one pays for its own development time almost immediately by making your actual freelance work easier to run.

---

### 77. Personal CRM & Relationship Health Tracker

1. **Project Name:** Personal CRM & Relationship Health Tracker  
2. **Purpose:** Keep relationships (not just clients) from going quiet — rule-based reach-out reminders based on contact frequency.  
3. **Problem It Solves:** Important personal and professional relationships decay from neglect; this system proactively reminds you to maintain them based on your own rules.  
4. **Why It Provides Long-Term Value:** Years of interaction history, tied to life events and goals, becomes a genuine relationship intelligence system — you'll know who you haven't spoken to, who was at major milestones, and how your network evolved.  
5. **Target Users:** You, for relationship maintenance.  
6. **Core Features:** Interaction logging, relationship-frequency targets, overdue-contact alerts.  
7. **Advanced Features:** Relationship "health score" from a transparent, documented rule set (not a black box).  
8. **Future Expansion Ideas:** Import interaction history from email/call logs (#78, #92) to auto-populate the timeline.  
9. **Recommended Architecture:** Built directly on the Contacts kernel (#7); adds interaction cadence rules via the Rule Engine.  
10. **Recommended Technology Stack:** Laravel, Rule Engine.  
11. **Database Design Suggestions:** `interactions`, `relationship_targets` (extends `people` from the kernel).  
12. **Offline Strategy:** Log interactions offline, sync later.  
13. **Security Considerations:** Sensitive third-party personal data — standard policies, careful with any future export/sharing features.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 3–4 weeks.  
16. **Skills You Will Learn:** Rule-based scoring design, relationship-domain modeling.  
17. **Reusable Components:** Contacts kernel, Rule Engine, Notification Hub.  
18. **Integration With Other Projects:** Freelance Client Tracker (professional relationships), Gift Planner, Important Dates.  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High.  
21. **SaaS Potential (Optional):** Medium.  
22. **Open Source Potential (Optional):** Low-medium.  
23. **Why It Should Be Built Before Other Projects:** Directly extends the Contacts kernel while it's still fresh, and it's a project whose *entire point* is compounding value from years of accumulated interaction history.

---

### 98. Server & Homelab Inventory

1. **Project Name:** Server & Homelab Inventory  
2. **Purpose:** A real asset registry for your homelab — hardware, services, IPs, credentials pointers (not the secrets themselves).  
3. **Problem It Solves:** "What's running where, on what port, with what config" becomes unmanageable from memory past a handful of machines.  
4. **Why It Provides Long-Term Value:** As your homelab grows, an accurate inventory prevents configuration drift, aids disaster recovery, and becomes the foundation for monitoring and deployment automation.  
5. **Target Users:** You, for homelab operations.  
6. **Core Features:** Device/service records, network topology notes, service-to-device mapping.  
7. **Advanced Features:** Auto-discovery via a simple network scan (rule-based port/service fingerprinting, not ML).  
8. **Future Expansion Ideas:** Feeds directly into Server Monitoring (#99) and Deployment Manager (#96) once built.  
9. **Recommended Architecture:** Standard kernel-consumer module — uses Tags for grouping, Audit for change history.  
10. **Recommended Technology Stack:** Laravel, Next.js.  
11. **Database Design Suggestions:** `devices`, `services`, `service_device`.  
12. **Offline Strategy:** Low priority for offline — this is inherently a "when I'm at my desk" tool.  
13. **Security Considerations:** Never store actual secrets here — link to entries in the Secrets Vault (#37) instead.  
14. **Difficulty:** Beginner.  
15. **Estimated Development Time:** 1–2 weeks.  
16. **Skills You Will Learn:** Infrastructure-as-data modeling, foundational DevOps thinking.  
17. **Reusable Components:** Consumed by Server Monitoring, Deployment Manager, Docker Dashboard.  
18. **Integration With Other Projects:** Secrets Vault, Tags, Audit.  
19. **Maintenance Effort:** Low.  
20. **Scalability Potential:** High for personal scale.  
21. **SaaS Potential (Optional):** Low.  
22. **Open Source Potential (Optional):** Medium — homelab inventory tools are a popular self-hosted category.  
23. **Why It Should Be Built Before Other Projects:** Cheap, fast, and it directly matches the homelab environment you're already running — unblocks the rest of your DevOps-category projects.

---

### 104. Unified Analytics & KPI Dashboard

1. **Project Name:** Unified Analytics & KPI Dashboard  
2. **Purpose:** Cross-project metric aggregation — the payoff view once enough modules exist to have real data to show.  
3. **Problem It Solves:** Without unified analytics, every tracker is a data silo; you can't see that your sleep, spending, and productivity are correlated.  
4. **Why It Provides Long-Term Value:** This is the project that turns 20 years of scattered tracking into unified personal intelligence — the ultimate compounding payoff.  
5. **Target Users:** You, for personal data insights.  
6. **Core Features:** Configurable KPI cards pulling from any module's data via the Reporting service.  
7. **Advanced Features:** Trend lines over time, goal-vs-actual overlays pulling from the Goal Hierarchy.  
8. **Future Expansion Ideas:** Custom Report Builder (#106) as its natural successor once you want ad hoc queries, not just fixed KPIs.  
9. **Recommended Architecture:** Consumes a `MetricProvider` contract each module implements — same registry pattern as the Widget Framework, one level more analytical.  
10. **Recommended Technology Stack:** Laravel, Next.js with `recharts` for visualization.  
11. **Database Design Suggestions:** `metric_snapshots` (periodic rollups, avoids recomputing from raw data every view).  
12. **Offline Strategy:** Renders last-cached snapshots offline.  
13. **Security Considerations:** Inherits per-module data policies.  
14. **Difficulty:** Advanced.  
15. **Estimated Development Time:** 4 weeks.  
16. **Skills You Will Learn:** Data aggregation patterns, materialized-view-style rollup design, dashboard charting.  
17. **Reusable Components:** `MetricProvider` contract reusable by Life Statistics (#105) and Report Builder (#106).  
18. **Integration With Other Projects:** Every domain module with numeric history.  
19. **Maintenance Effort:** Low-medium.  
20. **Scalability Potential:** High if snapshots are pre-computed rather than queried live.  
21. **SaaS Potential (Optional):** Low standalone.  
22. **Open Source Potential (Optional):** Low-medium.  
23. **Why It Should Be Built Before Other Projects:** It needs real accumulated data from Habits, Finance, and Planner to be worth anything — building it too early just gives you empty charts. Deliberately last of the Top 20.


---

## 5. Build Roadmap

### Phase 0 — Foundation (Months 1–3) — **IN PROGRESS (mostly done)**
**Goal:** Eliminate boilerplate tax for the next 19 years.

| Order | Project | Why First |
|-------|---------|-----------|
| 0.1 | `ctlab-support` PHP package | Base classes every module extends | ✅ (partial — conventions baked into domain modules) |
| 0.2 | Core Identity & Access (#1) | Nothing else works without auth | ✅ DONE |
| 0.3 | Shared REST API Gateway (#2) | Every controller depends on these conventions | ✅ DONE |
| 0.4 | Basic `ctlab-ui` + `ctlab-theme` | Frontend needs something to render | ◻ (Tailwind shared styles in use) |
| 0.5 | Docker/CI skeleton | You need to deploy before you build | ⚠ Skipped — running on XAMPP/Apache locally |

**Deliverable:** A working login page, a "Hello World" API response, and a Docker Compose that spins up app + MySQL + Redis + nginx with one command.

---

### Phase 1 — Kernel Services (Months 4–9)
**Goal:** Build the shared nervous system.

| Order | Project | Dependencies |
|-------|---------|--------------|
| 1.1 | Notification Hub & Event Bus (#3) | Identity |
| 1.2 | File & Document Vault (#4) | Identity |
| 1.3 | Tagging & Categorization Engine (#8) | — |
| 1.4 | Calendar & Scheduling Kernel (#6) | Identity, Notifications |
| 1.5 | Contacts & Relationship Graph (#7) | Identity, Tags |
| 1.6 | Audit Trail & Activity Timeline (#9) | Identity |
| 1.7 | Settings & Preferences Service (#10) | Identity |
| 1.8 | Rule Engine & Workflow Builder (#16) | Event Bus, Notifications |
| 1.9 | Dashboard & Widget Framework (#12) | Identity |
| 1.10 | Unified Search Engine (#5) | Tags |
| 1.11 | Import/Export Service (#11) | File Vault |
| 1.12 | Offline Sync Engine (#15) | Identity, API Gateway |
| 1.13 | UI Component Library completion (#13) | — |
| 1.14 | Plugin/Module Registry (#14) | ctlab-support |

**Deliverable:** Every kernel service has a working API, test coverage, and at least one consumer module proving it works end-to-end.

---

### Phase 2 — Daily-Use Core (Months 10–18)
**Goal:** The ecosystem starts paying rent — you use these daily.

| Order | Project | Dependencies |
|-------|---------|--------------|
| 2.1 | Unified Planner Engine (#19) | Calendar, Tags |
| 2.2 | Goal Hierarchy & OKR Tracker (#20) | Planner, Audit |
| 2.3 | Personal Dashboard / Life OS Home (#22) | Widget Framework |
| 2.4 | Habit Tracking Engine (#40) | Rule Engine, Calendar |
| 2.5 | Journal & Structured Review System (#41) | Calendar, Tags |
| 2.6 | Second Brain / Personal Wiki (#26) | Search, Tags | ✅ DONE (shipped ahead of schedule, 2026-08-16) |

**Deliverable:** You open the Dashboard every morning, check off habits, review your planner, and take structured notes. This is your real validation that the kernel was built correctly.

---

### Phase 3 — High-Value Expansion (Months 19–30)
**Goal:** Finance, career, and relationship modules.

| Order | Project | Dependencies |
|-------|---------|--------------|
| 3.1 | Expense Tracking & Budgeting Engine (#53) | Rule Engine, Tags |
| 3.2 | Bill Payment & Reminder Engine (#54) | Notifications, Calendar |
| 3.3 | Savings & Financial Goal Planner (#55) | Expense Engine |
| 3.4 | Investment & Net Worth Tracker (#56) | Expense Engine |
| 3.5 | Loan & Debt Payoff Planner (#57) | Expense Engine |
| 3.6 | Freelance Client & Invoice Tracker (#76) | CRM Kernel, Contacts |
| 3.7 | Personal CRM & Relationship Health Tracker (#77) | Contacts Kernel, Rule Engine |
| 3.8 | Health-category modules (#46–52) | Rule Engine, Calendar |
| 3.9 | Home-category modules (#61–66) | Rule Engine, Calendar |
| 3.10 | Career-category modules (#72–75) | Contacts, Calendar |

**Note:** Freelance Tracker can jump the queue earlier than #76's numbering suggests if client work is a nearer-term pain point than personal finance.

---

### Phase 4 — DevOps/Homelab Track (Parallel, Months 12–24)
**Goal:** Improve how you operate the platform itself.

| Order | Project | Dependencies |
|-------|---------|--------------|
| 4.1 | Server & Homelab Inventory (#98) | Tags, Audit |
| 4.2 | Docker Environment & Container Dashboard (#97) | Server Inventory |
| 4.3 | Server Monitoring Dashboard (#99) | Server Inventory, Rule Engine |
| 4.4 | Backup Manager & Scheduler (#100) | Rule Engine, Notifications |
| 4.5 | Deployment Manager (#96) | Server Inventory |
| 4.6 | Security Audit Dashboard (#102) | Identity, Rule Engine |

**Note:** This track runs alongside the others whenever you want a break from personal-data domains.

---

### Phase 5 — Long Tail (Months 25–40)
**Goal:** Lower-urgency, lower-dependency projects — build opportunistically.

- Travel & Transportation (#67–71)
- Entertainment & Lifestyle (#83–87)
- Writing & Content (#88–90)
- Business & Relationships long tail (#78–82)
- Developer Tools (#91–95)
- Security (#103)

---

### Phase 6 — Analytics Layer (Months 40–48)
**Goal:** The payoff layer — needs 18+ months of accumulated data to be meaningful.

| Order | Project | Dependencies |
|-------|---------|--------------|
| 6.1 | Unified Analytics & KPI Dashboard (#104) | Widgets, every domain |
| 6.2 | Life Statistics Engine (#105) | Analytics kernel |
| 6.3 | Custom Report Builder (#106) | Analytics kernel, Reporting service |

**Deliverable:** You can see cross-domain trends (e.g., "my productivity score drops when my sleep debt exceeds 4 hours and my entertainment spending exceeds $X").

---

### Phase 7 — Offline Rollout & Extraction (Months 48+)
**Goal:** Harden what exists, evaluate commercialization.

- Retrofit Offline Sync Engine (#15) across Planner, Habits, Journal, Second Brain, Contacts.
- Evaluate Freelance Tracker, Personal CRM, and the Rule Engine itself as SaaS or open-source candidates.
- Their domain boundaries were kept clean from day one specifically so this extraction is a "cut along the seam," not a rewrite.

---

## 6. Integration Model

Every project in this catalog talks to every other project through the kernel, not directly to each other. This is what makes it *one platform* rather than 107 apps.

| Integration Layer | How It Works | Example |
|-------------------|--------------|---------|
| **Shared Authentication** | One login (Identity kernel) across the single Next.js app; no module has its own user table. | Log in once, access Planner, Finance, and CRM seamlessly. |
| **Shared Database** | One MySQL/Postgres instance, module-prefixed tables, enabling cheap cross-module joins. | "Show me expenses tagged with a Habit" is a single JOIN. |
| **Shared REST API** | One `/api/v1` surface with consistent conventions; typed `ctlab-api-client` generated from it. | Frontend calls one client library, not 107 different APIs. |
| **Shared Notifications** | Every module fires domain events; only the Notification Hub decides channel/timing. | A habit streak break and a bill due date both use the same notification pipeline. |
| **Shared Search** | Every searchable model implements one contract; the Search kernel indexes all uniformly. | One search box finds notes, documents, contacts, and tasks. |
| **Shared File Storage** | One Document Vault; Medical, Tax, Research, and Resume all store through it. | One backup strategy covers all documents. |
| **Shared Calendar** | Planner, Habits, Home Maintenance, and Medical all attach to one `CalendarEvent` model. | One iCal export contains everything time-based. |
| **Shared Tagging** | One polymorphic tag table used by nearly every module for cross-cutting organization. | Tag "urgent" appears on tasks, bills, and maintenance items. |
| **Shared UI Components** | `ctlab-ui` + `ctlab-theme` mean every new module looks consistent with zero design decisions. | Every form looks the same; every button behaves the same. |
| **Shared Logging/Audit** | One audit trail schema; "what changed and when" works the same way everywhere. | Every mutation is traceable back to user, timestamp, and before/after state. |
| **Shared Settings** | One preferences store, namespaced per module. | Toggle a global dark mode, or per-module notification preferences. |
| **Shared Permissions** | One RBAC/policy system; a new module adds policies, never a new auth mechanism. | "Admin" vs "Viewer" works identically across all 107 modules. |

**The Compounding Effect:** Every new module makes the kernel more battle-tested, and every kernel improvement benefits every module retroactively, for free.

---

## 7. Technology Stack Reference

> **As implemented (2026-08-16):** PHP 8.2 + Laravel 10 (not 11), MySQL 8 on local XAMPP (not Postgres/Docker), Next.js 14.2 with static export + Tailwind (no SWR/Recharts/react-grid-layout yet), Apache via XAMPP vhost (not Nginx), no Redis (sync queue), no Docker. Column 'Notes' below is the v2.0 plan; actuals noted inline.

### Backend
| Layer | Technology | Notes |
|-------|------------|-------|
| Language | PHP 8.3+ | Latest stable, strict typing |
| Framework | Laravel 11+ | Modular monolith via `app/Domains/*` or `nwidart/laravel-modules` |
| Auth | Laravel Sanctum (SPA mode) | JWT alternative acceptable, but Sanctum is simpler for SPA |
| RBAC | Spatie Laravel-Permission OR hand-rolled | Hand-rolled recommended for learning DDD/policy patterns |
| Queues | Redis + Laravel Horizon | For notifications, rule evaluation, imports |
| Cache | Redis | Permission cache, search index, widget payloads |
| Search | MySQL FULLTEXT / custom inverted index | Deterministic, no Elasticsearch/Meilisearch needed at personal scale |
| Files | Laravel Filesystem (local → MinIO/S3) | Abstraction allows swap without touching consumer code |
| Testing | PHPUnit, Pest | Feature + unit tests per domain module |
| API Docs | Scramble or similar | Auto-generated OpenAPI from Laravel routes |

### Frontend
| Layer | Technology | Notes |
|-------|------------|-------|
| Framework | Next.js 14+ (App Router) | Route groups by domain: `app/(planner)`, `app/(finance)` |
| Language | TypeScript | Strict mode |
| Styling | Tailwind CSS | Via `ctlab-theme` design tokens |
| Components | React + `ctlab-ui` | Shared primitives: Button, Input, Modal, Table, DataGrid, Card |
| State | SWR or TanStack Query | Server state caching, optimistic updates |
| Offline | Service Worker + IndexedDB | `ctlab-sync` protocol |
| Charts | Recharts | For Analytics, Finance trends, Health metrics |
| Grid | `react-grid-layout` | For Dashboard widget arrangement |

### Database & Storage
| Layer | Technology | Notes |
|-------|------------|-------|
| Primary DB | MySQL 8+ OR PostgreSQL 16+ | Postgres recommended for CTEs (recursive queries in Goal Hierarchy) |
| Migrations | Module-prefixed tables | `planner_items`, `finance_transactions`, `habit_logs` |
| Files | Local disk → MinIO | Start local, move to MinIO when you need remote redundancy |
| Backups | `ctlab-backup` module (Phase 4) | Automated, verified, offsite-optional |

### DevOps & Infrastructure
| Layer | Technology | Notes |
|-------|------------|-------|
| Container | Docker + Docker Compose | Single compose file for local + production |
| Web Server | Nginx | Reverse proxy, static file serving, rate limiting at edge |
| CI/CD | GitHub Actions | Test → Build → Deploy to your homelab/VPS |
| Monitoring | `ctlab-monitoring` module (Phase 4) | Self-hosted, feeds the Rule Engine for alerts |
| SSL | Let's Encrypt | Auto-renewal tracked by Domain & SSL Tracker (#101) |

---

## 8. Getting Started: Immediate Action Items

### This Week
1. **Create the monorepo.** `mkdir ctlab && cd ctlab && git init`. Set up the folder structure from Section 2.3.
2. **Write your first ADR.** `docs/adr/001-modular-monolith.md` — document *why* you chose monolith over microservices. Future-you in year 8 will thank present-you.
3. **Scaffold `ctlab-support`.** Create the base `Repository`, `Service`, `Action`, and `DTO` classes. This is your first practice of Clean Architecture.
4. **Docker skeleton.** A `docker-compose.yml` with `app`, `mysql`, `redis`, and `nginx` services that boots without errors.

### This Month
5. **Implement Identity & Access (#1).** Registration, login, Sanctum SPA auth, and a basic `User` model. No RBAC yet — just auth.
6. **Implement Shared API Gateway (#2).** Response envelope, exception handler, and a `/api/v1/health` endpoint.
7. **First Next.js page.** A login page that hits the health endpoint. Prove the frontend ↔ backend loop works.

### Next 3 Months
8. **Complete Phase 0.** `ctlab-support` hardened, Identity with RBAC, API Gateway with rate limiting, Docker/CI green on every push.
9. **Start Phase 1 kernel.** Notification Hub and File Vault first — they have the most downstream consumers.
10. **Do not build a tracker yet.** Resist the urge. The kernel is boring; it is also the entire reason this plan takes 4 years instead of 40.

### Success Criteria for Phase 0
- [ ] `docker compose up` boots the full stack in <30 seconds.
- [ ] `php artisan test` runs the Identity + API Gateway test suites with 100% pass rate.
- [ ] You can register, log in, and see a protected dashboard page in Next.js.
- [ ] The `ctlab-support` package has zero dependencies on any framework other than Laravel's core — no accidental coupling to Eloquent in domain logic.

---

## Appendix A: Ranking Methodology

Projects were ranked by the following weighted criteria:

1. **Long-term usefulness** (25%) — Will this matter in 10 years?
2. **Daily/weekly usefulness** (20%) — How often will I touch this?
3. **Return on development time** (15%) — Does it save more time than it takes to build?
4. **Reusability** (15%) — How many other projects does this unblock?
5. **Data accumulation value** (10%) — Does it get better with more historical data?
6. **Automation potential** (10%) — Does it reduce manual work over time?
7. **Educational value** (5%) — What skills does this practice?

The Top 20 are not necessarily the "coolest" projects — they are the ones that maximize the product of these factors while respecting the dependency chain.

---

## Appendix B: Excluded Project Types

The following were deliberately excluded per the original requirements:

- Simple CRUD applications without domain logic
- Calculator apps
- Weather apps
- Basic To-Do apps (superseded by the Unified Planner Engine)
- Clone projects (Netflix clone, Uber clone, etc.)
- Tutorial projects
- Portfolio-only projects with no daily utility
- Any project requiring AI/ML/LLMs (all automation is rule-based and deterministic)

---

*End of Final Master Plan*
 Here is your complete, consolidated **Personal Education OS** build plan. This is the final blueprint you can follow from day one through production.


---

## Appendix C: Build Log & Decision Record (2026-08-16)

### What was built
- `apps/api` Laravel 10 monolith; all 106 domains scaffolded under `app/Domains/NN_Name`.
- Domain #1 (Identity) and #2 (API Gateway) implemented and verified.
- Domain #26 (Second Brain / Personal Wiki) fully implemented:
  - 13 migrations, 66 `wiki.*` API routes, models/controllers/policies/services.
  - Frontend: 12 exported Next.js routes under `app/(wiki)/`, block editor with autosave + read mode, versions, backlinks, favorites, templates, trash, projects/questions/references/tasks/reviews engines.
  - Sidebar wiki navigation added; `review.page` and `project.pages` eager-loaded.

### Verification results
- `tsc --noEmit --incremental false`: PASS.
- `npm run build` (static export): PASS — 12 wiki routes exported.
- Apache `lab.ct.local`: all routes HTTP 200.
- API smoke test: every frontend-consumed endpoint returns 200 with correct envelope.
- Playwright E2E: **25/25 PASS**, no console/page errors.

### Removals & cleanup
- Standalone "Personal Education OS" project removed from the platform after the port (Docker stack stopped; legacy `education_os` schema dumped to `archives/education_os_dump_2026-08-16.sql`).
- Wiki data wiped (14 `wiki_*` tables truncated) so the app starts clean.

### Credentials
- Owner: `owner@lab.ct.local` / `admin123` (reset 2026-08-16 after seed hash mismatch; new users seed via `RolePermissionSeeder` with `ChangeMe!123456` only if user absent).

### Known deviations from plan
- Local hosting is XAMPP/Apache + MySQL 8 (plan said Docker + Nginx + Postgres) — works for a solo local-first platform.
- Next.js static export (SSG) rather than SSR; auth via localStorage Bearer token.
- No Redis/queues yet (sync queue).
- "As-built" folder names differ from the plan's short names (`26_SecondBrainPersonalWiki` etc.) but preserve catalog numbering.

*End of Final Master Plan (v2.0)*
