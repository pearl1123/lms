# LMS System-Wide Library Modules

Database source: `application/sql/db_lms.sql`  
Registry: `application/config/library_registry.php`

## Architecture

| Layer | Path |
|-------|------|
| Registry (schema-driven config) | `application/config/library_registry.php` |
| Base controller | `application/core/KA_Library_controller.php` |
| Generic model | `application/models/Library_crud_model.php` |
| Shared UI | `application/views/libraries/crud/*` |
| Per-table wrappers | `application/views/libraries/{key}/listview.php` |
| Per-table controllers | `application/controllers/libraries/{Class}.php` |
| Hub | `Libraries_portal` → `index.php/libraries` |

**Windows note:** Top-level `Libraries.php` was renamed to `Libraries_portal.php` so the `controllers/libraries/` folder can coexist on case-insensitive filesystems.

## Detected library tables (included)

| Table | Module key | Group |
|-------|------------|-------|
| `course_categories` | `course_categories` | Course |
| `lib_course_access_type` | `lib_course_access_type` | Course |
| `lib_course_modality` | `lib_course_modality` | Course |
| `lib_assessment_questions` | `lib_assessment_questions` | Assessment |
| `lib_assessment_choices` | `assessment_choices` | Assessment (custom UI) |
| `lib_certificate_types` | `lib_certificate_types` | Certificate |
| `lib_certificate_templates` | `lib_certificate_templates` | Certificate |
| `lib_notification_type` | `lib_notification_type` | Notification |
| `lib_notification_channel` | `lib_notification_channel` | Notification |
| `lms_settings` | `lms_settings` | System |

## Excluded tables (and why)

| Table | Reason |
|-------|--------|
| `aauth_*` | External auth package — not LMS-owned lookup |
| `activity_logs` | Audit / log |
| `assessment_answers` | Transactional attempt answers |
| `assessment_attempt_orders` | Per-attempt shuffle state |
| `assignments` | Course transactional entity |
| `certificate_signatories` | Per-course records, not global lookup |
| `course_batches` | Operational batch scheduling |
| `course_categories_map` | Pivot |
| `course_departments` | Pivot (HRMIS) |
| `course_instructors` | Pivot |
| `course_invitations` | Transactional invitations |
| `course_module_video_checkpoints` | Legacy / module-bound |
| `course_modules` | Core content entity |
| `course_professions` | Pivot (HRMIS) |
| `courses` | Core entity |
| `enrollments` | Transactional |
| `learning_notes` | User-owned notes |
| `lib_assessments` | Assessment engine (module FK, content_version) |
| `lib_certificate_logs` | Audit log |
| `lib_certificates` | Issued certificate transactions |
| `lib_notification` | Notification instances |
| `lib_notification_channel_link` | Pivot / delivery log |
| `lib_user_notification` | Per-user inbox |
| `module_progress` | Progress tracking |
| `registration_attempts` | Security log |
| `submissions` | Assignment submissions |
| `user_youtube_quiz_passes` | Progress / pass tracking |

## Notifications (SweetAlert2)

All library modules use `assets/js/libraries_notify.js` which wraps the global `KA.toast()` helper (see `application/views/layouts/alerts.php`).

- **Success toasts** (3s, top-end): add, update, archive, restore
- **Archive confirmation**: `KA_SWAL.PRESETS.archiveLibrary`
- **Flash messages** on `/libraries/*` pages are converted to toasts automatically
- **No** `alert()` or `confirm()` in library JS


- Hub: `index.php/libraries`
- Module: `index.php/libraries/{registry_key}`
- CRUD: `POST …/create`, `…/update/{id}`, `…/delete/{id}`, `…/restore/{id}`
- Assessment choices API: `GET …/libraries/assessment_choices/get_by_question/{question_id}`

## Adding a new library

1. Confirm table qualifies (lookup/reference, not transactional).
2. Add entry to `library_registry.php` (`modules` + `group`).
3. Create `application/controllers/libraries/{Class}.php` extending `KA_Library_controller`.
4. Add `application/views/libraries/{key}/listview.php` wrapper loading `libraries/crud/listview`.
5. Clear route cache if used; CI3 default routing handles CRUD methods.

## Migrations

No new migrations required for existing tables in `db_lms.sql`.

## QA checklist (per generic module)

- [ ] Admin can open list; non-admin redirected
- [ ] Create / update / archive / restore (if `soft_delete` configured)
- [ ] Search filter works on `searchable` columns
- [ ] FK dropdowns populate from related tables
- [ ] `lms_settings`: no archive buttons
- [ ] `lib_notification_channel`: archive toggles `is_active`
- [ ] Assessment choices custom module still grades by choice `id`

## php -l

Run from project root:

```bash
php -l application/core/KA_Library_controller.php
php -l application/models/Library_crud_model.php
php -l application/controllers/Libraries_portal.php
php -l application/controllers/libraries/*.php
```
