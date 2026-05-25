# Development notes

## PHP syntax checks (Windows PowerShell)

PowerShell does not treat `&&` like POSIX shells. Prefer **separate commands** or **`;`** between statements.

**Avoid:**

```powershell
php -l application/models/course_model.php && php -l application/controllers/Manage_courses.php
```

**Use:**

```powershell
php -l application/models/course_model.php; php -l application/controllers/Manage_courses.php
```

See also the header comment in `application/sql/migration_phase3_cert_batches_modules.sql`.
