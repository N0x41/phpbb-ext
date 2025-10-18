<?php
/**
 * Migration v1.3.1 — Réparation des modules ACP (ré-ajout si supprimés)
 */
namespace linkguarder\activitycontrol\migrations\v1_3_1;

class repair_modules extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\\linkguarder\\activitycontrol\\migrations\\v1_3_0\\ip_ban_sync_migration'];
    }

    public function update_data()
    {
        $data = [];
        $db = $this->db;
        $table_prefix = $this->table_prefix;

        // 1) Nettoyer les modules orphelins (sécurité)
        $db->sql_query("DELETE m FROM {$table_prefix}modules m LEFT JOIN {$table_prefix}modules p ON m.parent_id = p.module_id WHERE m.parent_id > 0 AND p.module_id IS NULL");

        // 2) Supprimer tous les modules de l'extension (ACP/MCP), y compris la catégorie
        $db->sql_query("DELETE FROM {$table_prefix}modules WHERE module_class IN ('acp','mcp') AND (module_langname LIKE 'ACP_ACTIVITY_CONTROL%' OR module_basename = '\\linkguarder\\activitycontrol\\acp\\main_module')");

        // 3) Recréer la catégorie ACP
        $data[] = ['module.add', [
            'acp',
            'ACP_CAT_DOT_MODS',
            'ACP_ACTIVITY_CONTROL',
        ]];

        // 4) Recréer les modes sous la catégorie
        foreach (['settings', 'logs', 'ip_bans'] as $mode) {
            $data[] = ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\\linkguarder\\activitycontrol\\acp\\main_module',
                    'modes'             => [$mode],
                ],
            ]];
        }

        return $data;
    }
}
