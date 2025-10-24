<?php
/**
 * @Date: 2025-10-24
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: install_v1_0_0.php
 * 
 * Migration initiale pour Activity Control 1.0.0
 * Cette migration configure toutes les fonctionnalités de l'extension
 */

namespace linkguarder\activitycontrol\migrations;

class install_v1_0_0 extends \phpbb\db\migration\migration
{
    /**
     * Vérifie si la migration est déjà installée
     */
    public function effectively_installed()
    {
        return isset($this->config['ac_version']);
    }

    /**
     * Dépendances de migration - nécessite phpBB 3.3.1+
     */
    static public function depends_on()
    {
        return ['\phpbb\db\migration\data\v33x\v331'];
    }

    /**
     * Mise à jour du schéma de base de données
     */
    public function update_schema()
    {
        return [
            'add_tables' => [
                // Table des logs Activity Control
                $this->table_prefix . 'ac_logs' => [
                    'COLUMNS' => [
                        'log_id'        => ['UINT', null, 'auto_increment'],
                        'user_id'       => ['UINT', 0],
                        'log_time'      => ['TIMESTAMP', 0],
                        'log_action'    => ['VCHAR:255', ''],
                        'log_data'      => ['TEXT', ''],
                    ],
                    'PRIMARY_KEY' => 'log_id',
                    'KEYS' => [
                        'user_id' => ['INDEX', 'user_id'],
                        'log_time' => ['INDEX', 'log_time'],
                    ],
                ],
                // Table des IP bannies distantes
                $this->table_prefix . 'ac_remote_ip_bans' => [
                    'COLUMNS' => [
                        'id'            => ['UINT', null, 'auto_increment'],
                        'ip'            => ['VCHAR:45', ''],
                        'cidr'          => ['USINT', 32],
                        'reason'        => ['VCHAR:255', ''],
                        'source'        => ['VCHAR:32', 'local'],
                        'action'        => ['VCHAR:8', 'add'],
                        'hash'          => ['VCHAR:64', ''],
                        'banned_at'     => ['TIMESTAMP', 0],
                        'expires_at'    => ['TIMESTAMP', 0],
                        'last_sync_at'  => ['TIMESTAMP', 0],
                        'status'        => ['VCHAR:16', 'active'],
                    ],
                    'PRIMARY_KEY' => 'id',
                    'KEYS' => [
                        'ip' => ['INDEX', 'ip'],
                        'hash' => ['INDEX', 'hash'],
                        'status' => ['INDEX', 'status'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Restauration du schéma (lors de la désinstallation)
     */
    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'ac_logs',
                $this->table_prefix . 'ac_remote_ip_bans',
            ],
        ];
    }

    /**
     * Mise à jour des données (configuration et modules)
     */
    public function update_data()
    {
        return [
            // === Configuration de base ===
            ['config.add', ['ac_version', '1.0.0']],
            
            // === Contrôle des liens dans les posts ===
            ['config.add', ['min_posts_for_links', 10]],
            ['config.add', ['ac_quarantine_posts', 0]],
            
            // === Contrôle des liens dans la signature ===
            ['config.add', ['ac_remove_sig_links_posts', 5]],
            
            // === Contrôle des liens dans le profil ===
            ['config.add', ['ac_remove_profile_links_posts', 5]],
            
            // === Synchronisation des IP bannies ===
            ['config.add', ['ac_enable_ip_sync', 0]],
            ['config.add', ['ac_ip_sync_interval', 3600]], // 1 heure par défaut
            ['config.add', ['ac_last_ip_sync', 0]], // Timestamp de la dernière sync
            ['config.add', ['ac_ip_list_version', 0]], // Version de la liste
            ['config.add', ['ac_ban_reason', 'Activity Control - Central Ban List']], // Raison du ban
            
            // === Signalement d'IP au serveur central ===
            ['config.add', ['ac_enable_ip_reporting', 0]],
            ['config.add', ['ac_central_server_url', 'http://localhost:5000']],
            
            // === Modules ACP ===
            // Catégorie principale
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_ACTIVITY_CONTROL'
            ]],
            // Module paramètres
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\linkguarder\activitycontrol\acp\main_module',
                    'modes'             => ['settings'],
                ],
            ]],
            // Module logs
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\linkguarder\activitycontrol\acp\main_module',
                    'modes'             => ['logs'],
                ],
            ]],
            // Module IP bans
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\linkguarder\activitycontrol\acp\main_module',
                    'modes'             => ['ip_bans'],
                ],
            ]],
        ];
    }

    /**
     * Restauration des données (lors de la désinstallation)
     */
    public function revert_data()
    {
        return [
            // Suppression de la configuration
            ['config.remove', ['ac_version']],
            ['config.remove', ['min_posts_for_links']],
            ['config.remove', ['ac_quarantine_posts']],
            ['config.remove', ['ac_remove_sig_links_posts']],
            ['config.remove', ['ac_remove_profile_links_posts']],
            ['config.remove', ['ac_ipban_sync_enabled']],
            ['config.remove', ['ac_ipban_server_url']],
            ['config.remove', ['ac_ipban_server_token']],
            ['config.remove', ['ac_ipban_sync_interval']],
            ['config.remove', ['ac_ipban_last_sync']],
            ['config.remove', ['ac_ipban_post_local']],
            ['config.remove', ['ac_enable_ip_reporting']],
            ['config.remove', ['ac_central_server_url']],
            
            // Suppression des modules
            ['module.remove', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\linkguarder\activitycontrol\acp\main_module',
                    'modes'             => ['settings'],
                ],
            ]],
            ['module.remove', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\linkguarder\activitycontrol\acp\main_module',
                    'modes'             => ['logs'],
                ],
            ]],
            ['module.remove', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\linkguarder\activitycontrol\acp\main_module',
                    'modes'             => ['ip_bans'],
                ],
            ]],
            ['module.remove', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_ACTIVITY_CONTROL'
            ]],
        ];
    }
}
