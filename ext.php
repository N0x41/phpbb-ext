<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: ext.php
 */

namespace linkguarder\activitycontrol;

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
    exit;
}

class ext extends \phpbb\extension\base
{
    /**
     * Cette méthode est appelée lors de l'activation de l'extension.
     */
    public function enable_extension()
    {
        // Création de la table ac_remote_ip_bans si elle n'existe pas
        global $db, $table_prefix;
        $table = $table_prefix . 'ac_remote_ip_bans';
        $sql = "SHOW TABLES LIKE '" . $db->sql_escape($table) . "'";
        $result = $db->sql_query($sql);
        $exists = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if (!$exists) {
            $create_sql = "CREATE TABLE $table (
                id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
                ip VARCHAR(45) DEFAULT '' NOT NULL,
                cidr TINYINT(3) UNSIGNED DEFAULT '32' NOT NULL,
                reason VARCHAR(255) DEFAULT '' NOT NULL,
                source VARCHAR(32) DEFAULT 'local' NOT NULL,
                action VARCHAR(8) DEFAULT 'add' NOT NULL,
                hash VARCHAR(64) DEFAULT '' NOT NULL,
                banned_at INT(11) UNSIGNED DEFAULT '0' NOT NULL,
                expires_at INT(11) UNSIGNED DEFAULT '0' NOT NULL,
                last_sync_at INT(11) UNSIGNED DEFAULT '0' NOT NULL,
                status VARCHAR(16) DEFAULT 'active' NOT NULL,
                PRIMARY KEY (id)
            ) CHARACTER SET utf8 COLLATE utf8_bin;";
            $db->sql_query($create_sql);
        }
    }

    /**
     * Cette méthode est appelée lors de la désactivation de l'extension.
     */
    public function disable_extension()
    {
        // Logique de désactivation ici
    }

    /**
     * Cette méthode est appelée lors de la suppression des données de l'extension.
     */
    public function purge_extension()
    {
        // Logique de suppression ici
    }
}