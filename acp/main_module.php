<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: acp_activitycontrol_module.php
 */

namespace linkguarder\activitycontrol\acp;

class main_module
{
    public $u_action;
    
    /** @var \phpbb\user */
    protected $user;
    
    /** @var \phpbb\template\template */
    protected $template;
    
    /** @var \phpbb\request\request */
    protected $request;
    
    /** @var \phpbb\config\config */
    protected $config;

    public function main($id, $mode)
    {
        global $config, $request, $template, $user, $db, $phpbb_container;

        // Informations générales sur la page
        $user->add_lang_ext('linkguarder/activitycontrol', 'common');
        $this->tpl_name = 'acp_activitycontrol_body';
        $logo_html = '<img src="/ext/linkguarder/activitycontrol/styles/prosilver/theme/images/logo.png" alt="" style="height: 24px; vertical-align: middle; margin-right: 5px;" />';
        $this->page_title = $user->lang['ACP_ACTIVITY_CONTROL_SETTINGS'];
        add_form_key('linkguarder/activitycontrol');

        switch ($mode)
        {
            case 'settings':
                $this->page_title = $logo_html . $user->lang['ACP_ACTIVITY_CONTROL_SETTINGS'];
                $this->tpl_name = 'acp_activitycontrol_body';

                if ($request->is_set_post('submit')) {
		        	if (!check_form_key('linkguarder/activitycontrol'))
		        	{
		        		trigger_error('FORM_INVALID');
		        	}
                    $config->set('min_posts_for_links', $request->variable('min_posts_for_links', 0));
                    // Ajouter la sauvegarde des nouvelles options
                    $config->set('ac_quarantine_posts', $request->variable('ac_quarantine_posts', 0));
                    $config->set('ac_remove_sig_links', $request->variable('ac_remove_sig_links', 0));
                    $config->set('ac_remove_profile_links', $request->variable('ac_remove_profile_links', 0));

                    trigger_error($user->lang('ACP_ACTIVITY_CONTROL_SETTING_SAVED') . adm_back_link($this->u_action));
                }

                // Affichage des valeurs dans le template
                $template->assign_vars([
                    'U_ACTION'                  => $this->u_action,
                    'MIN_POSTS_FOR_LINKS'       => $config['min_posts_for_links'],
                    // Assigner les nouvelles variables au template
                    'AC_QUARANTINE_POSTS'       => $config['ac_quarantine_posts'],
                    'AC_REMOVE_SIG_LINKS'       => $config['ac_remove_sig_links'],
                    'AC_REMOVE_PROFILE_LINKS'   => $config['ac_remove_profile_links'],
                ]);
                break;

            case 'logs':
                $this->page_title = $logo_html . $user->lang['ACP_ACTIVITY_CONTROL_LOGS'];
                $this->tpl_name = 'acp_activitycontrol_logs';
                
                $sql = 'SELECT l.*, u.username, u.user_colour FROM ' . $db->sql_escape($table_prefix) . 'ac_logs l
                        LEFT JOIN ' . USERS_TABLE . ' u ON (l.user_id = u.user_id)
                        ORDER BY l.log_time DESC';
                $result = $db->sql_query_limit($sql, 50); // Limite aux 50 derniers logs pour la performance

                while ($row = $db->sql_fetchrow($result)) {
                    $template->assign_block_vars('logs', [
                        'USERNAME_FULL'  => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
                        'ACTION'         => $row['log_action'],
                        'TIME'           => $user->format_date($row['log_time']),
                        'DATA'           => $row['log_data'],
                    ]);
                }
                $db->sql_freeresult($result);
                break;
        }
    }
}