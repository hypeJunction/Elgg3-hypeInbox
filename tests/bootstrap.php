<?php
/**
 * PHPUnit bootstrap for hypeinbox (Elgg 4.x characterization suite).
 *
 * Unlike hypewall, hypeinbox uses the Elgg 4.x plugin lifecycle: elgg-plugin.php
 * declares a Bootstrap class which Elgg instantiates during the system init
 * event. No start.php closure to invoke manually.
 */

$elggRoot = '/var/www/html';

require_once $elggRoot . '/vendor/autoload.php';

// Elgg test base classes (IntegrationTestCase, Seeding trait, etc.).
$testClassesDir = $elggRoot . '/vendor/elgg/elgg/engine/tests/classes';
spl_autoload_register(function ($class) use ($testClassesDir) {
    $file = $testClassesDir . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

\Elgg\Application::getInstance()->bootCore();

if (function_exists('_elgg_services')) {
    _elgg_services()->plugins->generateEntities();
    $plugin = elgg_get_plugin_from_id('hypeinbox');
    if ($plugin) {
        if (!$plugin->isEnabled()) {
            $plugin->enable();
        }
        if (!$plugin->isActive()) {
            try { $plugin->activate(); } catch (\Throwable $e) {}
        }
        // Trigger system init so Bootstrap::init wires hooks/actions/events.
        try { elgg_trigger_event('init', 'system'); } catch (\Throwable $e) {}
    }
}
