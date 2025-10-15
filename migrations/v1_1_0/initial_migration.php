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
        return ['\phpbb\db\migration\data\v33x\v330'];
    }

    public function update_data()
    {
        return [
            // Ajoute une nouvelle configuration à la base de données
            ['config.add', ['activity_control_version', '1.1.0']],
            
            ['config.add', ['min_posts_for_links', 10]],
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
                    'module_basename'   => '\linkguarder\activitycontrol\acp\acp_activitycontrol_module',
                    'modes'             => ['settings'],
                ],
            ]],
        ];
    }
}