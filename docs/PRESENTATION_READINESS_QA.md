# Final Presentation Readiness QA — 2026-06-01

## A. System status

| Area | Status | Evidence / notes |
|------|--------|------------------|
| **Login / auth** | **PASS** | Session CSRF; assets `LMS-LOGO.png`, `tabler.min.css`, `custom.css` exist |
| **Registration** | **PASS** | HRMIS `check_employee`; branded register view |
| **Forgot password** | **PASS** | Branded view + CSRF |
| **Dashboard** | **PASS** | Role-based admin/employee dashboards |
| **Permissions / sidebar** | **PASS** *if pre-synced* | Auto-sync on first `/users` or `/permissions/groups` visit |
| **Group matrix UI** | **PASS** *if pre-synced* | Empty state only when catalog never synced |
| **PDF module progress** | **PASS** | No auto `complete_module` on open; mark button gated on post-assessment |
| **Video checkpoints** | **PASS** | Fixed `trigger_seconds` in DB; randomize only at **creation** time (admin tool) |
| **Resume system** | **PASS** | `courses/save_resume_state` + `module_progress.resume_state` |
| **Pre/post randomization** | **PASS** | `get_questions_for_attempt` only; `get_result` uses stored answers |
| **Result / review pages** | **PASS** | No shuffle in result/review views |
| **Certificate DOMPDF** | **PASS** | `qa_cert_premium.php` → 36KB PDF |
| **Certificate download** | **PASS** | Auto-generates if `file_path` missing |
| **QR / verify URL** | **RISK** | QR fetch needs outbound HTTP; verify page requires login |
| **Branding kaBAGA** | **PASS** | Zero `KABAGA Academy` in codebase; login subtext fixed |
| **DB branding override** | **RISK** | `lms_settings` may still store old name until Settings saved |

**Overall:** **PASS** for demo with pre-flight steps completed.

---

## B. Presentation risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Permission catalog empty on fresh DB | **High** | Open `/users` before demo |
| Admin has no group + engine active | **Medium** | Sync links role=admin to Super Administrator |
| QR missing on PDF (offline server) | **Medium** | Placeholder shows; verify URL still in PDF text |
| Certificate verify requires login | **Low** | Demo while logged in; mention “authenticated verify portal” |
| HRMIS down during live registration | **High** | **Do not demo registration** — use pre-created employee |
| Demo course missing post-assessment | **Medium** | Pre-check course completion rules |
| Cached old PDF after template change | **Low** | Regenerate cert or delete `uploads/certificates/cert_*.pdf` |
| `randomize_questions` migration not run | **Low** | Randomization gracefully off if column missing |
| Internet fonts on login (Google Fonts) | **Low** | Page works without; slight font fallback |

---

## C. Quick fix commands (before 2 PM)

```powershell
# 1. Verify critical assets
Test-Path c:\xampp\htdocs\lms\assets\img\LMS-LOGO.png
Test-Path c:\xampp\htdocs\lms\assets\css\custom.css

# 2. Test certificate PDF render
php c:\xampp\htdocs\lms\scripts\qa_cert_premium.php

# 3. PHP syntax spot-check (auth + cert)
php -l c:\xampp\htdocs\lms\application\views\auth\login.php
php -l c:\xampp\htdocs\lms\application\views\certificates\templates\premium_lcp_certificate.php

# 4. In browser (as admin) — permission sync
#    http://localhost/lms/index.php/users
#    http://localhost/lms/index.php/permissions/groups

# 5. Force new certificate PDF (replace CODE)
#    Delete: uploads\certificates\cert_YOUR-CODE.pdf
#    Re-download from Certificates UI

# 6. Optional SQL — branding in DB (adjust table/columns to your schema)
#    UPDATE lms_settings SET setting_value='kaBAGA Academy' WHERE setting_key='lms_name';
```

**Apache/MySQL not running:**
```powershell
# Start XAMPP Apache + MySQL from XAMPP Control Panel
```

**Session / CSRF oddities:**
- Clear cookies for `localhost` / hard refresh
- Re-login

---

## D. Demo confidence score

### **87%** presentation readiness

| Factor | Weight | Score |
|--------|--------|-------|
| Core learning flow stable | 30% | 90% |
| Auth + dashboard | 15% | 95% |
| Permissions (with pre-sync) | 15% | 85% |
| Assessments integrity | 15% | 90% |
| Certificates + PDF | 15% | 85% |
| Live environment unknowns | 10% | 70% |

**To reach 95%:** Complete pre-flight checklist + rehearse Steps 3–5 once with demo accounts + confirm one real certificate downloads.

---

## E. Code verification summary (this audit)

### No broken branding
- `KABAGA Academy`: **0 matches** in repo

### PDF progress integrity
- `module.js`: PDF enables mark button after timeout/scroll message only; `markComplete()` is user-click or explicit video/audio end — **not** on PDF open alone

### Assessment randomization scope
- Shuffle: `get_questions_for_attempt` → pre/post only via `etd_assessment_randomize_enabled`
- Results: `get_result()` — no shuffle
- Take page shows `randomized` badge when applicable

### Video checkpoints
- Learner payload: `get_public_checkpoints_payload` — fixed triggers from DB
- Randomization: `Assessment_service::generate_random_checkpoint_trigger_seconds` — **admin create only**

### Certificate pipeline
- Default template: `premium_lcp_certificate`
- QA PDF: `uploads/certificates/_qa_premium_saas.pdf` (**PASS**)

### Fix applied this session
- `login.php`: restored **kaBAGA Academy** subtext + logo `onerror` fallback (matches register page)

---

## F. Demo script

Full step-by-step: [`docs/DEMO_SCRIPT_PRESENTATION.md`](DEMO_SCRIPT_PRESENTATION.md)
