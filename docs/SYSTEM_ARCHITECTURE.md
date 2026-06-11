# kaBAGA Academy LMS — System Architecture

**Framework:** CodeIgniter 3 · **UI:** Tabler + kaBAGA tokens · **Pattern:** MVC + Services

---

## Layer Diagram

```mermaid
flowchart TB
  subgraph presentation [Presentation]
    V[Views Tabler + ka-* CSS]
    L[layouts/main.php]
  end
  subgraph controllers [Controllers]
    KC[KA_Controller]
    AC[API_Controller]
    SVC_CTRL[Domain Controllers]
  end
  subgraph services [Service Layer]
    CCS[Course_completion_service]
    AS[Assessment_service]
    NS[Notification_service]
    CS[Certificate_service]
    MAS[Module_access_service]
    AUD[Audit_service]
  end
  subgraph data [Data]
    M[Models]
    DB[(MySQL db_lms)]
    HRMIS[(MySQL dbhrmis)]
  end
  V --> SVC_CTRL
  SVC_CTRL --> KC
  AC --> KC
  SVC_CTRL --> services
  services --> M
  M --> DB
  M --> HRMIS
```

---

## Authentication Flow

```mermaid
sequenceDiagram
  participant U as User
  participant A as Auth.php
  participant UM as User_model
  participant H as HRMIS DB
  participant S as Session
  U->>A: POST employee_id + password
  A->>UM: login()
  UM->>H: optional validation at register
  UM-->>A: user row
  A->>S: user_id, role, name
  A->>AUD: Audit_service log login
  A-->>U: redirect dashboard
```

**Session guard:** `KA_Controller::_boot_auth()` on all authenticated pages.

**Permissions:** `Permission_model` + `require_permission()`; legacy `role` fallback when engine inactive or user has no groups.

---

## Course Completion Flow

```mermaid
flowchart LR
  E[Enroll approved] --> M[Open module]
  M --> G{Module_access_service}
  G -->|blocked| P[Previous module]
  G -->|ok| PRE[Pre-assessment]
  PRE --> CONTENT[PDF / Video / Slides]
  CONTENT --> POST[Post-assessment]
  POST --> MP[module_progress completed]
  MP --> CCS[Course_completion_service]
  CCS --> CERT{Eligible?}
  CERT -->|yes| CS[Certificate_service.issue]
```

**Single source:** `Course_completion_service::evaluate_user_course_state()`  
**CTA:** `get_course_cta()` in `course_cta_helper.php`

---

## Certificate Flow

```mermaid
sequenceDiagram
  participant C as Courses.php
  participant CCS as Course_completion_service
  participant CS as Certificate_service
  participant CM as certificate_model
  participant PDF as DOMPDF
  C->>CCS: evaluate completion
  CCS-->>C: is_certificate_eligible
  C->>CS: issue + generate_pdf
  CS->>CM: issue() serial code
  CS->>PDF: ka_cert_render_template_html
  CS-->>C: file_path
  Note over CS: Event certificate.issued
  CS->>NS: Notification_listener
  NS->>NS: in-app + email dispatch
```

---

## Event Architecture

| Event | Dispatcher | Listener |
|-------|------------|----------|
| `course.completed` | Courses | `Notification_listener` |
| `enrollment.approved` | Enrollments | `Notification_listener` |
| `certificate.issued` | Certificates | `Notification_listener` |

**Config:** `application/config/event_listeners.php`  
**Email side-effect:** `Notification_service::afterNotificationCreated()` → `Email_service` + `notification_email_log`

---

## API (v1 foundation)

| Route | Controller | Auth |
|-------|------------|------|
| `GET api/v1/courses` | `Api_v1/Courses` | Session + `courses.view` |
| `GET api/v1/courses/{id}` | `Api_v1/Courses::show` | Session + `courses.view` |

**Base:** `application/core/API_Controller.php` extends `KA_Controller`

---

## Key Single Sources of Truth

| Domain | Authority |
|--------|-----------|
| Branding | `ka_branding_settings()` |
| Pass threshold | `ka_assessment_pass_threshold($course_id, $assessment_id)` |
| Module access | `Module_access_service::can_access_module()` |
| Notification email | `User_model::resolve_notification_email()` |
| Certificate PDF | `certificate_pdf_helper.php` |

---

*Companion: `docs/MVC_ARCHITECTURE_AUDIT.md`, `docs/PERMISSION_SYSTEM_AUDIT.md`*
