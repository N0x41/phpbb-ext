<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: acp_activitycontrol_module.php
 */

namespace linkguarder\activitycontrol\acp;

class acp_activitycontrol_module
{
    public $u_action;
    private $phpbb_root_path;
    private $php_ext;

    public function main($id, $mode)
    {
        global $user, $template;

        $this->tpl_name = 'acp_activitycontrol_body';
        $this->page_title = $user->lang['ACP_ACTIVITY_CONTROL'];

        $template->assign_vars([
            'U_ACTION' => $this->u_action,
        ]);
    }
}