# Database Relationships — Foreign Key Map

**Primary schema:** `application/sql/db_lms.sql`

---

## Users & Permissions

```
aauth_users.id
  ← courses.created_by
  ← enrollments.user_id
  ← module_progress.user_id
  ← assessment_answers.user_id
  ← lib_certificates.user_id
  ← activity_logs.user_id
  ← lms_user_access_audit.user_id / actor_id

aauth_user_to_group (user_id → aauth_users.id, group_id → aauth_groups.id)
aauth_perm_to_group (perm_id → aauth_perms.id, group_id → aauth_groups.id)
aauth_perm_to_user (user_id, perm_id)
aauth_perm_deny_to_user (user_id, perm_id)
```

---

## Courses

```
courses.id
  ← course_modules.course_id
  ← enrollments.course_id
  ← lib_certificates.course_id
  ← certificate_signatories.course_id
  ← course_categories_map.course_id
  ← course_instructors.course_id
  ← course_departments.course_id
  ← course_invitations.course_id
  ← course_batches.course_id

courses.modality_id → lib_course_modality.modality_id
courses.category_id → course_categories.id (legacy single category)
courses.access_type_id → lib_course_access_type.access_type_id

course_modules.id
  ← lib_assessments.module_id
  ← module_progress.module_id
  ← course_module_prerequisites.module_id
  ← course_module_prerequisites.prerequisite_module_id
```

---

## Assessments

```
lib_assessments.id
  ← lib_assessment_questions.assessment_id
  ← assessment_attempt_orders.assessment_id

lib_assessment_questions.id
  ← lib_assessment_choices.question_id
  ← assessment_answers.question_id
```

---

## Notifications

```
lib_notification_type.type_id ← lib_notification.type_id
lib_notification.id ← lib_user_notification.notification_id
notification_email_log.notification_id → lib_notification.id (optional)
notification_email_log.user_id → aauth_users.id
```

---

## HRMIS (external database group `hrmis`)

```
tblemployee.idno  ↔  aauth_users.employee_id  (logical, not FK)
tblemployee.emailadd  →  resolve_notification_email() (runtime)
tbldepartment  →  course_departments.department_id (HRMIS id)
```

---

## Phase 5 Additions

| Table / Column | Relationship |
|----------------|--------------|
| `courses.pass_threshold_pct` | Optional override for assessments |
| `lib_assessments.pass_threshold_pct` | Optional per-assessment override |
| `courses.enforce_sequential_modules` | Gates module access order |
| `lms_hrmis_demographic_cache.employee_id` | Snapshot when HRMIS offline |

---

## Orphan / Legacy Tables

| Table | Status |
|-------|--------|
| `assignments` | Schema only — no controller |
| `lib_certificate_templates` | CRUD library — not wired to PDF engine |
| `aauth_login_attempts` | Schema only — lockout uses `aauth_users.failed_attempts` |
