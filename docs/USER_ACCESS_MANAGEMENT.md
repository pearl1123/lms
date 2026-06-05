# User Access Management (Aauth)

Admin module at `users` for managing `aauth_users` group membership and permission overrides.

## Tables

| Table | Purpose |
|-------|---------|
| `aauth_users` | LMS accounts |
| `aauth_groups` | Roles/groups |
| `aauth_user_to_group` | User ↔ group |
| `aauth_perm_module_main` | Permission module categories |
| `aauth_perm_module_sub` | Submodule rows (view/add/edit/delete/extra column names) |
| `aauth_perms` | Permission definitions (`name` matches submodule columns) |
| `aauth_perm_to_group` | Group permissions |
| `aauth_perm_to_user` | Direct user grants |
| `aauth_perm_deny_to_user` | Direct user denials (migration) |
| `lms_user_access_audit` | Audit log (migration) |

## Permission resolution

1. Collect permission IDs from all groups via `aauth_perm_to_group`.
2. Union direct grants from `aauth_perm_to_user`.
3. Subtract direct denials from `aauth_perm_deny_to_user`.
4. Result = **effective permissions**.

User overrides win over inheritance for grants; denials always block.

## Deploy

Run `application/sql/migration_user_access_audit.sql` before using deny/audit features.

## Security

- Admin role only (`KA_Controller::require_role('admin')`).
- CSRF on POST endpoints via session auth + same-origin fetch.
- Does not modify login, registration, or HRMIS flows.
