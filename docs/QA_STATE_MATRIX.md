# LMS State Integrity — QA Test Matrix

Run after deploying `application/sql/migration_state_resume.sql` on environments that use DB resume persistence.

| Scenario | Expected | Actual | Pass/Fail |
|----------|----------|--------|-----------|
| New enrollment (pending) | Learner sees pending state; no module progress; 0% on My Learning | _manual_ | |
| Enrollment approved | `module_progress` empty or fresh; resume URL opens first module | _manual_ | |
| Rejected enrollment | Visible on Rejected tab; Request Again available | _manual_ | |
| Reject → Request Again → Approve | Progress/answers reset; certificates archived; 0% progress | _manual_ | |
| Partially completed course | Course % matches flow (not inflated by stale `completed` rows) | _manual_ | |
| Completed course (all modules + post assessments) | 100% progress; certificate eligible | _manual_ | |
| Stale `mp.status=completed` without requirements | Dashboard % does **not** show 100; debug log `Progress mismatch` | _manual_ | |
| Checkpoint course | Checkpoints persist after refresh; % updates after submit | _manual_ | |
| Assessment retake | SweetAlert confirm; progress reset per ETD rules | _manual_ | |
| Certificate generation | Unique serial; PDF downloads; verify URL works | _manual_ | |
| Certificate re-enroll | Prior cert archived; new cert can issue after full completion | _manual_ | |
| Resume — video | Continue Learning opens module with `?t=`; playback seeks | _manual_ | |
| Resume — PDF | `?page=` restores PDF iframe page hash | _manual_ | |
| Resume — last module | Dashboard resume URL targets in-progress module first | _manual_ | |
| Routes: `progress`, `libraries`, `my_courses` | No raw CI 404 | _manual_ | |
| Routes: legacy `my-learning` | Redirects to My Learning | _manual_ | |
| Register (HRMIS miss) | Manual name/dept; clear error message | _manual_ | |
| Admin enrollee table | Active enrollees listed on admin dashboard | _manual_ | |

## Automated checks (developer)

```powershell
php -l application/helpers/ka_resume_helper.php
php -l application/models/course_model.php
php -l application/controllers/Courses.php
php -l application/services/Assessment_service.php
php -l application/services/Certificate_service.php
```

## High-risk areas (monitor in production)

- `is_course_fully_completed()` remains DB-status based — ensure `complete_module` gate is never bypassed.
- PDF resume depends on browser PDF viewer supporting `#page=N`.
- `resume_state` column must exist for server-side resume sync (migration optional until run).
