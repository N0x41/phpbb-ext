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
    // IP bans ACP
    'ACP_ACTIVITY_CONTROL_IP_BANS' => 'IP bans',
    'ACP_ACTIVITY_CONTROL_IP_BANS_EXPLAIN' => 'Manage banned IP addresses locally and synchronize with the central server.',
    'ACP_ACTIVITY_CONTROL_IP_BANS_MANAGE' => 'Manage IP bans',
    'ACP_ACTIVITY_CONTROL_IP_BANS_ADD' => 'Add a new IP ban',
    'L_IP' => 'IP address',
    'L_CIDR' => 'CIDR',
    'L_REASON' => 'Reason',
    'L_SOURCE' => 'Source',
    'L_STATUS' => 'Status',
    'L_BANNED_AT' => 'Banned at',
    'L_EXPIRES_AT' => 'Expires at',
    'L_ACTIONS' => 'Actions',
    'L_ADD' => 'Add',
    'L_SYNC_NOW' => 'Synchronize now',
    'L_NO_IP_BANS_FOUND' => 'No IP bans found.',
    'L_DELETE' => 'Delete',
    
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
    
    // Signalement d'IP au serveur central
    'AC_ENABLE_IP_REPORTING'        => 'Enable IP reporting',
    'AC_ENABLE_IP_REPORTING_EXPLAIN' => 'When enabled, suspicious IPs will be automatically reported to the central server with RSA signature.',
    'AC_CENTRAL_SERVER_URL'         => 'Central server URL',
    'AC_CENTRAL_SERVER_URL_EXPLAIN' => 'URL of the central IP reporting server (e.g., http://localhost:5000)',
    
    // Logs IP reporting
    'LOG_AC_IP_SUBMITTED'           => 'IP %s submitted to central server (%s)',
    'LOG_AC_SUBMISSION_FAILED'      => 'Failed to submit IP %s (HTTP %s): %s',
    'LOG_AC_SERVER_UNREACHABLE'     => 'Central server unreachable: %s',
    'LOG_AC_PRIVATE_KEY_MISSING'    => 'Private key not found: %s',
    'LOG_AC_PRIVATE_KEY_INVALID'    => 'Invalid private key: %s',
    'LOG_AC_SIGNATURE_FAILED'       => 'Failed to sign IP for submission',
    
    // Erreurs ext.php
    'AC_PHP_VERSION_ERROR'          => 'This extension requires PHP 7.4 or higher.',
    'AC_OPENSSL_ERROR'              => 'The PHP OpenSSL extension is required for IP reporting.',
    
    // Synchronisation IP
    'AC_ENABLE_IP_SYNC'             => 'Enable IP synchronization',
    'AC_ENABLE_IP_SYNC_EXPLAIN'     => 'When enabled, the forum will automatically synchronize banned IPs from the central server.',
    'AC_IP_SYNC_INTERVAL'           => 'Synchronization interval (seconds)',
    'AC_IP_SYNC_INTERVAL_EXPLAIN'   => 'How often to check for updates from the central server (default: 3600 = 1 hour)',
    'AC_BAN_REASON'                 => 'Default ban reason',
    'AC_BAN_REASON_EXPLAIN'         => 'Reason displayed for automatically banned IPs',
    'AC_LAST_IP_SYNC'               => 'Last synchronization',
    'AC_IP_LIST_VERSION'            => 'IP list version',
    
    // Logs synchronisation
    'LOG_AC_IP_SYNC_SUCCESS'        => 'IP sync completed: %d added, %d removed, %d total',
    'LOG_AC_IP_SYNC_FAILED'         => 'IP sync failed: %s',
    
    // Divers
    'NEVER'                         => 'Never',
]);