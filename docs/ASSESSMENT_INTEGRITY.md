# Assessment Integrity Sprint

Enterprise-grade pre/post assessment randomization, content versioning, and integrity analytics.

## Schema

Run after backup:

```bash
mysql -u root lms < application/sql/migration_assessment_integrity.sql
```

Also ensure Phase 4 migration is applied if `randomize_questions` is missing:

```bash
mysql -u root lms < application/sql/migration_phase4_etd.sql
```

### Objects

| Object | Purpose |
|--------|---------|
| `lib_assessments.content_version` | Increments on question/choice edits |
| `assessment_attempt_orders` | Persistent question/choice order per learner attempt |

## Behavior

### Persistent attempts (Phase 1)

- **Source of truth:** `assessment_attempt_orders` (status `active` until submit or retake).
- **Session:** Optional cache only; cleared on submit/retake.
- **Pre/post only:** Checkpoints unchanged (DB order + index scoring).
- **Survives:** Refresh, logout, new browser, session timeout.

### Content versioning (Phase 2)

- `content_version` bumps on: question add/edit/delete, choice add/edit/delete (via `save_choices`), batch create.
- New attempts snapshot current version at creation.
- Active attempt with older version → `is_legacy_version = 1`; order preserved.

### Integrity Analytics (Phase 3)

- URL: `assessments/integrity_analytics` (admin/teacher).
- Menu: Assessments → **Integrity Analytics** button on manager list.

## Deployment checklist

- [ ] Backup database
- [ ] Run `migration_assessment_integrity.sql`
- [ ] Run `migration_phase4_etd.sql` if not already applied
- [ ] `php -l` on modified PHP files (see below)
- [ ] Smoke-test employee pre-test: refresh mid-attempt → same order
- [ ] Smoke-test retake → new order in DB (`status=retaken` old row)
- [ ] Smoke-test checkpoint video submit → no `ASSESSMENT_RANDOMIZE` / no attempt row
- [ ] Open Integrity Analytics as teacher/admin

### PHP syntax validation

```bash
php -l application/models/assessment_model.php
php -l application/models/Assessment_attempt_order_model.php
php -l application/models/Assessment_integrity_model.php
php -l application/services/Assessment_integrity_service.php
php -l application/libraries/Assessment_integrity_service.php
php -l application/controllers/Assessments.php
```

## QA checklist

| # | Test | Expected |
|---|------|----------|
| 1 | Take pre-test, note Q order, refresh | Same order (DB `active` row) |
| 2 | Log out / log in, reopen take | Same order |
| 3 | Submit assessment | Row `status=submitted` |
| 4 | Retake failed post | Old rows `retaken`, new `active` with new shuffle |
| 5 | Editor question list | `id ASC` (not shuffled) |
| 6 | Grade/review/export | Canonical order, choice IDs |
| 7 | Admin edits question during active attempt | `is_legacy_version=1`, learner keeps order |
| 8 | Video checkpoint | No `assessment_attempt_orders` row |
| 9 | Integrity dashboard KPIs | Match attempts/answers |
| 10 | Debug log | `ASSESSMENT_RANDOMIZE` with `"source":"db"` |

## Backward compatibility

- If `assessment_attempt_orders` table missing → session fallback (legacy).
- All existing routes/controllers unchanged; new route is additive.
- Grading still uses `choice.id` from POST; `get_questions()` canonical for scoring.

## Files touched

- `application/sql/migration_assessment_integrity.sql`
- `application/models/Assessment_attempt_order_model.php`
- `application/models/assessment_model.php`
- `application/models/Assessment_integrity_model.php`
- `application/services/Assessment_integrity_service.php`
- `application/libraries/Assessment_integrity_service.php`
- `application/controllers/Assessments.php`
- `application/views/assessments/integrity_analytics.php`
- `application/views/assessments/index.php` (manager link only)
- `application/config/routes.php`
- `assets/css/assessment_integrity.css`
