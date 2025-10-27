#!/usr/bin/env php
<?php
/**
 * Script pour recharger l'extension Activity Control
 * Force phpBB à recharger les routes en désactivant/activant l'extension
 */

define('IN_PHPBB', true);
$phpbb_root_path = '/home/nox/Documents/NiMP/var/www/forum/';
$phpEx = 'php';

// Charger phpBB
require($phpbb_root_path . 'common.' . $phpEx);
require($phpbb_root_path . 'includes/functions_admin.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();

echo "=== Activity Control Extension Reload Script ===\n\n";

// Extension manager
$ext_name = 'linkguarder/activitycontrol';
$phpbb_extension_manager = $phpbb_container->get('ext.manager');

try {
    echo "Step 1: Disabling extension...\n";
    
    if ($phpbb_extension_manager->is_enabled($ext_name)) {
        $phpbb_extension_manager->disable($ext_name);
        echo "✓ Extension disabled successfully\n";
    } else {
        echo "⚠ Extension was already disabled\n";
    }
    
    echo "\nStep 2: Clearing cache...\n";
    $cache->purge();
    
    // Vider aussi le cache des fichiers
    $cache_dir = $phpbb_root_path . 'cache';
    $files = glob($cache_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    
    // Vider spécifiquement le cache Symfony
    $symfony_cache = $cache_dir . '/production';
    if (is_dir($symfony_cache)) {
        system("rm -rf " . escapeshellarg($symfony_cache));
    }
    
    echo "✓ Cache cleared\n";
    
    echo "\nStep 3: Enabling extension...\n";
    $phpbb_extension_manager->enable($ext_name);
    echo "✓ Extension enabled successfully\n";
    
    echo "\nStep 4: Final cache clear...\n";
    $cache->purge();
    echo "✓ Final cache cleared\n";
    
    echo "\n=== Extension reloaded successfully! ===\n";
    echo "\nYou can now test the API endpoints:\n";
    echo "  curl -X POST http://localhost:8080/forum/app.php/ac_node_query \\\n";
    echo "       -H 'Content-Type: application/json' \\\n";
    echo "       -d '{\"query\":\"status\"}'\n\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
