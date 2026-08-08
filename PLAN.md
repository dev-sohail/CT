# CT / TirahAi — Master Implementation Plan

Generated from actual repo state on branch `Centralized`.
Do not treat this as ready-to-ship code; it is the execution roadmap for step-by-step implementation.

---

## 0. Current State Summary

| Area | Status |
|---|---|
| TirahAI core (agent, intent, memory, tools, voice, hardware, OS, study, code, iot) | Implemented — `AgenticModel`, `IntentEngine`, `MemoryStore`, `ToolRegistry`, `NLPBrain`, `CodeHelper`, `VoiceAssistant`, integrations |
| TirahAI GUI (`gui/modern_ui.py`) | Implemented — Flet dashboard (Chat, Study, Code, System, Hardware, Settings) |
| TirahAI REST API (`api/main.py`) | Implemented — FastAPI app with JWT auth, chat, tools, capabilities endpoints |
| Entrypoints | Implemented — `main.py` (GUI mode), `entrypoint.py` (API server), `run.bat`, `run-api.bat` |
| FASTAPI v1 (`FASTAPI/v1/`) | Implemented — async SQLAlchemy, JWT + refresh tokens, rate limiting, WebSocket, repositories, schemas, services, tests, Alembic |
| FASTAPI v2 (`FASTAPI/v2/`) | Implemented — real v2 routes, versioning middleware, migration guide |
| WEB/Edu_PHP | Implemented — modular PHP app with FastRoute, ORM-like `BaseModel`, WebSocket (Ratchet), Redis cache/queue, Stripe, templating |
| APP/Edu_FLUTTER | Implemented — Flutter app with role login, biometric, Hive offline cache, FCM notifications, FastAPI v1 client |
| APP/TirahAI_FLUTTER | Deprecated — does not exist; use `APP/Edu_FLUTTER` |
| WEB/TirahAI_NEXT | Implemented — Next.js 14 App Router for TirahAI assistant only (auth, dashboard chat, assistant admin, SSE realtime) |
| Dockerization | Implemented — `Dockerfile`, `Dockerfile.php`, `docker-compose.yml` (Postgres, Redis, FastAPI, PHP-FPM, Nginx, Adminer) |
| CI/CD | Implemented — `.github/workflows/ci.yml` (ruff, ESLint, PHPStan, pytest, Docker buildx) |
| DB services | SQLite + Postgres connection pools present (`services/db.py`, `services/db_postgres.py`), Alembic configured |

---

## 1. Fixes & Stability (Prerequisite for everything)

### 1.1 Fix import/runtime blockers in `TirahAI/`
- [x] Add `WEATHER_API_KEY` to `config/settings.py` (module-level constant + inside `api_keys` dict).
- [x] Add `IOT_CONFIG` dict to `config/settings.py`.
- [x] Fix `modules/integrations/openai_api.py` — upgrade to `openai>=1.0` SDK, remove broken config imports.
- [x] Standardize `modules/integrations/translation_api.py` on `TranslationService` class name.
- [x] Fix `modules/integrations/email_service.py` — make `oauth2client` optional import.
- [x] Guard module-level side effects in `modules/automation/play_music.py` (lazy imports for Spotify auth, scheduler, pyttsx3).
- [x] Wrap `cryptography`/`textblob` imports in `modules/journal.py` with `try/except`.
- [x] Unify entrypoints: `main.py` for GUI (default), `entrypoint.py` for API server, `run.bat`/`run-api.bat` launchers.

### 1.2 Build missing entry points
- [x] `TirahAI/gui/modern_ui.py` — Flet dashboard with Chat, Study, Code, System, Hardware, Settings panels.
- [x] `TirahAI/api/main.py` — FastAPI REST API (JWT auth, `/v1/assistant/chat`, `/v1/tools/execute`, `/v1/capabilities`).
- [x] `TirahAI/entrypoint.py` — launches `api.main:app` via uvicorn.

---

## 2. TirahAI Core Enhancements

### 2.1 Agent & Intelligence
- [x] Plug local LLM (Ollama) as primary fallback when OpenAI/Gemini unavailable.
- [x] Improve intent classifier in `core/intent.py` with confidence thresholds and graceful fallback.
- [x] Add tool-result caching in `core/tools.py` for expensive ops (weather, translate, web search).
- [x] Add request timing and circuit-breaker patterns for external API calls.

### 2.2 Memory & Learning
- [x] Persist long-term memory in Postgres via `services/db_postgres.py`.
- [x] Add memory export/import in `core/memory.py`.
- [x] Add user feedback loop (`agent.feedback`) to improve intent routing over time.

### 2.3 Voice & Hardware
- [x] Add wake-word support using `porcupine` or offline `Vosk` (`modules/voice.py`).
- [x] Make hardware I/O configurable from `config.json` instead of hardcoded COM port.
- [x] Add MQTT discovery / auto-register for IoT devices (`modules/iot_manager.py`).

### 2.4 Modules cleanup
- [x] Wrap standalone automation files as registered tools in `core/tools.py` so the agent can call them.
- [x] Replace `googletrans` with `deep-translator`.
- [x] Remove or modularize `modules/ai_components/` orphaned code.

---

## 3. FASTAPI Backend

### 3.1 v1 — Secure API layer (`FASTAPI/v1/`)
- [x] `requirements.txt` with FastAPI, uvicorn, SQLAlchemy, aiosqlite, pydantic-settings, python-jose, passlib, httpx.
- [x] Structured project: `routes/`, `schemas/`, `services/`, `repos/`, `models/`, `core/`.
- [x] Authentication & Authorization:
  - JWT access + refresh tokens (`core/security.py`).
  - Role-based access (`get_current_active_superuser`).
  - API key auth for service-to-service calls.
- [x] Endpoints:
  - `POST /v1/auth/login`, `POST /v1/auth/refresh`, `GET /v1/auth/me`, `GET /v1/auth/users`
  - `POST /v1/assistant/chat` — TirahAI agent proxy
  - `POST /v1/tools/execute` — generic tool execution
  - `GET /v1/capabilities` — assistant capabilities
  - `GET /v1/health`, `GET /v1/health/ready`
- [x] Request validation with Pydantic v2.
- [x] Slow-query logging and request timing middleware (`RequestLoggingMiddleware`).
- [x] Rate limiting middleware (`RateLimitMiddleware`).
- [x] CORS policy locked to known frontend origins.
- [x] WebSocket endpoint for real-time voice/assistant streaming (`/v1/ws/assistant`).
- [x] Alembic migrations setup (`alembic/` directory, env.py, script template).

### 3.2 v2 — Future API version
- [x] Replace stub with real v2 routes/versioning (`FASTAPI/v2/main.py`).
- [x] Migration strategy from v1 (`FASTAPI/v2/MIGRATION.md`).

---

## 4. TirahAI Web & Mobile Placeholders

### 4.1 `TirahAI_NEXT` (Next.js) — TirahAI assistant interface ONLY
- [x] Scaffold Next.js 14 App Router project.
- [x] Auth pages: login, register, forgot connected to FastAPI v1.
- [x] Dashboard with chat assistant (`/dashboard`).
- [x] Admin portal page for assistant management (`/admin`).
- [x] API route handlers proxying to FastAPI v1 (auth, assistant, realtime SSE).
- [x] Reusable UI components (`components/ui/card.tsx`, `button.tsx`, `input.tsx`).
- [x] Real-time features via SSE for stats streaming (`/api/realtime/stats`).
- [x] Wire admin stats, capabilities, and tools UI in `/admin`.

> Note: `TirahAI_NEXT` is **not** the admin shell for Edu_PHP institute modules. Institute features belong in `WEB/Edu_PHP`.

### 4.2 `TirahAI_FLUTTER`
- [x] Deprecated — `APP/TirahAI_FLUTTER` does not exist; use `APP/Edu_FLUTTER` as the single Flutter mobile app.

---

## 5. Deployment & DevOps
- [x] `Dockerfile` + `docker-compose.yml` with Postgres, Redis, FastAPI, PHP-FPM, Nginx, Adminer.
- [x] `remote_wifi_access` — documented IPv6, HTTPS (Let's Encrypt, Caddy/Traefik), Docker firewall integration guide (`Dockerization/remote_wifi_access.md`).
- [x] GitHub Actions CI (`lint`, `test`, `build` jobs).
- [x] `.env.example` for TirahAI and FASTAPI v1.

---

## 6. Edu_PHP Institute Management System

> These modules belong to `WEB/Edu_PHP` and its own admin UI. They are **not** part of `TirahAI_NEXT`.

### 6.1 Institute Profile & Infrastructure
- [x] Institute Profile, Branding, Basic Info, Contact, Address, Academic Year
- [x] Campus, Branch, Department, Program, Class/Section management
- [x] Institution Policies, Working Days/Holidays, Time Zones, Language, Currency
- [x] Roles & Permissions, User Management, System Settings
- [x] Backup & Restore, Audit Logs
- [x] Edu_PHP implementation: `InstituteController`, `InstituteModel`, DB tables, routes, admin view
- [x] FastAPI v1 internal API: `/v1/institute/profile`, `/campuses`, `/departments`, `/programs`, `/classes`, `/audit-logs`

### 6.2 Examination Management
- [x] Exam schedules, seating plans, invigilator assignment
- [x] Online/offline exam delivery, OMR scanning
- [x] Auto-grading, result publishing, report cards
- [x] Edu_PHP implementation: `ExamModel`, `ExamController`, DB tables, routes, admin view
- [x] Existing: `PaperGeneratorController`, `ExamPaperModel`, `ExamRecordModel`
- [x] FastAPI v1 internal API: `/v1/exam/schedules`, `/seating`, `/invigilators`, `/grade/{paper_id}`, `/publish/{paper_id}`

### 6.3 Course Management
- [x] Course catalog, syllabus, prerequisites
- [x] Lesson plans, resources, assignments
- [x] Progress tracking, completion certificates
- [x] Edu_PHP new: `CourseModel` with progress/certificates tables; `CourseController` for admin CRUD
- [x] FastAPI v1 internal API: `/v1/courses`, `/modules`, `/resources`, `/enrollments`, `/progress/{student}`, `/certificate/{student}`

### 6.4 Admission Management
- [x] Online application forms, document upload
- [x] Entrance test scheduling, merit lists
- [x] Offer letters, enrollment conversion
- [x] Edu_PHP new: `AdmissionModel` with tables; `AdmissionController`, routes, admin view
- [x] FastAPI v1 internal API: `/v1/admission/applications`, `/documents`, `/tests`, `/results`, `/merit/{program_id}`, `/offer`, `/accept-offer`

### 6.5 Faculty/Staff Management
- [x] Staff profiles, qualifications, experience
- [x] Timetable/task assignment, timesheet
- [x] Appraisals, leave management, payroll integration
- [x] Edu_PHP new: `FacultyModel` with tables; `FacultyController`, routes, admin view
- [x] FastAPI v1 internal API: `/v1/faculty`, `/faculty/{id}`, `/timesheets`, `/leaves`, `/appraisals`

### 6.6 Finance Management
- [x] Fee structure, invoices, payment gateway
- [x] Expense tracking, budget planning
- [x] Accounting, payroll, scholarships
- [x] Edu_PHP enhanced: `FinanceModel` with full CRUD, `PaysController` with reports, admin views
- [x] FastAPI v1 internal API: `/v1/finance/transactions`, `/invoices`, `/categories`, `/reports`

### 6.7 Student/Parent/Teacher Management
- [x] Student profiles, guardians, contacts
- [x] Parent portal login, child progress, messaging
- [x] Teacher class assignment, workload, communication
- [x] Edu_PHP enhanced: `PeopleModel` with students/parents/teachers queries; `PeopleController`, routes, admin views
- [x] FastAPI v1 internal API: `/v1/people/students`, `/parents`, `/teachers`, `/parent-student/links`, `/progress`

### 6.8 Attendance Management
- [x] Attendance reports, leave requests, approval workflow
- [x] SMS/email alerts for absences
- [x] Edu_PHP enhanced: `Admin\AttendanceModel`; `Admin\AttendanceController`, routes, admin view
- [x] FastAPI v1 internal API: `/v1/attendance/daily`, `/leaves`, `/reports`, `/alerts`

### 6.9 Learning Management System (LMS)
- [x] Course content delivery, video lessons
- [x] Assignments, submissions, grading
- [x] Quizzes, anti-cheating, discussion forums
- [x] Digital library, resources
- [x] Edu_PHP enhanced: `CourseModel` extended with assignments/submissions/quizzes/forum/library tables; `CourseController` extended
- [x] FastAPI v1 internal API: `/v1/lms/courses`, `/modules`, `/resources`, `/enrollments`, `/assignments`, `/submissions/grade`, `/quizzes`, `/forums`, `/library`

### 6.10 Communication & Community
- [x] Helpdesk & support tickets
- [x] Community forums / discussion boards
- [x] Blog & news management, notice board, announcements
- [x] Surveys, polls, events management
- [x] Edu_PHP enhanced: `CommunityModel` with tables; `CommunityController`, routes, admin view
- [x] FastAPI v1 internal API: `/v1/community/tickets`, `/forums`, `/posts`, `/blog`, `/surveys`, `/polls`

### 6.11 Business Management Modules *(Optional)*
- [x] E-Commerce, Purchase, Expense, CRM
- [x] Email marketing, sales, inventory
- [x] Asset request, staff appraisals, payroll
- [x] Placement management, secure transcript
- [x] Edu_PHP new: `BusinessModel` with tables; `BusinessController`, routes, admin view
- [x] FastAPI v1 internal API: `/v1/business/orders`, `/purchases`, `/crm`, `/inventory`, `/assets`, `/placements`, `/transcripts`, `/campaigns`

### 6.12 Campus Infrastructure Modules *(Optional)*
- [x] Transportation management
- [x] Campus & hostel management
- [x] Canteen management
- [x] Convocation management
- [x] Edu_PHP new: `CampusModel` with tables; `CampusController`, routes, admin view
- [x] FastAPI v1 internal API: `/v1/campus/transport`, `/hostel`, `/canteen`, `/convocation`

### 6.13 Gradebook & Self-Assessment System
- [x] Self-assessment over grade comparison
- [x] Personal growth analytics, skill tracking
- [x] Learning progress visualization, AI recommendations
- [x] Reflection journals, goal setting, strength/weakness analysis
- [x] Edu_PHP new: `GradebookModel` with tables; `GradebookController`, routes, admin view
- [x] FastAPI v1 internal API: `/v1/gradebook/entries`, `/self-assessments`, `/journals`, `/goals`, `/skills`, `/analysis`

### 6.14 Edu_PHP Communication / Chat System
- [x] Existing real-time chat: WebSocket (`WebSocketService` with Ratchet), REST routes
- [x] Database: `user_messages`, `chat_groups`, `chat_group_members`
- [x] Frontend: Vue-based chat UI in `frontend/public/chat.ct.php` and `frontend/portals/pages/chat.ct.php`
- [x] Edu_PHP controllers: `Public\ChatController`, `Portal\ChatController`, `Public\MessagesModel`, `Portal\ChatModel`
- [x] Status/Story module (backend): `ChatStatusModel`, `ChatStatusController`, tables, routes, 24h TTL, role-based visibility, viewed-by tracking
- [x] Acknowledged chat list (backend): `MessagesModel::getConversations()` + `/chat/conversations`
- [x] FastAPI v1 internal API: `/v1/chat/status`, `/chat/status/viewers`, `/chat/acknowledged`, `/chat/messages/search`, `/chat/messages/delete`
- [x] Profile view integration: show user status/story in profile page (`profile.ct.php`) and peer status data in chat (`MessagesModel::getPeers()` includes status)
- [x] Admin chat management panel: admin UI to view all conversations, search messages, export logs, moderation actions (`admin/chat` page with management panel + Vue chat)

---

## 7. APP — Edu_FLUTTER Improvements
- [x] Move API base URL to `.env` / `--dart-define` (`lib/utils/constants.dart`).
- [x] Remove unnecessary files/folders for organization.
- [x] Offline cache (Hive) for attendance, classes, notices (`lib/services/api_service.dart`).
- [x] Connect to FastAPI v1 endpoints (`lib/services/api_service.dart`).
- [x] Biometric login (`lib/services/biometric_service.dart` + login screen).
- [x] Push notifications (FCM) (`lib/services/notification_service.dart`).
- [x] Chat and Status.

## 8. Testing & Quality Assurance
- [x] Add unit tests for `TirahAI/core/intent.py`, `core/tools.py`, `core/memory.py`.
- [x] Add unit tests for `FASTAPI/v1/routes/auth.py`, `assistant.py`.
- [x] Add unit tests for Edu_PHP models: `MessagesModel`, `ChatStatusModel`, `InstituteModel`.
- [x] Add integration tests for chat flow: send message, search, status post, view.
- [x] Add PHPUnit tests for controllers: `ChatController`, `ProfileController`.
- [x] Add E2E tests for TirahAI_NEXT auth and dashboard chat.
- [x] Add Flutter widget tests for Edu_FLUTTER login and attendance screens.
- [x] Add CI test coverage reporting (pytest + PHPUnit + coverage badges).

## 9. Security Hardening
- [x] Disable `DEBUG_MODE` by default in production configs (`TirahAI/config/settings.py`, `.env.example`).
- [x] Add rate limiting to public Edu_PHP API routes (`/api/*`) and chat endpoints.
- [x] Add CSRF protection to all Edu_PHP POST forms that lack it.
- [x] Sanitize all chat message outputs to prevent XSS in Vue chat templates.
- [x] Add input validation middleware to FASTAPI v1/v2 for all POST bodies.
- [x] Audit JWT token expiry and refresh rotation in FASTAPI v1.
- [x] Add password strength enforcement in `AuthController` registration/reset.
- [x] Add security headers (CSP, HSTS, X-Frame-Options) to Edu_PHP `.htaccess`.
- [x] Encrypt sensitive columns in `chat_statuses` (`media_url`) and `user_messages` (`attachment_url`).

## 10. Performance & Observability
- [x] Add database query profiling and slow-query alerts in Edu_PHP `QueryLogger`.
- [x] Add HTTP cache headers for static assets in Edu_PHP.
- [x] Optimize `getPeers()` query with covering index on `user_messages(sender, receiver, created_at)`.
- [x] Add Redis caching for `capabilities` and `auth/me` responses in FASTAPI v1.
- [x] Add structured JSON logging with correlation IDs across TirahAI, FASTAPI, Edu_PHP.
- [x] Add Prometheus metrics endpoint to FASTAPI v1/v2 (`/metrics`).
- [x] Add health check probes for Redis, PostgreSQL, MQTT in FASTAPI v2 `/health/ready`.
- [x] Add client-side caching strategy for TirahAI_NEXT API routes (React Query / SWR).

## 11. TirahAI_NEXT Features
- [x] Add error boundary and 404/500 error pages.
- [x] Add dark mode toggle with system preference detection.
- [x] Add i18n support (en/ur) with next-intl.
- [x] Add WebSocket/SSE bridge for live assistant streaming in dashboard.
- [x] Add tool execution UI (form inputs for weather, translate, search tools).
- [x] Add session history and conversation list for assistant chats.
- [x] Add admin user management UI (list users, toggle active, assign roles).
- [x] Add onboarding tour for first-time TirahAI_NEXT users.

## 12. Data Integrity & Migrations
- [x] Add Alembic migrations for all new Edu_PHP tables (`chat_statuses`, `chat_status_views`, etc.).
- [x] Add seed data scripts for demo institute, users, courses, and chat data.
- [x] Add database backup/restore CLI commands for Edu_PHP.
- [x] Add foreign key constraints audit for `user_messages.group_id` and `chat_group_members`.
- [x] Add data retention policy for `user_messages` and `chat_statuses` (auto-purge expired).

## 14. Accessibility & UX Polish
- [x] Audit all Edu_PHP admin views for WCAG 2.1 AA compliance (contrast, labels, focus).
- [x] Add ARIA labels to TirahAI_NEXT components and navigation.
- [x] Add keyboard navigation support for Vue chat app.
- [x] Add loading skeletons and empty states to all TirahAI_NEXT pages.
- [x] Add toast notifications for success/error feedback in Edu_PHP and TirahAI_NEXT.

## 15. Edu_PHP Modules/Models
- [x] Check Duplications.
- [x] Check Bugs.
- [x] Check Unused, Unnecesry and Unefficient Code.

## 16. Edu_PHP Modules/Controller
- [x] Check Duplications.
- [x] Check Bugs.
- [x] Check Unused, Unnecesry and Unefficient Code.

## 17. Edu_PHP dynamic Variables
- [x] Remove Duplications of Variables and Constants.
- [x] Remove Static Values, replace with dynamic (get from db)
- [x] Remove Unnecesry and Unefficient and Unused Variables, Constants, Values etc.

## 18. Continuous Improvement
- [x] Set up all things for Docker do ready.
- [x] Set up Dependabot or Renovate for dependency updates.
- [x] Add pre-commit hooks (ruff, ESLint, PHP-CS-Fixer, prettier).
- [x] Add Docker multi-stage builds for production images.
- [x] Add staging environment config in `docker-compose.override.yml`.
- [x] Schedule quarterly dependency audit and security patch review.

## --- Future End Zone ---

## 1. Documentation
- [ ] Write README for TirahAI core (setup, API, voice, IoT).
- [ ] Write README for Edu_PHP (installation, routes, module map).
- [ ] Write README for Edu_FLUTTER (build, flavors, API integration).
- [ ] Write README for TirahAI_NEXT (dev, build, deploy).
- [ ] Document environment variables in `.env.example` for all services.
- [ ] Generate OpenAPI spec for FASTAPI v1 and v2.