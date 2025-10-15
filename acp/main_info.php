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
		return array(
			'filename'	=> '\linkguarder\activitycontrol\acp\main_module',
			'title'		=> 'ACP_ACTIVITY_CONTROL',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_ACTIVITY_CONTROL_SETTINGS',
					'auth'	=> 'ext_linkguarder/activitycontrol && acl_a_board',
					'cat'	=> array('ACP_ACTIVITY_CONTROL')
				),
                'logs'	    => array(
                    'title'	=> 'ACP_ACTIVITY_CONTROL_LOGS',
                    'auth'	=> 'ext_linkguarder/activitycontrol && acl_a_board',
                    'cat'	=> array('ACP_ACTIVITY_CONTROL')
                ),
            ),
		);
    }
}