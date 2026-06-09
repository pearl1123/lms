# Assessments Controller Refactor Plan

**Project:** kaBAGA Academy LMS (CodeIgniter 3)  
**Date:** 2026-06-08  
**Status:** Planning document only — **no code split in Phase 1**

---

## Executive Summary

`application/controllers/Assessments.php` (~2,065 lines) is a **god controller** that owns the full assessment lifecycle: listing, authoring, taking, grading, integrity analytics, video checkpoints, and numerous AJAX endpoints. It extends `CI_Controller` with duplicated auth and uses **role-based gates** instead of `require_permission()`.

This plan defines how to split responsibilities **without changing behavior** in the first migration pass. Phase 1 hardening intentionally **does not** split this file; this document is the approved blueprint for Phase 2.

---

## Current Responsibilities (today)

| Area | Methods | Notes |
|------|---------|-------|
| **Auth / session** | `__construct()` | Duplicates `KA_Controller` logic; AJAX JSON errors |
| **Assessment list** | `index()` | Role-based: admin all, teacher owned, employee enrolled |
| **Integrity analytics** | `integrity_analytics()` | Admin/teacher reporting |
| **Learner take flow** | `take()`, `retake()`, `submit()`, `result()` | Pre/post assessments; modal redirect for video pre-test |
| **Grading** | `grade()`, `save_grade()`, `review()` | Instructor manual grading |
| **Authoring** | `create()`, `edit()`, `save_question()`, `delete_question()`, `delete()` | CRUD + question batch |
| **Checkpoint workspace** | `save_checkpoint_meta()`, `ajax_auto_generate_checkpoints()`, `video_checkpoints()`, `video_checkpoint_submit()`, `migrate_video_checkpoints()` | Video module checkpoints (JSON API) |
| **Ownership guards** | `_check_ownership()`, `_check_ownership_module()`, `_check_ownership_json()`, `_require_manager()` | Role + course instructor checks |
| **Checkpoint helpers** | `_build_checkpoint_workspace()`, `_checkpoint_*`, `_post_whole_video_duration_seconds()` | Private orchestration |
| **Module discovery** | `_get_available_modules()` | Teacher module picker |

### External couplings (must preserve)

| System | Coupling |
|--------|----------|
| **Grading** | `Assessment_service`, `assessment_model`, manual grade POST |
| **Checkpoints** | `Module_video_checkpoint_model`, `Courses::module()` player JSON |
| **Certificates** | Indirect via `Course_completion_service` after module/course completion |
| **Reporting** | Integrity analytics, attempt aggregates in `Reports_model` |
| **Permissions** | `assessments.view`, `.take`, `.add`, `.edit`, `.delete`, `.grade` in `lms_permissions.php` |
| **Courses player** | Flash `pre_assessment_modal` → `Courses::module()` modal DTO |

---

## Target Architecture

```
HTTP Request
     │
     ▼
┌─────────────────────────────────────────────────────────────┐
│  Thin controllers (KA_Controller + require_permission)      │
│  Assessments          — list, take, result, review          │
│  Assessment_admin     — create, edit, delete, questions     │
│  Assessment_grading   — grade, save_grade                   │
│  Assessment_checkpoints — video checkpoint JSON API         │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  Services (orchestration, no HTTP)                          │
│  Assessment_service          (existing — expand)            │
│  Assessment_authoring_service (new)                       │
│  Assessment_grading_service   (new)                         │
│  Video_checkpoint_service     (new)                       │
│  Assessment_policy_service    (new — ownership + gates)     │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  Models (persistence only)                                  │
│  assessment_model, Module_video_checkpoint_model,           │
│  Assessment_integrity_model, course_model                   │
└─────────────────────────────────────────────────────────────┘
```

---

## Services to Create

### 1. `Assessment_authoring_service`

**Extract from:** `create()`, `edit()`, `save_question()`, `_save_questions_batch()`, `_validate_question_payload()`, `delete_question()`, `delete()`, checkpoint meta on edit form.

**Responsibilities:**
- Validate assessment + question payloads
- Persist assessments and questions via `assessment_model`
- Build edit-form DTOs (questions, choices, checkpoint tab state)
- Enforce `assessments.add` / `assessments.edit` via caller controller

**Does not:** Handle HTTP redirects or JSON status codes.

### 2. `Assessment_grading_service`

**Extract from:** `grade()`, `save_grade()`, `review()`, parts of `submit()` for manual-grade items.

**Responsibilities:**
- Load submission bundles for instructor review
- Save per-question scores and feedback
- Recalculate attempt totals and pass/fail
- Notify learners via `Notification_service` when graded

**Permissions:** `assessments.grade`

### 3. `Video_checkpoint_service`

**Extract from:** `video_checkpoints()`, `video_checkpoint_submit()`, `save_checkpoint_meta()`, `ajax_auto_generate_checkpoints()`, `migrate_video_checkpoints()`, `_build_checkpoint_workspace()`, duration helpers.

**Responsibilities:**
- CRUD checkpoint assessments tied to video modules
- Serve player JSON payload (gates, passed IDs, submit URL)
- Process checkpoint answers and update progress
- Auto-generate checkpoint segments from video duration

**Coupling:** Called by `Courses` module player and `Assessments` admin UI.

### 4. `Assessment_policy_service`

**Extract from:** `_check_ownership()`, `_check_ownership_module()`, `_check_ownership_json()`, `_require_manager()`, `_get_available_modules()`, role branches in `index()`.

**Responsibilities:**
- Centralize “may this user view/edit/grade this assessment?”
- Map legacy `admin` / `teacher` behavior to permission keys
- Instructor course scope via `Course_phase2_model::user_manages_course()`

**Replaces:** All inline `$user->role === 'admin'` checks in assessment flows.

### 5. Expand `Assessment_service` (existing)

**Keep / consolidate:**
- `take` / `submit` orchestration already partially here
- `get_module_flow_state()`, `course_module_play_context()`
- Progress aggregates used by `Courses` and `My_courses`

**Move in:** Remaining submit-side logic from controller; integrity hooks via `Assessment_integrity_service`.

---

## Controller Split Strategy

### Phase 2a — Base migration (low risk)

| Step | Action |
|------|--------|
| 1 | Extend `Assessments` from `KA_Controller` |
| 2 | Replace constructor auth with parent; add route-level `require_permission()` |
| 3 | Replace `_require_manager()` with `require_permission(['assessments.add','assessments.edit'])` |
| 4 | Introduce `Assessment_policy_service`; swap ownership private methods |

**No route changes.** All existing URLs remain `assessments/*`.

### Phase 2b — Checkpoint API extraction (medium risk)

| New controller | Routes (proposed) | Source methods |
|----------------|-------------------|----------------|
| `Assessment_checkpoints` | `assessments/video_checkpoints/{id}` (alias) | `video_checkpoints`, `video_checkpoint_submit` |
| | `assessments/save_checkpoint_meta` | `save_checkpoint_meta` |
| | `assessments/ajax_auto_generate_checkpoints` | `ajax_auto_generate_checkpoints` |

Use **route aliases** in `routes.php` so `Courses` player URLs stay unchanged.

### Phase 2c — Admin + grading split (higher risk)

| New controller | Routes | Source methods |
|----------------|--------|----------------|
| `Assessment_admin` | `assessments/create`, `edit/{id}`, `save_question`, `delete_question`, `delete/{id}` | Authoring cluster |
| `Assessment_grading` | `assessments/grade/{aid}/{uid}`, `save_grade`, `review/{id}` | Grading cluster |

Keep `Assessments` as **learner-facing** facade: `index`, `take`, `retake`, `submit`, `result`, `integrity_analytics`.

### Phase 2d — Deprecate monolith

- Reduce `Assessments.php` to &lt;300 lines (learner routes + delegating constructors)
- Mark removed methods `@deprecated` for one release cycle in changelog
- Delete monolith private helpers once services absorb them

---

## Permission Mapping

| Route / action | Permission key |
|----------------|----------------|
| List assessments | `assessments.view` |
| Take / retake / submit / result | `assessments.take` |
| Create / edit / questions / delete | `assessments.add`, `assessments.edit`, `assessments.delete` |
| Grade / review / save_grade | `assessments.grade` |
| Integrity analytics | `assessments.view` + manager scope |
| Checkpoint admin | `assessments.edit` on owning course |
| Checkpoint player JSON | `assessments.take` + enrollment |

Fallback when permission engine inactive: `KA_Controller::require_permission()` already falls back to `admin` role (preserve).

---

## Migration Risk Assessment

| Risk | Severity | Mitigation |
|------|----------|------------|
| **Checkpoint player regression** | **High** | Contract tests on `video_checkpoints` JSON shape; manual QA on video modules |
| **Submit → course completion → certificate** | **High** | End-to-end test: pre → module → post → complete → cert issue |
| **Grading AJAX breakage** | **Medium** | Keep `save_grade` URL; snapshot POST payloads |
| **Pre-assessment modal flash** | **Medium** | Verify `Assessments::submit` → `Courses::module` flash still works |
| **Role vs permission drift** | **Medium** | `Assessment_policy_service` documents parity matrix per group |
| **Merge conflicts** | **Medium** | Split in small PRs: 2a → 2b → 2c |
| **Route / bookmark breakage** | **Low** | Route aliases; no URL changes in 2a |

### Suggested test checklist (per PR)

- [ ] Employee: take pre/post assessment, pass/fail paths
- [ ] Employee: video checkpoint gate and submit
- [ ] Instructor: create/edit assessment with questions
- [ ] Instructor: grade manual question; learner sees result
- [ ] Admin: integrity analytics, delete assessment
- [ ] Certificate issues after course completion with assessments
- [ ] Reports export includes assessment aggregates
- [ ] Permission denied for user without `assessments.*`

---

## Estimated Effort

| Phase | Scope | Effort |
|-------|-------|--------|
| 2a | KA_Controller + permissions + policy service | 3–5 days |
| 2b | Checkpoint controller + `Video_checkpoint_service` | 4–6 days |
| 2c | Admin + grading controllers + services | 5–8 days |
| 2d | Monolith deletion + docs | 2–3 days |
| **Total** | Full Assessments refactor | **3–4 developer-weeks** |

---

## Out of Scope (Phase 1)

- Splitting `Assessments.php` into multiple controllers
- Changing assessment UI or database schema
- Refactoring `assessment_model` (1,769 lines) — separate god-model initiative

---

## Approval

This plan satisfies **MVC Audit P1-2 planning requirement** without functional changes. Implementation should proceed **only after Phase 1 hardening is verified in production**.

*End of Assessments Refactor Plan*
