# Learning Notes Workspace

Personal notes for learners during course modules, with cross-device sync via database.

## Migration

```bash
mysql -u root your_db < application/sql/migration_learning_notes.sql
```

## API endpoints

| Method | URL | Purpose |
|--------|-----|---------|
| GET | `learning_notes` | My Notes dashboard |
| GET | `learning_notes/api_list?course_id=&module_id=&q=` | List notes (owner only) |
| POST | `learning_notes/api_save` | Create note (JSON body) |
| POST | `learning_notes/api_update/{id}` | Update note |
| POST | `learning_notes/api_delete/{id}` | Soft-delete |
| POST | `learning_notes/api_toggle_pin/{id}` | Toggle pin |
| POST | `learning_notes/api_toggle_favorite/{id}` | Toggle favorite |

All API routes require authenticated session. Notes are scoped to `user_id` — admins cannot edit learner notes.

## Module player

- Collapsible right drawer: **📝 My Notes**
- Context capture: video/audio timestamp, PDF page, slide index
- Return to content via stored deep links (`?t=`, `?page=`, `?slide=`)
- Draft auto-save: `localStorage` key `ln_draft_u{user}_c{course}_m{module}`

## Deployment checklist

- [ ] Backup database
- [ ] Run `migration_learning_notes.sql`
- [ ] `php -l` on modified PHP files
- [ ] Employee: open module → My Notes → save → refresh → note persists
- [ ] Return to content link seeks correct position
- [ ] Reports → verify Learning notes KPI + analytics section
- [ ] Confirm progress/resume/checkpoints unchanged

## QA checklist

| # | Test | Expected |
|---|------|----------|
| 1 | Save note on video module | `timestamp_seconds` stored |
| 2 | Return to content | Opens module at `?t=` |
| 3 | PDF note | `pdf_page` from resume/hash |
| 4 | Search in sidebar | Filters list |
| 5 | Pin / favorite / color | Persists after reload |
| 6 | Draft | Close tab mid-type → restore on reopen |
| 7 | Other user | Cannot access note via API |
| 8 | My Notes dashboard | Filters by course/favorites |

## Modified files

- `application/sql/migration_learning_notes.sql`
- `application/models/Learning_notes_model.php`
- `application/controllers/Learning_notes.php`
- `application/controllers/Courses.php`
- `application/models/Reports_model.php`
- `application/views/components/learning_notes_sidebar.php`
- `application/views/learning_notes/index.php`
- `application/views/courses/module.php`
- `application/views/layouts/sidebar.php`
- `application/views/profile/index.php`
- `application/views/reports/executive_overview.php`
- `application/views/reports/learning_notes_analytics.php`
- `application/views/reports/index.php`
- `application/config/routes.php`
- `assets/css/learning_notes.css`
- `assets/js/learning_notes.js`
- `docs/LEARNING_NOTES.md`
