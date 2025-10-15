<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: main_info.php
 */

namespace linkguarder\activitycontrol\acp;

class main_info
{
    public function module()
    {
		return [
			'filename'	=> '\linkguarder\activitycontrol\acp\main_module',
			'title'		=> 'ACP_ACTIVITY_CONTROL',
            'modes'     => [
                'settings'  => [
                    'title' => 'ACP_ACTIVITY_CONTROL_SETTINGS', // Le lien "Settings"
                    'auth'  => 'ext_linkguarder/activitycontrol && acl_a_board',
                    'cat'   => ['ACP_CAT_DOT_MODS'] // Placé dans l'onglet "Extensions"
                ],
                'logs'      => [
                    'title' => 'ACP_ACTIVITY_CONTROL_LOGS', // Le lien "Logs"
                    'auth'  => 'ext_linkguarder/activitycontrol && acl_a_board',
                    'cat'   => ['ACP_CAT_DOT_MODS'] // Placé DANS LE MÊME onglet
                ],
            ],
        ];
    }
}