-- Registration hardening: unique employee_id on active accounts.
-- Run only after resolving duplicates (see audit queries in docs).
-- Existing rows are NOT modified.

-- Optional: find case-insensitive duplicates before applying UNIQUE
-- SELECT UPPER(employee_id) AS k, COUNT(*) c FROM aauth_users WHERE DELETED = 0 GROUP BY k HAVING c > 1;

ALTER TABLE `aauth_users`
  ADD UNIQUE KEY `uq_aauth_users_employee_id` (`employee_id`);
