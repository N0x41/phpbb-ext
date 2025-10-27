#!/usr/bin/env php
<?php
/**
 * Script pour vérifier l'état de l'extension
 */

define('IN_PHPBB', true);
$phpbb_root_path = '/home/nox/Documents/NiMP/var/www/forum/';
$phpEx = 'php';

// Charger phpBB
require($phpbb_root_path . 'common.' . $phpEx);

echo "=== Extension Status Check ===\n\n";

$ext_name = 'linkguarder/activitycontrol';
$phpbb_extension_manager = $phpbb_container->get('ext.manager');

// Vérifier si l'extension est activée
$is_enabled = $phpbb_extension_manager->is_enabled($ext_name);
echo "Extension enabled: " . ($is_enabled ? "YES" : "NO") . "\n";

if ($is_enabled) {
    // Vérifier les routes
    echo "\n--- Checking routes ---\n";
    $router = $phpbb_container->get('router');
    
    try {
        $route = $router->getRouteCollection()->get('linkguarder_activitycontrol_node_query');
        if ($route) {
            echo "✓ Route 'linkguarder_activitycontrol_node_query' found\n";
            echo "  Path: " . $route->getPath() . "\n";
            echo "  Controller: " . $route->getDefault('_controller') . "\n";
        } else {
            echo "✗ Route 'linkguarder_activitycontrol_node_query' NOT found\n";
        }
    } catch (Exception $e) {
        echo "✗ Error checking route: " . $e->getMessage() . "\n";
    }
    
    // Vérifier le service contrôleur
    echo "\n--- Checking controller service ---\n";
    try {
        $controller = $phpbb_container->get('linkguarder.activitycontrol.controller');
        echo "✓ Controller service 'linkguarder.activitycontrol.controller' found\n";
        echo "  Class: " . get_class($controller) . "\n";
    } catch (Exception $e) {
        echo "✗ Controller service NOT found: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Check completed ===\n";
