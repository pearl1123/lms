# kaBAGA Academy LMS — Gap Analysis & Implementation Plan

**Date:** 2026-06-09  
**Companions:** `docs/LMS_FULL_TIMELINE_AUDIT.md`, `docs/LMS_ENHANCEMENT_AUDIT.md`

---

## Audit Coverage

This gap analysis extends the 13-item enhancement audit to the **full project timeline** (58 checklist items across System Setup → Reports). See `LMS_FULL_TIMELINE_AUDIT.md` for per-item PASS/PARTIAL/MISSING status, file paths, and DB tables.

**Headline:** 36 PASS · 21 PARTIAL · 0 FAIL · 1 MISSING (rubrics)

---

## Gap Summary by Priority

| Priority | Area | Status | Risk |
|----------|------|--------|------|
| P1 | Email notification dispatch | PARTIAL | **High** |
| P1 | Permission seed on fresh DB | PARTIAL | High |
| P2 | Essay PDF workflow | PARTIAL | Medium |
| P2 | E-signature upload on certs | PARTIAL | Medium |
| P2 | Full-course retake alignment | PARTIAL | Medium |
| P2 | Sequential module prerequisites | PARTIAL | Medium |
| P2 | PHPUnit / smoke tests | MISSING | Medium |
| P3 | Rubric-based evaluation | **MISSING** | Major |
| P3 | Points / leaderboard | MISSING | Major |
| P3 | Unified audit trail UI | PARTIAL | Medium |
| P3 | REST API layer | MISSING | Medium |
| P3 | Category hierarchy | PARTIAL | Medium |
| P4 | Avatar resize service | PARTIAL | Low |
| P4 | Branding CSS cleanup (`wmis-*`) | PARTIAL | Low |

---

## Enhancement Audit Cross-Reference (#1–13)

| # | Enhancement | Status | Risk | Priority |
|---|-------------|--------|------|----------|
| 1 | Profile photo | PARTIAL | Low | P2 |
| 2 | F2F notice | PARTIAL | Low | P2 |
| 3 | HyFlex | PARTIAL | Low | P3 |
| 4 | Categories | PARTIAL | Medium | P3 |
| 5 | Retake rules | PARTIAL | Medium | P2 |
| 6 | Randomization | **PASS** | — | — |
| 7 | Essay PDF | PARTIAL | Medium | P2 |
| 8 | E-signature | PARTIAL | Medium | P2 |
| 9 | Points/leaderboard | MISSING | Low | P4 |
| 10 | Cert download | **PASS** | Low | — |
| 11 | Email events | PARTIAL | **High** | P1 |
| 12 | Branding | PARTIAL | Low | P3 |
| 13 | Test automation | MISSING | Medium | P2 |

---

## Missing Functions

| Function | Module | Risk | Recommended Fix |
|----------|--------|------|-----------------|
| Email on notification create | Notifications | **High** | `Notification_service::afterNotificationCreated()` + toggles + `resolve_notification_email()` |
| Invitation email (non-user) | Courses | High | `Manage_courses` direct `Email_service` for raw email |
| Reminder / deadline cron | Courses | Medium | `php index.php cron send_reminders` |
| Rubric CRUD + scoring | Assessment | Major | New tables + grader UI |
| Sequential module lock | Learning | Medium | `Assessment_service` + `module_order` enforcement |
| Full-course retake reset | Assessment | Medium | `Etd_retake_service` + all modules |
| Essay PDF upload + download | Assessment | Medium | `Essay_submission_service` + multipart submit |
| Signature image upload | Certificates | Medium | phase3 signatories + `template_pdf.php` |
| Category `parent_id` tree | Libraries | Medium | Migration + nested CRUD |
| Points award + leaderboard | Gamification | Major | `user_points` tables + replace under_construction |
| Unified audit viewer | System | Medium | Read `lms_user_access_audit` + wire `activity_logs` |
| REST API v1 | Integration | Medium | `application/controllers/api/` + auth middleware |
| Avatar resize/thumbnail | Profile | Low | `Avatar_service::process_upload()` |
| F2F schedule/venue UI | Courses | Low | Wire `courses.schedule_*` fields in manage UI |
| `lib_certificate_templates` → PDF | Certificates | Medium | Connect DB templates to render pipeline |

---

## Risk Matrix

### High Risk (address before production email reliance)

| Gap | Impact | Mitigation |
|-----|--------|------------|
| Email toggles not wired | Users miss invitations, certs, approvals | Implement dispatch in `afterNotificationCreated()`; use HRMIS `emailadd`; staging SMTP test |
| Permission seed on fresh DB | Redirect/403 loops | Run `/permissions/sync`; document in `DEVELOPMENT.md` |

### Medium Risk

| Gap | Impact | Mitigation |
|-----|--------|------------|
| Essay PDF missing | Audit/compliance gap | Multipart submit + grader download |
| Retake = module not course | Policy mismatch | `Etd_retake_service` or fix copy |
| No sequential module lock | Learners skip content | Enforce in `Courses::module` |
| No automated tests | Regression on refactor | PHPUnit smoke suite |
| E-signature upload | Certs lack visual authority | phase3 upload + PDF slots |
| Demographic reports | HRMIS dependency | Graceful fallback (exists) |

### Low Risk

| Gap | Impact | Mitigation |
|-----|--------|------------|
| Avatar resize | Storage bloat | Optional thumbnail service |
| Branding CSS legacy | Visual inconsistency | Incremental `wmis-*` → `ka-*` |
| Points/leaderboard | Feature not live | Keep under construction |
| Slides/audio admin | Was blocked in UI | ✅ Enabled in module picker |

---

## Implementation Plan

### Quick Wins (1–3 days) — **DONE or READY**

| Item | Status |
|------|--------|
| Profile upload validation (size/MIME) | ✅ Applied |
| HyFlex display consistency | ✅ Applied |
| `migration_hybrid_to_hyflex.sql` | ✅ Created (run manually) |
| F2F notice on enroll + cards + detail | ✅ Applied |
| `Certificates::require_permission()` | ✅ Applied |
| `get_course_cta()` single source | ✅ Applied |
| `User_model::resolve_notification_email()` (HRMIS) | ✅ Applied |
| Sidebar dynamic logo from branding | ✅ Applied |
| Slides/audio module types in admin | ✅ Applied |
| Invite form / hidden-tab validation fix | ✅ Applied |

### Medium Changes (1–2 weeks)

| Item | Approach |
|------|----------|
| Email notification dispatch | `afterNotificationCreated()` → `Email_service` + HRMIS email + settings toggles |
| Essay PDF workflow | Multipart submit; `uploads/essay_submissions/`; grader download |
| E-signature upload | Extend `sync_course_signatories`; images in `template_pdf.php` |
| Full-course retake | `Etd_retake_service` — confirm policy first |
| Sequential module prerequisites | Gate in `Courses::module` using `module_order` |
| F2F schedule fields | Admin UI for `schedule_date`, `venue` |
| Category hierarchy | `parent_id` migration + tree UI |
| Branding cleanup | `ka_branding_settings()` everywhere; remove `*bk.php` |

### Major Changes (3–6 weeks)

| Item | Approach |
|------|----------|
| Rubric-based evaluation | `assessment_rubrics`, criteria rows, grader UI |
| Points & leaderboard | `user_points`, `Leaderboard_service`; replace stub route |
| Assessments controller split | Per `ASSESSMENTS_REFACTOR_PLAN.md` |
| PHPUnit + CI pipeline | `tests/` smoke: auth, progress, certs |
| REST API layer | Versioned JSON API with session/token auth |
| Unified audit platform | `activity_logs` writer + admin viewer |
| Avatar processing service | Resize, WebP, cleanup |

---

## MEDIUM/HIGH-RISK — Proposal Required Before Code

### A. Email Event Pipeline (HIGH)

```
Event_dispatcher / Manage_courses
  → Notification_service::send_database_once()
    → afterNotificationCreated()
      → User_model::resolve_notification_email()  ← HRMIS tblemployee.emailadd
      → if settings.notifications.{toggle} enabled
        → Email_service::send_template()
```

**Prerequisites:** SMTP configured in staging; test invite / approval / certificate toggles.

### B. Essay PDF (MEDIUM)

- Upload dir `uploads/essay_submissions/{user_id}/{assessment_id}/`
- Max 5 MB PDF; `essay_response_mode` in builder
- Grader: `Assessments::download_essay/{answer_id}`

### C. Rubrics (MAJOR)

- New schema: `assessment_rubrics`, `assessment_rubric_criteria`
- Grader UI replaces free-form 0–100 for configured questions
- Stakeholder sign-off on rubric templates

### D. Full-Course Retake (MEDIUM)

- **Option A:** Reset all modules (audit assumes this for non-managerial ETD)
- **Option B:** Keep module-only reset; fix UI copy
- Do not use `reset_course_learning_state()` (archives certificates)

### E. Sequential Module Lock (MEDIUM)

- Policy: require module N−1 `completed` before module N
- Implement in `Assessment_service::can_access_module()` + redirect

---

## Database Tables Reference

| Module | Primary Tables |
|--------|----------------|
| Auth / Users | `aauth_users`, `aauth_groups`, `aauth_perm_*`, `lms_user_access_audit` |
| HRMIS (external) | `tblemployee.emailadd`, `tbldepartment` |
| Courses | `courses`, `course_modules`, `enrollments`, `course_invitations`, `course_batches` |
| Progress | `module_progress`, `lib_assessments` |
| Assessment | `lib_assessment_questions`, `assessment_answers`, `assessment_attempt_orders` |
| Certificates | `lib_certificates`, `certificate_signatories`, `lib_certificate_logs` |
| Notifications | `lib_notification`, `lib_user_notification`, `lms_settings.notifications` |
| Reports | Same aggregates as dashboard + HRMIS optional |
| Settings / Branding | `lms_settings`, `lib_course_modality` |
| Audit (partial) | `lms_user_access_audit`, `activity_logs` (unused) |
| Points | *(none — to be created)* |
| Rubrics | *(none — to be created)* |

---

## Recommended Execution Order

1. Run pending SQL migrations on dev DB (`hybrid_to_hyflex`, permissions seed, `lms_settings`)
2. Wire email dispatch with HRMIS email resolution (P1)
3. Essay PDF submit + grader download (P2)
4. E-signature upload + `template_pdf` images (P2)
5. Retake policy decision + `Etd_retake_service` (P2)
6. Sequential module lock if required by training policy (P2)
7. PHPUnit smoke suite (P2)
8. Rubrics / points / API (P3 — stakeholder approval)

---

*End of Gap Analysis*
