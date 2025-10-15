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
            'filename'  => '\linkguarder\activitycontrol\acp\acp_activitycontrol_module',
            'title'     => 'ACP_ACTIVITY_CONTROL',
            'modes'     => [
                'settings' => ['title' => 'ACP_ACTIVITY_CONTROL_SETTINGS', 'cat' => ['ACP_CAT_DOT_MODS']],
            ],
        ];
    }
}