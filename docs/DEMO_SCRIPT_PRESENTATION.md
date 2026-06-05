# kaBAGA Academy LMS — Live Demo Script (10–15 min)

**Audience:** Stakeholders / management  
**Recommended account:** Admin with **Super Administrator** group  
**Pre-demo (5 min before):** Run checklist in § Pre-flight below

---

## Pre-flight checklist (do this first)

| # | Action | Why |
|---|--------|-----|
| 1 | Log in as admin once, open `/users` | Auto-syncs permission catalog if empty |
| 2 | Open `/permissions/groups` | Confirm matrix has checkboxes (not empty message) |
| 3 | Settings → General → LMS name = **kaBAGA Academy** | DB may override code defaults |
| 4 | Confirm demo course is **published** with PDF + video modules | Avoid empty-state during demo |
| 5 | Confirm demo employee is **enrolled** and partially progressed | Faster flow |
| 6 | Open `uploads/certificates/_qa_premium_saas.pdf` locally | Backup cert if live gen fails |
| 7 | Hard-refresh browser (Ctrl+F5) | Clear stale JS/CSS |

---

## Step 1 — Login & dashboard (2 min)

**URL:** `/auth/login`

**Say:**  
> “kaBAGA Academy is our enterprise LMS for Lung Center of the Philippines — HRMIS-linked registration, role-based access, and full learning-to-certificate workflow.”

**Show:**
- Logo + **kaBAGA Academy** on login
- Sign in as **admin**
- Dashboard: courses, enrollments, notifications overview

**Avoid:** Registering a new user live (HRMIS dependency + rate limits).

---

## Step 2 — Permission system (2 min)

**Say:**  
> “Access is group-based with optional per-user overrides — not just a single admin flag.”

**Show:**
- Sidebar: Management section (Manage Courses, User Management, Reports, Administration)
- **`/permissions/groups`** — Group Permission Matrix (rows = permissions, columns = groups)
- Optional: **`/users`** → Manage Permissions on one user → permission tree

**Talking point:** Effective access = group permissions + user grants − user denials.

---

## Step 3 — Course & module flow (3 min)

**Option A (admin):** Manage Courses → open published course → preview structure  
**Option B (employee):** My Learning → open enrolled course

**Show PDF module:**
- Open module player
- **Say:** “Progress does not auto-complete on open — learner must pass post-assessment and explicitly mark complete (or meet content rules).”
- Point out sidebar progress % does not jump from merely opening PDF

**Show video module:**
- Resume restores last position (if demo user has prior progress)
- Video checkpoints fire at **fixed timestamps** set by instructors — not shuffled during playback

---

## Step 4 — Assessment flow (3 min)

**Pre-test (if configured):**
- Open take assessment URL from module sidebar
- If randomization enabled: show **“shuffled for this attempt”** badge on take page only

**Submit → result page:**
- Scores and pass/fail
- **Say:** “Order is locked per attempt in the database; results and grading use stored answers, not a new shuffle.”

**Post-test:**
- Same flow; mention 75% threshold for certificate eligibility (if applicable to demo course)

**Admin alternative:** Assessments → integrity analytics (optional 30 sec)

---

## Step 5 — Certificate generation (3 min)

**Trigger:** Course detail when eligible, or Certificates list

**Show:**
- Completion gate (modules + post-assessments)
- Certificate record created
- **Download PDF** — premium SaaS layout (white, blue accent, learner name prominent)
- QR block + verification URL on PDF

**Backup:** Open `_qa_premium_saas.pdf` if generation fails (network/QR API).

---

## Step 6 — Verification (1 min)

**URL:** `/certificates/verify/{CERTIFICATE_CODE}` (while logged in)

**Say:**  
> “Each certificate has a unique serial and verify URL — stakeholders can confirm authenticity against our registry.”

**Show:** Verify page with learner name, course, issue date, status.

**Note:** Verify route uses authenticated app shell (login required).

---

## Closing (30 sec)

**Recap strengths:**
- HRMIS-integrated identity
- Structured learning path with integrity controls
- Enterprise permissions
- Professional verifiable certificates
- SaaS-ready admin UI

---

## If something breaks live

See **Quick fix commands** in `docs/PRESENTATION_READINESS_QA.md`.
