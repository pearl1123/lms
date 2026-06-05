# QA Report — Assessment Randomization & Integrity Engine

**Audit date:** 2026-06-01  
**Stack:** CodeIgniter 3 LMS  
**Source of truth (post-migration):** `assessment_attempt_orders` table

---

## Executive summary

| Phase | Status |
|-------|--------|
| QA audit | Complete |
| DB persistent attempts | Implemented |
| Content versioning | Implemented |
| Integrity analytics | Implemented (admin route; one manager nav link) |
| Critical isolation (checkpoints/editor/grading) | **PASS** |

**Deploy gate:** Run `application/sql/migration_assessment_integrity.sql` before production QA.

---

## QA matrix

### 1. Scope isolation

| Check | Result | Evidence |
|-------|--------|----------|
| Shuffle only via `get_questions_for_attempt()` | **PASS** | Single caller: `Assessments::take()` |
| Editor uses `get_questions()` | **PASS** | `edit`, `save_question`, `review`, `grade` |
| Submit/scoring uses `get_questions()` | **PASS** | `submit_answers()` line ~1170 |
| Reports/certificates | **PASS** | No `get_questions_for_attempt` usage |
| Pre/post gate in model | **PASS** | Non pre/post returns canonical immediately |
| Randomize flag gate | **PASS** | `etd_assessment_randomize_enabled()` |

**Root cause if FAIL:** N/A  
**Fix:** N/A

---

### 2. Answer integrity

| Check | Result | Evidence |
|-------|--------|----------|
| Single-choice MC submits `choice.id` | **PASS** | `take.php` `value="<?= $c->id ?>"` |
| Scoring matches `choice.id` to `is_correct` | **PASS** | `submit_answers()` string compare on ID |
| True/false (2-choice MC) | **PASS** | Same MC path |
| Fill blank / essay / likert | **PASS** | Not choice-shuffled |
| Multi-correct MC (multiple `is_correct`) | **RISK** | UI is single radio; one correct ID still scores 100% |

**Recommended fix (RISK):** Enforce single correct in editor or add multi-select type.

---

### 3. Persistence behavior

| Check | Result | Evidence |
|-------|--------|----------|
| Page refresh | **PASS** | DB `status=active` restored |
| Back button | **PASS** | Same active row |
| Resume incomplete attempt | **PASS** | Active row without submit |
| Logout / new browser | **PASS** | DB keyed by `user_id` + `assessment_id` |
| Session timeout | **PASS** (with migration) | DB authoritative; session is cache only |
| Migration not applied | **RISK** | Falls back to session-only (`source: session` in logs) |

**Recommended fix (RISK):** Run `migration_assessment_integrity.sql` in all environments.

---

### 4. Retake behavior

| Check | Result | Evidence |
|-------|--------|----------|
| Old order closed | **PASS** | `mark_retaken()` on `clear_user_assessment_attempt()` |
| New shuffle on retake | **PASS** | New `active` row after retake → `take()` |
| No answer leakage | **PASS** | Answers `archived=1`; `has_answered()` ignores archived |
| Submit closes attempt | **PASS** | `finalize_attempt_order_on_submit()` → `submitted` |

---

### 5. Checkpoint exclusion

| Check | Result | Evidence |
|-------|--------|----------|
| `take()` blocks checkpoints | **PASS** | `_reject_checkpoint_assessment()` → 404 |
| Checkpoint submit path | **PASS** | `save_checkpoint_pass()` + `get_questions()` + **index** |
| No attempt order rows | **PASS** | `get_questions_for_attempt` returns early for non pre/post |
| Checkpoint randomize flag forced off | **PASS** | `create_assessment` / `update_assessment` |
| Video timestamp shuffle (triggers) | **PASS** | Unrelated to question order (checkpoint placement only) |

---

### 6. Debug logging

| Field | Result |
|-------|--------|
| `assessment_id` | **PASS** |
| `user_id` | **PASS** |
| `question_ids` | **PASS** |
| `choice_orders` | **PASS** |
| `event` created/restored/cleared | **PASS** |
| `source` db/session | **PASS** |

Log prefix: `ASSESSMENT_RANDOMIZE:` at `debug` level.

---

### 7. Content versioning

| Check | Result | Evidence |
|-------|--------|----------|
| `content_version` column | **PASS** | Migration + model helpers |
| Bump on Q/choice CRUD | **PASS** | create/update/delete/save_choices/batch |
| Attempt snapshots version | **PASS** | `assessment_version` on insert |
| Legacy mid-attempt edit | **PASS** | `is_legacy_version=1`, order preserved |
| Question drag-reorder API | **N/A** | Questions ordered by `id ASC`; no reorder endpoint |

---

### 8. Risk areas (explicit)

| Risk | Result | Notes |
|------|--------|-------|
| Session expiry mid-attempt | **PASS** (post-migration) | DB restores order |
| Question set edited mid-attempt | **PASS** | Legacy flag; frozen order JSON |
| Multi-correct MC | **RISK** | Product/UX limitation |
| Retake + analytics | **PASS** | Retakes counted via `status=retaken` |
| Partial submissions | **PASS** | Active order until submit/retake |

---

## Success criteria checklist

| Criterion | Met |
|-----------|-----|
| Stable across session loss (with migration) | Yes |
| No grading inconsistency | Yes |
| No take-flow UI structure changes | Yes |
| No checkpoint interference | Yes |
| DB single source of truth | Yes (when migrated) |
| Retake generates new order | Yes |
| Reports unaffected | Yes |

---

## Migration SQL

Primary: `application/sql/migration_assessment_integrity.sql`  
Prerequisite: `application/sql/migration_phase4_etd.sql` (`randomize_questions`)

---

## Rollback safety

1. **Code rollback:** Revert PHP files; engine falls back to session if table missing.
2. **DB rollback (optional):**
   - `DROP TABLE assessment_attempt_orders;` (loses in-flight order snapshots only)
   - `ALTER TABLE lib_assessments DROP COLUMN content_version;` (optional)
3. **Data safety:** `assessment_answers` and grading history are unchanged by rollback.
4. **Forward-only note:** Learners mid-attempt lose frozen order if table dropped without code rollback.

---

## Modified files (integrity sprint)

- `application/sql/migration_assessment_integrity.sql`
- `application/models/assessment_model.php`
- `application/models/Assessment_attempt_order_model.php`
- `application/models/Assessment_integrity_model.php`
- `application/services/Assessment_integrity_service.php`
- `application/libraries/Assessment_integrity_service.php`
- `application/controllers/Assessments.php`
- `application/config/routes.php`
- `application/views/assessments/integrity_analytics.php`
- `application/views/assessments/index.php` (manager link only)
- `assets/css/assessment_integrity.css`
- `docs/ASSESSMENT_INTEGRITY.md`
- `docs/QA_ASSESSMENT_RANDOMIZATION.md` (this file)

---

## Manual QA script (15 min)

1. Apply migration → confirm `assessment_attempt_orders` exists.
2. Employee: start pre-test → note Q1 text → refresh → Q1 unchanged.
3. Logout/login → same order.
4. Submit → `SELECT status FROM assessment_attempt_orders` = `submitted`.
5. Fail post → retake → new order; old row `retaken`.
6. Admin: edit question → learner still on old order; `is_legacy_version=1`.
7. Video checkpoint submit → no new row in `assessment_attempt_orders`.
8. Tail log: `ASSESSMENT_RANDOMIZE` with `"source":"db"`.
