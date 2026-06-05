# Assessment Choices Library (Admin)

Part of the system-wide library framework — see `docs/LIBRARY_SYSTEM.md`.

Admin CRUD for `lib_assessment_choices` per `application/sql/db_lms.sql`.

## Schema (confirmed)

```sql
lib_assessment_choices (
  id INT AUTO_INCREMENT PK,
  question_id INT NOT NULL → FK lib_assessment_questions(id) ON DELETE CASCADE,
  choice_text VARCHAR(500) NOT NULL,
  is_correct TINYINT(1) DEFAULT 0,
  choice_order INT DEFAULT 1,
  archived TINYINT(1) DEFAULT 0
)
```

## URLs

| Action | URL |
|--------|-----|
| List | `libraries/assessment_choices` |
| Create | POST `libraries/assessment_choices/create` |
| Update | POST `libraries/assessment_choices/update/{id}` |
| Archive | POST `libraries/assessment_choices/delete/{id}` |
| Restore | POST `libraries/assessment_choices/restore/{id}` |
| By question | GET `libraries/assessment_choices/get_by_question/{question_id}` |

Admin role only.

## QA checklist

- [ ] Non-admin → redirected from `libraries/assessment_choices`
- [ ] Create choice → appears in list
- [ ] Archive → hidden unless “Show archived”
- [ ] Restore → active again
- [ ] Correct badge visible on `is_correct=1`
- [ ] `get_by_question/{id}` returns JSON for integrators
- [ ] Assessment take/grade still uses choice IDs (unchanged)
