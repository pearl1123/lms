# kaBAGA Academy LMS — Full Timeline Audit

**Date:** 2026-06-09  
**System:** `C:\xampp\htdocs\lms`  
**Schema:** `application/sql/db_lms.sql` (+ `dbhrmis.sql` external)  
**Framework:** CodeIgniter 3 · **UI:** Tabler + kaBAGA design tokens  

**Legend:** **PASS** = implemented and functional · **PARTIAL** = core exists, gaps remain · **FAIL** = broken or unsafe · **MISSING** = not implemented  

---

## Executive Summary

| Phase | Items | PASS | PARTIAL | FAIL | MISSING |
|-------|-------|------|---------|------|---------|
| System Setup | 5 | 4 | 1 | 0 | 0 |
| System Design | 5 | 1 | 4 | 0 | 0 |
| Libraries | 7 | 5 | 2 | 0 | 0 |
| User Management | 7 | 4 | 3 | 0 | 0 |
| Courses | 8 | 5 | 3 | 0 | 0 |
| Learning Materials | 7 | 4 | 3 | 0 | 0 |
| Assessment | 7 | 4 | 2 | 0 | 1 |
| Certificates | 5 | 4 | 1 | 0 | 0 |
| Monitoring & Reports | 7 | 5 | 2 | 0 | 0 |
| **Total** | **58** | **36** | **21** | **0** | **1** |

**Overall maturity:** Production-capable core LMS with hybrid RBAC, full course/assessment/certificate flows, and reports. Primary gaps: email dispatch pipeline, rubrics, sequential module prerequisites, unified audit trail, points/leaderboard, and formal API layer.

**Low-risk fixes applied this audit:** `User_model::resolve_notification_email()` (HRMIS `emailadd`), sidebar branding from `ka_branding_settings()`, slides/audio enabled in module builder, prior fixes for F2F notice variable and invite form validation.

---

## System Setup

### Database Schema — **PASS**

| | |
|---|---|
| **Files** | `application/sql/db_lms.sql`, `application/sql/migration_*.sql`, `application/sql/dbhrmis.sql` (reference) |
| **Tables** | Full LMS schema: users (`aauth_*`), courses, modules, enrollments, assessments, certificates, notifications, `lms_settings`, Phase 2/3/4 extensions |
| **Notes** | Migrations are manual SQL files; fresh install requires running seeds (`migration_lms_permissions_seed.sql`, `migration_lms_settings.sql`, etc.) |

### Login / Authentication — **PASS**

| | |
|---|---|
| **Files** | `application/controllers/Auth.php`, `application/models/user_model.php`, `application/views/auth/login.php`, `application/helpers/registration_helper.php` |
| **Tables** | `aauth_users`, `aauth_login_attempts` (schema only) |
| **Behavior** | Employee ID + password; lockout after 5 failures (15 min); HRMIS-validated registration; session auth |
| **Gaps** | `aauth_login_attempts` unused; no MFA despite `totp_secret` column |

### System Configuration — **PASS**

| | |
|---|---|
| **Files** | `application/controllers/Settings.php`, `application/models/Settings_model.php`, `application/views/settings/*` |
| **Tables** | `lms_settings` (JSON sections: general, branding, learning, etd, certificates, notifications, security, storage) |
| **Gaps** | Some security settings not enforced app-wide; table must be migrated manually |

### Template / Theme (Tabler) — **PARTIAL**

| | |
|---|---|
| **Files** | `application/views/layouts/main.php`, `header.php`, `sidebar.php`, `navbar.php`, `assets/tabler/*`, `assets/css/ka-saas-*.css` |
| **Behavior** | Tabler CSS/JS + custom kaBAGA tokens; dynamic accent/favicon from settings |
| **Gaps** | Mixed legacy `wmis-*` naming in some assets; auth pages separate from main layout |

### Framework (CodeIgniter 3) — **PASS**

| | |
|---|---|
| **Files** | `application/core/KA_Controller.php`, `application/config/config.php` (`subclass_prefix = KA_`) |
| **Notes** | ~20 controllers on `KA_Controller`; `Auth`, `Assessments`, `Error_pages` remain on `CI_Controller` (intentional for public/legacy) |

---

## System Design

### Database ERD Finalization — **PARTIAL**

| | |
|---|---|
| **Files** | `application/sql/db_lms.sql`, `docs/LIBRARY_SYSTEM.md`, inline schema comments |
| **Gaps** | No standalone ERD diagram file in repo; relationships documented in SQL and audit docs only |

### System Architecture Design — **PARTIAL**

| | |
|---|---|
| **Files** | `docs/MVC_ARCHITECTURE_AUDIT.md`, `docs/ASSESSMENTS_REFACTOR_PLAN.md`, `docs/PERMISSION_SYSTEM_AUDIT.md` |
| **Pattern** | MVC + Services (`*_service.php`) + Helpers + Event dispatcher |
| **Gaps** | `Assessments.php` monolith (~2k lines); no formal architecture diagram |

### UI/UX (Dashboard, Courses, Assessments) — **PARTIAL**

| | |
|---|---|
| **Files** | `application/views/dashboard/*`, `courses/*`, `assessments/*`, `assets/css/*`, `assets/js/*` |
| **Behavior** | Role-based dashboards; course catalog/detail/module player; assessment take/grade/result |
| **Gaps** | Leaderboard under construction; some admin flows tab-heavy (single form) |

### Security & Access Control Framework — **PARTIAL**

| | |
|---|---|
| **Files** | `application/core/KA_Controller.php`, `application/config/lms_permissions.php`, `application/models/Permission_model.php`, `application/controllers/Permissions.php` |
| **Tables** | `aauth_groups`, `aauth_perms`, `aauth_perm_to_group`, `aauth_user_to_group`, `aauth_perm_deny_to_user` |
| **Gaps** | Hybrid `aauth_users.role` + group permissions; users without groups bypass engine; `Assessments` uses custom role checks |

### API / Integration Structure Planning — **PARTIAL**

| | |
|---|---|
| **Files** | `application/config/event_listeners.php`, `application/libraries/Event_dispatcher.php`, scattered JSON endpoints |
| **Gaps** | No REST API layer, versioning, or OpenAPI; HRMIS read-only second DB; ad-hoc AJAX JSON per controller |

---

## Libraries

### Role & Module Permission Library — **PASS**

| | |
|---|---|
| **Files** | `application/config/lms_permissions.php`, `application/helpers/permission_helper.php`, `application/libraries/Permission_seed_service.php`, `application/models/Permission_model.php` |
| **Tables** | `aauth_perm_module_main`, `aauth_perm_module_sub`, `aauth_perms`, `aauth_perm_to_group` |

### Notification Library — **PARTIAL**

| | |
|---|---|
| **Files** | `application/services/Notification_service.php`, `application/models/notification_model.php`, `application/libraries/Notification_listener.php`, `application/constants/Notification_types.php` |
| **Tables** | `lib_notification_type`, `lib_notification`, `lib_user_notification`, `lib_notification_channel` |
| **Gaps** | In-app complete; `afterNotificationCreated()` empty (no email dispatch); invitation not in event_listeners |

### Course Access Control Library — **PASS**

| | |
|---|---|
| **Files** | `application/models/Course_phase2_model.php`, `application/helpers/course_phase2_helper.php`, `application/services/Course_completion_service.php` |
| **Tables** | `lib_course_access_type`, `course_departments`, `course_professions`, `course_invitations`, `enrollments` |
| **Behavior** | open / approval_required / invitation_only / hidden; dept/profession visibility |

### Assessment Engine Library — **PASS**

| | |
|---|---|
| **Files** | `application/services/Assessment_service.php`, `application/models/assessment_model.php`, `application/models/Assessment_attempt_order_model.php`, `application/services/Assessment_integrity_service.php` |
| **Tables** | `lib_assessments`, `lib_assessment_questions`, `lib_assessment_choices`, `assessment_answers`, `assessment_attempt_orders` |

### Certificate Generation Library — **PASS**

| | |
|---|---|
| **Files** | `application/services/Certificate_service.php`, `application/helpers/certificate_pdf_helper.php`, `application/libraries/Pdf.php`, `application/models/certificate_model.php` |
| **Tables** | `lib_certificates`, `lib_certificate_logs`, `certificate_signatories`, `courses.certificate_prefix` |

### Dashboard Statistics Library — **PASS**

| | |
|---|---|
| **Files** | `application/models/dashboard_model.php`, `application/models/Reports_model.php` |
| **Tables** | `aauth_users`, `courses`, `enrollments`, `module_progress`, `lib_certificates` |

### Audit Trail — **PARTIAL**

| | |
|---|---|
| **Files** | `application/models/User_access_model.php` (`log_audit`), `application/models/certificate_model.php` (`lib_certificate_logs`) |
| **Tables** | `lms_user_access_audit`, `lib_certificate_logs`, `activity_logs` (**unused**) |
| **Gaps** | No unified audit UI; no login/reset logging; `activity_logs` never written |

---

## User Management Module

### User Account Management — **PARTIAL**

| | |
|---|---|
| **Files** | `application/controllers/Users.php`, `application/models/user_model.php`, `application/views/administrator/users/management.php` |
| **Tables** | `aauth_users` |
| **Gaps** | Access control focus; no create/delete user UI in LMS (registration via Auth + HRMIS) |

### Role-Based Access Control — **PARTIAL**

| | |
|---|---|
| **Files** | `application/controllers/Permissions.php`, `application/views/administrator/permissions/group_matrix.php`, `User_access_service.php` |
| **Tables** | `aauth_groups`, `aauth_user_to_group`, `aauth_perm_*` |
| **Gaps** | Dual legacy `role` enum + groups; `Assessments` not on permission manifest |

### Office / Department Assignment — **PASS**

| | |
|---|---|
| **Files** | `Course_phase2_model.php`, HRMIS integration, `course_departments` |
| **Tables** | `course_departments`, HRMIS `tbldepartment` / `tblemployee.Department` |
| **Behavior** | Course visibility by department; user office from HRMIS at registration |

### User Permissions Management — **PASS**

| | |
|---|---|
| **Files** | `Users.php` (grant/deny overrides), `Permissions.php` (group matrix), `assets/js/group_permissions.js` |
| **Tables** | `aauth_perm_to_user`, `aauth_perm_deny_to_user`, `lms_user_access_audit` |

### User Profile Management — **PASS**

| | |
|---|---|
| **Files** | `application/controllers/Profile.php`, `application/models/Profile_model.php`, `application/views/profile/*` |
| **Tables** | `aauth_users` (+ `avatar_path`, `bio`, `contact_number` via migration) |
| **Behavior** | HRMIS-linked read-only identity fields; avatar upload with validation |

### Password Reset and Security — **PASS**

| | |
|---|---|
| **Files** | `Auth.php`, `application/services/Password_reset_service.php`, `Email_service.php` |
| **Tables** | `aauth_users.token`, `token_date_*`, `forgot_exp` |
| **Gaps** | Email-dependent; dev fallback link in non-production |

### User Status Management — **PASS**

| | |
|---|---|
| **Files** | `user_model.php`, `Users.php`, `aauth_users.status`, `DELETED`, `locked_until` |
| **Tables** | `aauth_users` |

---

## Courses Module

### Course Creation & Management — **PASS**

| | |
|---|---|
| **Files** | `Manage_courses.php`, `course_model.php`, `edit_course_workspace.php`, `manage_courses.js` |
| **Tables** | `courses`, `course_modules`, `course_categories`, `course_instructors` |

### Course Registration Workflow — **PASS**

| | |
|---|---|
| **Files** | `Courses.php` (`enroll`), `Enrollments.php`, `Course_phase2_model.php` |
| **Tables** | `enrollments`, `registration_attempts`, `course_invitations` |
| **Flow** | Open auto-approve · approval queue · invitation-only block |

### Course Access Rules — **PASS**

| | |
|---|---|
| **Files** | `Course_phase2_model.php`, `course_phase2_helper.php` |
| **Tables** | `lib_course_access_type`, `course_departments`, `course_professions` |

### Asynchronous Course Module — **PARTIAL**

| | |
|---|---|
| **Files** | Modality library, self-paced module player, `Course_completion_service.php` |
| **Tables** | `lib_course_modality`, `module_progress` |
| **Gaps** | “Async” is a modality label only; no distinct async completion rules vs sync |

### In-Person Course Module — **PARTIAL**

| | |
|---|---|
| **Files** | `etd_phase4_helper.php`, `components/etd_f2f_notice.php`, `courses/detail.php` |
| **Tables** | `lib_course_modality`, `courses.schedule_date`, `schedule_time`, `venue` |
| **Gaps** | F2F notice on enroll/cards; schedule/venue columns **unused** in UI |

### Course Deadline & Expiry Control — **PARTIAL**

| | |
|---|---|
| **Files** | `course_model.php` (`auto_unpublish_expired_courses`), `phase3_batches_block.php` |
| **Tables** | `courses.expiry_days`, `course_batches.start_date`, `end_date` |
| **Gaps** | No enrollment deadline; `assignments.due_date` orphan; reminder cron missing |

### Course Enrollment Monitoring — **PASS**

| | |
|---|---|
| **Files** | `Enrollments.php`, `Manage_courses.php` (invitations), `Reports_model.php` |
| **Tables** | `enrollments`, `course_invitations` |

### Course Completion Tracking — **PASS**

| | |
|---|---|
| **Files** | `Course_completion_service.php`, `course_cta_helper.php`, `Courses.php`, `My_courses_model.php` |
| **Tables** | `module_progress`, `lib_assessments`, `lib_certificates` |

---

## Learning Materials Module

### PDF Learning Material Viewer — **PASS**

| | |
|---|---|
| **Files** | `courses/module.php`, `Manage_courses.php` (`upload_module_file`), `assets/js/module.js` |
| **Tables** | `course_modules.content_type=pdf`, `uploads/modules/{course_id}/` |

### Video / Zoom Recording Viewer — **PASS**

| | |
|---|---|
| **Files** | `courses/module.php`, video checkpoints via `Assessment_service.php` |
| **Tables** | `course_modules` (`video`, `zoom_recording` → `meeting_link`), `course_module_video_checkpoints` |

### Slide Presentation Viewer — **PARTIAL**

| | |
|---|---|
| **Files** | `course_phase3_helper.php`, `courses/module.php` |
| **Behavior** | PDF embed for slides; PPTX download-only |
| **Gaps** | No native slide deck player; admin picker now enabled (was “coming soon”) |

### Voice Recording Player — **PARTIAL**

| | |
|---|---|
| **Files** | `courses/module.php` (HTML5 audio) |
| **Gaps** | Admin upload UX minimal; no dedicated audio upload field (URL/path based) |

### Structured Module Navigation — **PASS**

| | |
|---|---|
| **Files** | `courses/detail.php`, `courses/module.php`, `course_cta_helper.php` (`build_resume_url`) |
| **Tables** | `course_modules.module_order`, `module_progress.resume_state` |

### Module Locking / Prerequisite Logic — **PARTIAL**

| | |
|---|---|
| **Files** | `Assessment_service.php` (pre/post/checkpoint gates), `courses/module.php` |
| **Implemented** | Enrollment lock · pre-assessment gate · video checkpoint gate · post-assessment for completion |
| **Gaps** | **No sequential module order** (module N does not require N−1); no course-to-course prerequisites |

### Learning Progress Tracking — **PASS**

| | |
|---|---|
| **Files** | `Course_completion_service.php`, `Courses.php` (`progress_state`, `complete_module`), `Progress.php` (stub route) |
| **Tables** | `module_progress` |

---

## Assessment Module

### Multiple Choice Assessment Engine — **PASS**

| | |
|---|---|
| **Files** | `assessment_model.php`, `Assessments.php`, `assessments/take.php` |
| **Tables** | `lib_assessment_questions`, `lib_assessment_choices`, `assessment_answers` |

### Essay Submission Interface — **PARTIAL**

| | |
|---|---|
| **Files** | `assessments/take.php`, `assessments/grade.php`, `Assessments.php` |
| **Tables** | `lib_assessment_questions.essay_response_mode`, `assessment_answers.essay_file_path` |
| **Gaps** | Text essay works; PDF mode schema-only; builder does not save `essay_response_mode` |

### Self-Reflection (Likert Scale) — **PASS**

| | |
|---|---|
| **Files** | `assessments/take.php` (1–5 scale), manual grading in `grade.php` |
| **Gaps** | Fixed 1–5; no custom labels; always manual score |

### Fill-in-the-Blanks Assessment — **PASS**

| | |
|---|---|
| **Files** | `assessment_model.php` (case-insensitive exact match) |
| **Gaps** | No fuzzy/partial credit |

### Assessment Validation Logic — **PASS**

| | |
|---|---|
| **Files** | `Assessment_service.php`, `ka_assessment_pass_threshold()` (75%), integrity service |
| **Tables** | `assessment_attempt_orders`, `lib_assessments.randomize_questions` |
| **Gaps** | Threshold not per-course configurable |

### Rubric-Based Evaluation — **MISSING**

| | |
|---|---|
| **Gaps** | No rubric tables, UI, or scoring criteria; essay/likert use free-form 0–100 |

### Assessment Result Recording — **PASS**

| | |
|---|---|
| **Files** | `assessment_model.php`, `assessments/result.php`, `Assessments.php` |
| **Tables** | `assessment_answers`, `assessment_attempt_orders` |

---

## Certificate Generation Module

### Certificate Template Management — **PARTIAL**

| | |
|---|---|
| **Files** | `certificate_pdf_helper.php`, `views/certificates/templates/*`, `settings/certificates.php`, `Lib_certificate_templates.php` |
| **Tables** | `lms_settings.certificates.pdf_template`; `lib_certificate_templates` (**CRUD only, not wired to PDF**) |
| **Templates** | `official_lcp_certificate`, `premium_lcp_certificate`, `template_pdf`, `minimalist`, `modern`, `template_saas_preview` |

### Automatic Certificate Generation — **PASS**

| | |
|---|---|
| **Files** | `Certificate_service.php`, `Courses.php` (on completion), `Course_completion_service.php` (eligibility) |
| **Tables** | `lib_certificates` |

### Serial Coding Logic — **PASS**

| | |
|---|---|
| **Files** | `certificate_model.php` (`_generate_code`) |
| **Format** | `{PREFIX}-{YEAR}-{NNNN}` |

### Certificate Data Mapping — **PASS**

| | |
|---|---|
| **Files** | `certificate_pdf_helper.php` (`ka_cert_build_view_data`), `certificate_model.php` (`resolve_signatories_for_pdf`) |
| **Tables** | `lib_certificates`, `certificate_signatories`, `courses` |

### Certificate Download — **PASS**

| | |
|---|---|
| **Files** | `Certificates.php`, DOMPDF via `Pdf.php`, `uploads/certificates/` |
| **Gaps** | E-signature image upload UI partial; `template_pdf.php` text-only signatures |

---

## Monitoring & Reports Module

### LMS Dashboard Statistics — **PASS**

| | |
|---|---|
| **Files** | `Dashboard.php`, `dashboard_model.php`, role views |
| **Tables** | Aggregates across users, courses, enrollments, certs |

### Course Completion Reports — **PASS**

| | |
|---|---|
| **Files** | `Reports.php`, `Reports_model.php`, `reports/course_performance.php` |

### Employee Participation Reports — **PASS**

| | |
|---|---|
| **Files** | `reports/learner_insights.php`, `reports/executive_overview.php` |

### Demographic Reports — **PARTIAL**

| | |
|---|---|
| **Files** | `reports/hrmis_analytics.php` |
| **Gaps** | Requires HRMIS DB connection; degrades gracefully if unavailable |

### Course Activity Reports — **PASS**

| | |
|---|---|
| **Files** | `reports/learning_analytics.php`, `learning_notes_analytics.php` |

### Graphs and Visuals — **PASS**

| | |
|---|---|
| **Files** | ApexCharts in dashboard/reports views, `assets/js/reports.js` |

### Export Reports (PDF / Excel) — **PARTIAL**

| | |
|---|---|
| **Files** | `Reports.php`, `Reports_export.php`, `report_export_helper.php` |
| **Formats** | CSV · Excel 2003 XML (`.xls`) · PDF (dompdf) |
| **Gaps** | No true `.xlsx`; dompdf vendor required |

---

## Single Sources of Truth (verified)

| Domain | Authority |
|--------|-----------|
| Auth / permissions | `KA_Controller`, `require_permission()`, `lms_permissions.php`, `Permission_model` |
| Course progress / CTA | `Course_completion_service`, `get_course_cta()`, `course_cta_helper.php` |
| Assessment pass / gates | `Assessment_service`, `ka_assessment_pass_threshold()` |
| Certificate PDF | `Certificate_service`, `certificate_pdf_helper.php` |
| Branding display | `ka_branding_settings()` → layout header + sidebar |
| HyFlex label | `etd_modality_display_label()` |
| Notification email (prep) | `User_model::resolve_notification_email()` → HRMIS `tblemployee.emailadd` |

---

## Low-Risk Implementations (this audit)

| Item | Status |
|------|--------|
| `User_model::resolve_notification_email()` | ✅ Applied |
| Sidebar logo/name from `ka_branding_settings()` | ✅ Applied |
| Slides/audio module types in admin picker | ✅ Applied |
| F2F `$show_f2f` undefined (detail.php) | ✅ Prior fix |
| Invite form validation vs hidden cert fields | ✅ Prior fix |

---

## Recommended Next Steps

1. **P1 — Email dispatch** (HIGH): Wire `afterNotificationCreated()` + HRMIS email resolver + SMTP staging test  
2. **P2 — Essay PDF + e-sign + retake alignment** (MEDIUM): Per `LMS_GAP_ANALYSIS.md`  
3. **P2 — Sequential module prerequisites** (MEDIUM): Policy + `Assessment_service` gate  
4. **P3 — Rubrics** (MAJOR): New schema + grader UI  
5. **P3 — Points / leaderboard** (MAJOR): Replace `under_construction` route  
6. **P3 — Unified audit trail** (MEDIUM): Wire `activity_logs` or extend `lms_user_access_audit` viewer  

---

*End of Full Timeline Audit*
