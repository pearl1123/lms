# kaBAGA Academy LMS — API v1

**Base URL:** `{site}/index.php/api/v1/`  
**Auth:** PHP session (same as web) or future `X-API-Token` header  
**Format:** JSON

---

## Conventions

| Item | Value |
|------|-------|
| Success | `{ "ok": true, ... }` |
| Error | `{ "ok": false, "message": "..." }` |
| Version | `v1` in response body |

**Permissions:** Endpoints call `require_permission()` from `lms_permissions.php`.

---

## Endpoints

### GET `/api/v1/courses`

List published courses visible in catalog.

**Permission:** `courses.view`

**Response:**
```json
{
  "ok": true,
  "version": "v1",
  "data": [
    {
      "id": 9,
      "title": "Course title",
      "description": "...",
      "modality": "Online"
    }
  ]
}
```

### GET `/api/v1/courses/{id}`

Single course detail.

**Permission:** `courses.view`

**Response:**
```json
{
  "ok": true,
  "version": "v1",
  "data": {
    "id": 9,
    "title": "...",
    "description": "...",
    "modality": "..."
  }
}
```

---

## Planned (not yet implemented)

| Method | Path | Permission |
|--------|------|------------|
| GET | `/api/v1/enrollments` | `my_courses.view` |
| GET | `/api/v1/assessments/{id}` | `assessments.view` |
| GET | `/api/v1/certificates` | `certificates.view` |

---

## Existing JSON endpoints (non-versioned)

| Path | Controller |
|------|------------|
| `notifications/unread_count` | `Notifications` |
| `notifications/latest` | `Notifications` |
| `courses/progress_state/{id}` | `Courses` |
| `learning_notes/api_*` | `Learning_notes` |

---

*OpenAPI spec: to be added when v1 surface is complete.*
