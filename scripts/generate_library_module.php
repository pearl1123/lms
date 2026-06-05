<?php
/**
 * Scaffold a new registry-driven library module.
 *
 * Usage: php scripts/generate_library_module.php my_table_key
 *
 * Requires manual registry entry in application/config/library_registry.php first.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$key = $argv[1] ?? '';
if ($key === '' || ! preg_match('/^[a-z0-9_]+$/', $key)) {
    fwrite(STDERR, "Usage: php scripts/generate_library_module.php registry_key\n");
    exit(1);
}

$root = dirname(__DIR__);
$class = str_replace(' ', '_', ucwords(str_replace('_', ' ', $key)));
if (strpos($key, 'lib_') === 0) {
    $class = implode('_', array_map('ucfirst', explode('_', $key)));
}

$ctrlDir  = $root . '/application/controllers/libraries';
$viewDir  = $root . '/application/views/libraries/' . $key;
$ctrlFile = $ctrlDir . '/' . $class . '.php';

if ( ! is_dir($ctrlDir)) {
    mkdir($ctrlDir, 0755, true);
}
if ( ! is_dir($viewDir)) {
    mkdir($viewDir, 0755, true);
}

$ctrlBody = <<<PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class {$class} extends KA_Library_controller {
    protected \$library_key = '{$key}';
}

PHP;

$viewBody = <<<PHP
<?php defined('BASEPATH') OR exit('No direct script access allowed');
\$this->load->view('libraries/crud/listview', get_defined_vars());

PHP;

if ( ! file_exists($ctrlFile)) {
    file_put_contents($ctrlFile, $ctrlBody);
    echo "Created controller: {$ctrlFile}\n";
} else {
    echo "Controller exists: {$ctrlFile}\n";
}

$viewFile = $viewDir . '/listview.php';
if ( ! file_exists($viewFile)) {
    file_put_contents($viewFile, $viewBody);
    echo "Created view: {$viewFile}\n";
} else {
    echo "View exists: {$viewFile}\n";
}

echo "Add module config to application/config/library_registry.php\n";
