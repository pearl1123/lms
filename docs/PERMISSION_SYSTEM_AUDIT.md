# LMS Permission System — Audit Report

**Date:** 2026-06-01  
**Reference schema:** `application/sql/db_lms.sql`  
**Manifest:** `application/config/lms_permissions.php`

---

## Executive summary

| Area | Status |
|------|--------|
| Schema (Aauth tables) | **PASS** — tables exist and match CI3 module design |
| Seed data (modules/perms) | **FAIL → FIXED** — empty DB caused “No permission modules configured.” |
| Runtime permission checks | **RISK → IMPROVED** — was role-only; now `Permission_model` + `require_permission()` |
| User access UI | **PASS** — User Management modal + tree + effective access |
| Group matrix UI | **PASS** — `/permissions/groups` |
| Navigation filtering | **PASS** — `ka_nav_can()` in sidebar |
| Legacy tables | **RISK** — `aauth_perms_new`, `aauth_group_to_group` unused by LMS |

---

## 1. Table usage

| Table | Used by LMS | Purpose |
|-------|-------------|---------|
| `aauth_users` | **YES** | Accounts, `role` enum (legacy), profile |
| `aauth_groups` | **YES** | Permission groups (Super Admin, Employee, …) |
| `aauth_user_to_group` | **YES** | User ↔ group membership |
| `aauth_perms` | **YES** | Named permissions (`courses.view`, …) |
| `aauth_perm_module_main` | **YES** | Top-level modules (Dashboard, Learning Management, …) |
| `aauth_perm_module_sub` | **YES** | Submodules; columns `view`/`add`/`edit`/`delete` hold perm **names** |
| `aauth_perm_to_group` | **YES** | Group permission assignments |
| `aauth_perm_to_user` | **YES** | Direct user **grants** (overrides) |
| `aauth_perm_deny_to_user` | **YES** (migration) | Direct user **denials** |
| `lms_user_access_audit` | **YES** (migration) | Admin audit log |
| `aauth_group_to_group` | **NO** | Legacy subgroup nesting (hospital system) |
| `aauth_perms_new` | **NO** | Legacy alternate permission catalog |

---

## 2. Root cause — “No permission modules configured.”

1. `User_access_model::get_permission_matrix_rows()` reads `aauth_perm_module_main` / `aauth_perm_module_sub`.
2. Submodule rows reference permission **names** in `view`/`add`/… columns; cells resolve via `aauth_perms.name`.
3. `db_lms.sql` defines tables but ships **no INSERT** seed data.
4. Empty tables → zero matrix rows → JS fallback message in `user_management.js`.

**Fix:** `Permission_seed_service::sync()` (idempotent) + auto-run on first `/users` or `/permissions/groups` visit.

---

## 3. Permission model (mixed)

| Layer | Mechanism |
|-------|-----------|
| Legacy | `aauth_users.role` (`admin`, `teacher`, `employee`) via `require_role()` |
| Primary | Group permissions (`aauth_perm_to_group`) |
| Override | User grants (`aauth_perm_to_user`) |
| Deny | User denials (`aauth_perm_deny_to_user`) |

**Effective permissions:**

```
effective = (⋃ group perms ∪ user grants) − user denials
```

User grants add permissions; user denials always block.

---

## 4. Controller audit

| Controller | Guard before | Guard after |
|------------|--------------|-------------|
| `KA_Controller` | Session + active user | + `Permission_model`, `require_permission()`, `user_can()` |
| `Users` | `require_role('admin')` | `require_permission(users.view\|users.manage)` + auto-sync |
| `Permissions` | — | `groups.view` / `groups.manage` |
| `Libraries_portal` | `require_role('admin')` | `libraries.view` |
| `Reports` | role `admin` | + `reports.view` when engine active |
| `Settings` | role `admin` | + `settings.view` when engine active |
| `Manage_courses` | role admin/teacher | **RISK** — still role-only (CI_Controller) |
| `Assessments` | `_require_manager()` | **RISK** — role/manager methods |
| `Courses` / `My_courses` | enrollment/ownership | learner paths unchanged |

When the permission engine has **no seed data**, admins retain legacy `require_role('admin')` fallback so existing installs do not break.

---

## 5. Hierarchy implemented

| Module | Submodules (examples) |
|--------|------------------------|
| Dashboard | Dashboard → `dashboard.view` |
| Learning Management | Course Catalog, Manage Courses, My Learning, Enrollments |
| Assessments | Assessments (+ take, grade extras) |
| Certificates | Certificates |
| Reports | Reports & Analytics (+ export) |
| Libraries | System Libraries |
| User Management | Users, Groups |
| Settings | Administration, Announcements, Profile, Learning Notes, Progress |

~45 permission names generated from manifest (see `lms_permissions.php`).

---

## 6. Groups seeded

- Super Administrator (`*`)
- LMS Administrator
- ETD Manager / ETD Staff
- Instructor
- Department Head
- Employee

Admins with **no group** are auto-linked to **Super Administrator** on first sync.

---

## 7. UI deliverables

| Page | URL | Features |
|------|-----|----------|
| User Management | `/users` | List, View/Manage Permissions modal, tree + table, effective access |
| Group Permission Matrix | `/permissions/groups` | Rows = permissions, columns = groups, checkbox save |
| Sync catalog | POST `/permissions/sync` | Inserts missing modules/perms/groups/links only |

---

## 8. Security findings

| Finding | Severity | Mitigation |
|---------|----------|------------|
| URL bypass via role-only controllers | Medium | Extend `require_permission` to `Manage_courses`, `Assessments` admin actions |
| Empty engine = admin bypass | Low | Intentional bootstrap; run sync in production |
| Group matrix changes immediate | Low | Admin-only + audit via user module for user overrides |
| `aauth_group_to_group` unused | Info | No LMS impact |

---

## 9. Deployment checklist

- [ ] Run `application/sql/migration_user_access_audit.sql` (deny + audit tables)
- [ ] Deploy code (config, libraries, models, controllers, views, assets)
- [ ] Visit `/users` or `/permissions/groups` once (auto-sync) **or** POST `/permissions/sync`
- [ ] Verify admin users are in **Super Administrator** group (auto on sync)
- [ ] Assign real users to appropriate groups (Employee, Instructor, …)
- [ ] Review group matrix defaults; adjust checkboxes per org policy
- [ ] Clear PHP opcache if enabled

---

## 10. QA checklist

- [ ] `/users` loads; user list paginates
- [ ] Manage Access modal shows permission **tree** (not empty message)
- [ ] View Permissions opens Effective Access tab (read-only)
- [ ] Grant/deny overrides save; effective list updates
- [ ] Group add/remove works; audit rows in `lms_user_access_audit` (if migrated)
- [ ] `/permissions/groups` matrix renders; toggling checkbox saves
- [ ] Sync button adds missing perms without duplicating existing names
- [ ] Sidebar hides Reports when user lacks `reports.view`
- [ ] Sidebar hides Libraries when user lacks `libraries.view`
- [ ] Employee can still access My Learning / course player
- [ ] Direct URL to `/reports` blocked for user without `reports.view`

---

## 11. Files added/updated

**New:** `config/lms_permissions.php`, `libraries/Permission_seed_service.php`, `models/Permission_model.php`, `helpers/permission_helper.php`, `controllers/Permissions.php`, `views/administrator/permissions/group_matrix.php`, `assets/js/group_permissions.js`, `sql/migration_lms_permissions_seed.sql`

**Updated:** `KA_Controller.php`, `User_access_model.php`, `Users.php`, `User_access_service.php`, `sidebar.php`, `user_management.js`, `user_management.css`, `routes.php`, `autoload.php`, `ka_layout_helper.php`, `Reports.php`, `Settings.php`, `Libraries_portal.php`
