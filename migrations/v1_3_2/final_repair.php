<?php
/**
 * Migration v1.3.2 — Réparation finale des modules ACP après suppression MCP
 */
namespace linkguarder\activitycontrol\migrations\v1_3_2;

class final_repair extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\\linkguarder\\activitycontrol\\migrations\\v1_3_1\\repair_modules'];
    }

    public function update_data()
    {
        $data = [];
        $db = $this->db;
        $p = $this->table_prefix;

        // Forcer la suppression de toute référence MCP restante
        $db->sql_query("DELETE FROM {$p}modules WHERE module_class = 'mcp' AND (module_langname LIKE 'ACP_ACTIVITY_CONTROL%' OR module_basename LIKE '%activitycontrol%')");

        // Vérifier que la catégorie ACP existe sinon la créer
        $sql = "SELECT module_id FROM {$p}modules WHERE module_class='acp' AND module_langname='ACP_ACTIVITY_CONTROL'";
        $res = $db->sql_query($sql);
        $row = $db->sql_fetchrow($res);
        $db->sql_freeresult($res);
        if (!$row) {
            $data[] = ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_ACTIVITY_CONTROL',
            ]];
        }

        // S'assurer que les trois modes existent
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
