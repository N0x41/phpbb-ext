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
    'ERROR_MIN_POSTS_FOR_LINKS' => 'You need at least %d posts to be able to post links.',

    // Nouvelles clés de langue pour l'ACP
    'AC_MODERATION_SETTINGS'        => 'Moderation Settings',
    'AC_QUARANTINE_POSTS'           => 'Quarantine posts',
    'AC_QUARANTINE_POSTS_EXPLAIN'   => 'If enabled, posts with removed links will be sent to the moderation queue instead of being posted directly.',
    'AC_REMOVE_SIG_LINKS'           => 'Minimum posts for links in signature',
    'AC_REMOVE_SIG_LINKS_EXPLAIN'   => 'Users with fewer posts than this value will have links removed from their signature.',
    'AC_REMOVE_PROFILE_LINKS'       => 'Minimum posts for links in profile',
    'AC_REMOVE_PROFILE_LINKS_EXPLAIN' => 'Users with fewer posts than this value will have links removed from their profile fields (e.g., website).',

    'AC_LINK_REMOVED'               => 'link removed',
    'ACP_ACTIVITY_CONTROL_LOGS'     => 'Logs',
    'ACP_ACTIVITY_CONTROL_LOGS_EXPLAIN' => 'View the latest activity control logs.',
    'MCP_ACTIVITY_CONTROL'          => 'Activity Control',
    'MCP_ACTIVITY_CONTROL_LOGS'     => 'Action Logs',
    'AC_LOG_USER'                   => 'User',
    'AC_LOG_TIME'                   => 'Time',
    'AC_LOG_ACTION'                 => 'Action',
    'AC_LOG_DATA'                   => 'Details',
    'NO_LOGS_FOUND'                 => 'No logs found.',
    
    // Messages pour les groupes
    'AC_RESTRICTED_GROUP'           => 'AC - Restricted Users',
    'AC_PARTIALLY_VERIFIED_GROUP'   => 'AC - Partially Verified Users',
    'AC_VERIFIED_GROUP'             => 'AC - Verified Users',
    
    // Messages pour les actions de logs
    'POST_LINKS_REMOVED'            => 'Post links removed',
    'POST_QUARANTINED'              => 'Post quarantined',
    'SIGNATURE_LINKS_REMOVED'       => 'Signature links removed',
    'WEBSITE_LINK_REMOVED'          => 'Website link removed',
    'PROFILE_FIELD_LINKS_REMOVED'   => 'Profile field links removed',
    'MESSAGE_LINKS_REMOVED'         => 'Message links removed',
    'USER_ADDED_TO_RESTRICTED_GROUP' => 'User added to restricted group',
    'USER_GROUP_UPDATED'            => 'User group updated',
    'GROUP_CREATED'                 => 'Group created',
    'GROUP_CREATION_FAILED'         => 'Group creation failed',
]);