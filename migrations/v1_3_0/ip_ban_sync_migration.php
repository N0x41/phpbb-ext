<?php
/**
 * Migration v1.3.0 — Ajout gestion IP bannies et synchronisation serveur central
 */
namespace linkguarder\activitycontrol\migrations\v1_3_0;

class ip_ban_sync_migration extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['ac_ipban_sync_enabled']);
    }

    static public function depends_on()
    {
        return ['\linkguarder\activitycontrol\migrations\v1_1_0\initial_migration'];
    }

    public function update_data()
    {
        return [
            // Ajout des configs pour la sync IP bans
            ['config.add', ['ac_ipban_sync_enabled', 0]],
            ['config.add', ['ac_ipban_server_url', '']],
            ['config.add', ['ac_ipban_server_token', '']],
            ['config.add', ['ac_ipban_sync_interval', 60]],
            ['config.add', ['ac_ipban_last_sync', 0]],
            ['config.add', ['ac_ipban_post_local', 0]],
            // Ajout du module ACP
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\\linkguarder\\activitycontrol\\acp\\main_module',
                    'modes'             => ['ip_bans'],
                ],
            ]],
            // Création de la table d’appoint pour les IP bannies distantes
            ['custom', [[$this, 'create_remote_ip_bans_table']]],
        ];
    }

    public function create_remote_ip_bans_table()
    {
        $table_name = $this->table_prefix . 'ac_remote_ip_bans';
        // Correction : le champ 'cidr' doit être typé (TINYINT) pour éviter l'erreur SQL
        $schema = [
            'COLUMNS' => [
                'id'            => ['UINT', null, 'auto_increment'],
                'ip'            => ['VCHAR:45', ''],
                'cidr'          => ['TINYINT:3', 32],
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
        ];
        $this->db_tools->sql_create_table($table_name, $schema);
    }
}
