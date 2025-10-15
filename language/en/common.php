<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: common.php
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

$lang = array_merge($lang, [
    'ACP_ACTIVITY_CONTROL'          => 'Activity Control',
    'ACP_ACTIVITY_CONTROL_SETTINGS' => 'Settings',
    'ACP_CAT_DOT_MODS'              => 'Extensions',
]);