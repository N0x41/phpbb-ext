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
        return [
            // S'assurer que la catégorie ACP existe
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_ACTIVITY_CONTROL',
            ]],
            // S'assurer que les modes existent
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\\linkguarder\\activitycontrol\\acp\\main_module',
                    'modes'             => ['settings'],
                ],
            ]],
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\\linkguarder\\activitycontrol\\acp\\main_module',
                    'modes'             => ['logs'],
                ],
            ]],
            ['module.add', [
                'acp',
                'ACP_ACTIVITY_CONTROL',
                [
                    'module_basename'   => '\\linkguarder\\activitycontrol\\acp\\main_module',
                    'modes'             => ['ip_bans'],
                ],
            ]],
        ];
    }
}
