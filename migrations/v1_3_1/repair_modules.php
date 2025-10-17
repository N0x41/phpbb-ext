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
        // Vérifier la catégorie ACP
        $sql = "SELECT module_id FROM {$table_prefix}modules WHERE module_langname = 'ACP_ACTIVITY_CONTROL' AND module_class = 'acp'";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if (!$row) {
            $data[] = ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_ACTIVITY_CONTROL',
            ]];
        }
        // Vérifier chaque mode
        // Nettoyage automatique : supprimer tous les modules ACP/MCP de l'extension
        $db->sql_query("DELETE FROM {$table_prefix}modules WHERE module_langname LIKE 'ACP_ACTIVITY_CONTROL%' AND module_class IN ('acp','mcp')");
        foreach (['settings', 'logs', 'ip_bans'] as $mode) {
            $langname = 'ACP_ACTIVITY_CONTROL_' . strtoupper($mode);
            $sql = "SELECT module_id FROM {$table_prefix}modules WHERE module_langname = '" . $db->sql_escape($langname) . "' AND module_class = 'acp'";
            $result = $db->sql_query($sql);
            $row = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);
            if (!$row) {
                $data[] = ['module.add', [
                    'acp',
                    'ACP_ACTIVITY_CONTROL',
                    [
                        'module_basename'   => '\\linkguarder\\activitycontrol\\acp\\main_module',
                        'modes'             => [$mode],
                    ],
                ]];
            }
        }
        // S'assurer que la catégorie ACP existe
        $sql = "SELECT module_id FROM {$table_prefix}modules WHERE module_langname = 'ACP_ACTIVITY_CONTROL' AND module_class = 'acp'";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if (!$row) {
            $data[] = ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_ACTIVITY_CONTROL',
            ]];
        }
        return $data;
    }
}
