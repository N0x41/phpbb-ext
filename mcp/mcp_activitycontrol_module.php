<?php
namespace linkguarder\activitycontrol\mcp;

class mcp_activitycontrol_module
{
	public $u_action;

	public function main($id, $mode)
	{
        global $template, $user, $db, $table_prefix;

        $user->add_lang_ext('linkguarder/activitycontrol', 'common');
        $logo_html = '<img src="/ext/linkguarder/activitycontrol/styles/prosilver/theme/images/logo.png" alt="" style="height: 24px; vertical-align: middle; margin-right: 5px;" />';
        
        $this->page_title = $logo_html . $user->lang['MCP_ACTIVITY_CONTROL_LOGS'];
        $this->tpl_name = '@linkguarder_activitycontrol/mcp_activitycontrol_logs';

        $sql = 'SELECT l.*, u.username, u.user_colour FROM ' . $db->sql_escape($table_prefix) . 'ac_logs l
                LEFT JOIN ' . USERS_TABLE . ' u ON (l.user_id = u.user_id)
                ORDER BY l.log_time DESC';
        $result = $db->sql_query_limit($sql, 50);

        while ($row = $db->sql_fetchrow($result)) {
            $template->assign_block_vars('logs', [
                'USERNAME_FULL'  => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
                'ACTION'         => $row['log_action'],
                'TIME'           => $user->format_date($row['log_time']),
                'DATA'           => $row['log_data'],
            ]);
        }
        $db->sql_freeresult($result);
	}
}