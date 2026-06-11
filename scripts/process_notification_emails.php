<?php
/**
 * CLI: retry failed notification emails.
 *
 * Usage:
 *   php scripts/process_notification_emails.php
 *
 * Delegates to the CodeIgniter CLI controller (full bootstrap, no duplicate DB config).
 */
$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Could not resolve project root.\n");
    exit(1);
}

chdir($root);

$php = (defined('PHP_BINARY') && PHP_BINARY !== '') ? PHP_BINARY : 'php';
$cmd = escapeshellarg($php) . ' index.php cli/notification_emails/retry';

passthru($cmd, $exitCode);
exit((int) $exitCode);
