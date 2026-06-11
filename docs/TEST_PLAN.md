# kaBAGA Academy LMS — Test Plan

**Date:** 2026-06-09  
**Scope:** Enhancement Phase 6 verification

---

## Automated smoke tests

Location: `tests/`

```bash
composer install --dev
vendor/bin/phpunit -c tests/phpunit.xml
```

| Suite | File | Covers |
|-------|------|--------|
| Authentication | `AuthenticationTest.php` | Auth controller, password reset, login branding |
| Courses | `CourseTest.php` | Full-course retake, category hierarchy helpers |
| Assessments | `AssessmentTest.php` | Essay mode builder, randomization model |
| Certificates | `CertificateTest.php` | E-sign upload, template PDF images |
| Permissions | `PermissionTest.php` | KA_Controller, cert view gate, leaderboard route |
| Points | `PointsTest.php` | Migration + services |

---

## Manual test checklist

### Essay PDF (#7)

- [ ] Create essay question with **PDF upload only** in assessment builder
- [ ] Learner sees PDF upload on take page
- [ ] Grader downloads PDF from grade page

### E-signatures (#8)

- [ ] Upload PNG signature on course edit → signatories block
- [ ] Preview thumbnail appears after save
- [ ] Generated `template_pdf` certificate shows signature image

### Email (#11)

- [ ] Approve enrollment → approval email logged in `notification_email_log`
- [ ] F2F course approval → F2F notification + email with venue/schedule
- [ ] CLI retry: `php index.php cli/notification_emails/retry`

### Points & leaderboard (#9–10)

- [ ] Run `migration_enhancement_phase6.sql`
- [ ] Complete course → points awarded once (idempotent)
- [ ] Pass assessment → assessment points
- [ ] Issue certificate → certificate points
- [ ] `/leaderboard` shows global, department, monthly tabs

### Retake (#5)

- [ ] Non-managerial course: fail post-assessment → retake resets **all modules**
- [ ] Certificates **not** archived on retake

### Categories (#4)

- [ ] Set `parent_id` on child category (after migration)
- [ ] Catalog filter on parent includes child-mapped courses

### Profile photo (#1)

- [ ] Upload avatar → resized file + `_thumb` created
- [ ] Re-upload replaces old files

### Branding (#12)

- [ ] Login page shows `ka_branding_settings()` logo and org name

---

## Regression checks

- [ ] Question randomization still works (do not modify PASS item)
- [ ] Certificate download still requires `certificates.view`
- [ ] Existing enrollments and progress unchanged without retake action

---

## Database migrations (manual)

1. `application/sql/migration_master_phase5.sql` (if not applied)
2. `application/sql/migration_enhancement_phase6.sql`

Rollback: `rollback_enhancement_phase6.sql`
