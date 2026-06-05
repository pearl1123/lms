<?php
/**
 * CLI: sync LMS permission catalog (idempotent).
 * Usage: php scripts/seed_permissions.php
 */
define('BASEPATH', true);
define('ENVIRONMENT', getenv('CI_ENV') ?: 'development');

$_SERVER['CI_ENV'] = ENVIRONMENT;
chdir(dirname(__DIR__));

require_once 'index.php';

// Bootstrap minimal CI for CLI is heavy via index.php — use direct DB if needed.
// Recommended: call via browser POST /permissions/sync as admin instead.

echo "Use POST " . (getenv('APP_URL') ?: 'http://localhost/lms/index.php') . "/permissions/sync as admin,\n";
echo "or open /users once (auto-sync when module table is empty).\n";
