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
	'DEMO_PAGE'			=> 'Demo',
    'ACP_ACTIVITY_CONTROL'                  => 'Activity Control',
    'ACP_ACTIVITY_CONTROL_SETTINGS'         => 'Settings',
    'ACP_ACTIVITY_CONTROL_SETTING_SAVED'    => 'Settings have been saved successfully!',
    'ACP_CAT_DOT_MODS'                      => 'Extensions',

    'SETTINGS'                              => 'Settings',
    'MIN_POSTS_FOR_LINKS'                   => 'Minimum posts to post links',
    'MIN_POSTS_FOR_LINKS_EXPLAIN'           => 'The number of posts a user must have to be able to post URLs.',
    'CONFIG_UPDATED'                        => 'Configuration updated successfully.',

    // Le nouveau message d'erreur
    'ERROR_MIN_POSTS_FOR_LINKS'             => 'You need at least %d posts to be able to post links.',
]);