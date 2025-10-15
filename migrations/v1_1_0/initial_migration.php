<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: initial_migration.php
 */

namespace linkguarder\activitycontrol\migrations\v1_1_0;

class initial_migration extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // Cette condition vérifie si la migration a déjà été appliquée.
        // Par exemple, en vérifiant l'existence d'une table ou d'une config.
        return isset($this->config['activity_control_version']);
    }

    static public function depends_on()
    {
        // La dépendance doit pointer vers une migration de la version 3.3.x
        return ['\phpbb\db\migration\data\v33x\v331'];
    }

    public function update_data()
    {
        return [
            // Ajoute une nouvelle configuration à la base de données
            ['config.add', ['activity_control_version', '1.1.0']],
            
            ['config.add', ['min_posts_for_links', 10]],
            ['config.add', ['ac_quarantine_posts', 0]],
            ['config.add', ['ac_remove_sig_links_posts', 5]],
            ['config.add', ['ac_remove_profile_links_posts', 5]],
            
            // Créer la table des logs
            ['custom', [[$this, 'create_logs_table']]],
            
            // Ajoute le module ACP
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_ACTIVITY_CONTROL'
            ]],
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\linkguarder\activitycontrol\acp\main_module',
                    'modes'             => ['settings'],
                ],
            ]],
        ];
    }
    
    /**
     * Crée la table des logs pour Activity Control
     */
    public function create_logs_table()
    {
        $table_name = $this->table_prefix . 'ac_logs';
        
        $schema = [
            'COLUMNS' => [
                'log_id'        => ['UINT', null, 'auto_increment'],
                'user_id'       => ['UINT', 0],
                'log_time'      => ['TIMESTAMP', 0],
                'log_action'    => ['VCHAR', ''],
                'log_data'      => ['TEXT', ''],
            ],
            'PRIMARY_KEY' => 'log_id',
            'KEYS' => [
                'user_id' => ['INDEX', 'user_id'],
                'log_time' => ['INDEX', 'log_time'],
            ],
        ];
        
        $this->db_tools->sql_create_table($table_name, $schema);
    }
}