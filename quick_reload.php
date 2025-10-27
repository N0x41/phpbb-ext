#!/usr/bin/env php
<?php
/**
 * Script rapide pour recharger l'extension
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

require($phpbb_root_path . 'common.' . $phpEx);

$ext_name = 'linkguarder/activitycontrol';
$phpbb_extension_manager = $phpbb_container->get('ext.manager');

echo "Disabling extension...\n";
$phpbb_extension_manager->disable($ext_name);

echo "Clearing cache...\n";
$cache->purge();

echo "Enabling extension...\n";
$phpbb_extension_manager->enable($ext_name);

echo "Final cache clear...\n";
$cache->purge();

echo "\nDone! Test with:\n";
echo "curl -X POST http://localhost:8080/forum/app.php/ac_node_query -H 'Content-Type: application/json' -d '{\"query\":\"status\"}'\n";
