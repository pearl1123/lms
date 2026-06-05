# Phase 4 — ETD Enhancement (May 2026)

Incremental learner experience, assessment integrity, certification, and branding on top of Phase 2/3 and the SaaS UI pass (`PHASE4_SAAS_UI.md`).

## Database migration

Run once (after backup):

```text
application/sql/migration_phase4_etd.sql
```

Adds (when missing):

- `lib_assessments.randomize_questions`
- `lib_assessment_questions.essay_response_mode`
- `assessment_answers.essay_file_path`
- `certificate_signatories.signature_image_path`
- `course_categories.description`, `course_categories.color_hex`

## Central helper

`application/helpers/etd_phase4_helper.php` (autoloaded)

| Function | Purpose |
|----------|---------|
| `etd_modality_display_label()` | HyFlex UI label (DB may stay `hybrid`) |
| `etd_is_face_to_face_modality()` | F2F detection from modality text |
| `etd_f2f_notice_html()` | Admin HTML advisory |
| `etd_course_is_managerial()` | Category keyword match |
| `etd_retake_requires_full_course()` | Non-managerial full retake rule |
| `ka_user_avatar_url()` | Avatar URL helper |
| `ka_branding_settings()` | Logo, favicon, accent from `lms_settings` |

## Settings

**Settings → ETD / Learner UX** (`application/views/settings/etd.php`)

- Face-to-Face advisory HTML
- Managerial category keywords
- HyFlex label map (`hybrid:HyFlex`)
- Full-course retake toggle

## Implemented features

| # | Feature | Status | Key files |
|---|---------|--------|-----------|
| 1 | Profile avatar standard | Done | `components/ka_avatar.php`, `ka-saas-ui.css`, `navbar.php`, `ka_layout_helper.php` |
| 2 | F2F notice | Done | `components/etd_f2f_notice.php`, `courses/detail.php`, settings ETD |
| 3 | HyFlex labels | Done | `etd_modality_display_label()`, manage course + detail |
| 4 | Category UX | Partial | Select2 + descriptions in `phase2_block_categories.php`; run migration for `description`/`color_hex` |
| 5 | Retake rules | Done | `Assessments::retake()`, `course_model::reset_module_progress_for_retake()`, `result.php` |
| 6 | Randomized questions | Done | `get_questions_for_attempt()` + `assessment_attempt_orders` (DB source of truth); see `docs/QA_ASSESSMENT_RANDOMIZATION.md` |
| 7 | Essay PDF mode | Schema + helper ready | Migration `essay_response_mode`; wire take/upload in next pass |
| 8 | E-signatures on PDF | Partial | `template_pdf.php` renders `signature_image_path` when set |
| 9 | Certificate download | Existing | `Certificates::download()`; completion modal polish optional |
| 10 | Email system | Foundation | `libraries/Email_service.php`, `Notification_service::send_email()` |
| 11 | Branding | Partial | `header.php` accent/favicon; full logo swap in sidebar optional |
| 12 | Managerial scoring | Planned | Use `etd_course_is_managerial()` + assessment % for reports; dedicated points table not added |

\* Enable randomization per assessment: `UPDATE lib_assessments SET randomize_questions = 1 WHERE id = ?;`

## Retake flow

- **Managerial** (category matches settings keywords): `assessments/retake/{id}` clears answers only → `assessments/take/{id}`.
- **Non-managerial**: same URL resets `module_progress` for the module, clears pre/post answers, redirects to `courses/module/{id}`.

## Email

Configure **Settings → Notifications** (SMTP). Call from code:

```php
$this->load->library('assessment_service'); // or notification
$this->notification_service->send_email_template($email, 'invite', [
  'learner_name' => $name,
  'course_title' => $title,
  'action_url'   => $url,
]);
```

## Next incremental steps

1. Essay PDF upload on `assessments/take` + grader download link.
2. Admin UI: randomize toggle on assessment edit; signatory image upload.
3. Wire invitation/enrollment events to `send_email_template()` when toggles are on.
4. Certificate PDF logo from `lms_settings` branding path.
5. Managerial points/leaderboard view on Reports (weighted score from existing assessment %).
