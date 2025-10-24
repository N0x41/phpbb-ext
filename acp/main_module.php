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
        $this->page_title = $user->lang['ACP_ACTIVITY_CONTROL_SETTINGS'];
        add_form_key('linkguarder/activitycontrol');

        switch ($mode)
        {
            case 'settings':
                $this->page_title = $user->lang['ACP_ACTIVITY_CONTROL_SETTINGS'];
                $this->tpl_name = 'acp_activitycontrol_body';

                // Synchronisation manuelle immédiate
                if ($request->is_set_post('sync_now')) {
                    if (!check_form_key('linkguarder/activitycontrol'))
                    {
                        trigger_error('FORM_INVALID');
                    }
                    
                    // Augmenter le temps d'exécution PHP pour la synchronisation
                    @set_time_limit(300); // 5 minutes
                    @ini_set('max_execution_time', '300');
                    
                    // Appeler le service de synchronisation
                    $ip_ban_sync = $phpbb_container->get('linkguarder.activitycontrol.ip_ban_sync');
                    $result = $ip_ban_sync->sync();
                    
                    if ($result['success']) {
                        trigger_error(sprintf($user->lang['AC_SYNC_SUCCESS'], $result['added'], $result['removed'], $result['total']) . adm_back_link($this->u_action));
                    } else {
                        trigger_error(sprintf($user->lang['AC_SYNC_FAILED'], $result['message']) . adm_back_link($this->u_action), E_USER_WARNING);
                    }
                }

                if ($request->is_set_post('submit')) {
                    if (!check_form_key('linkguarder/activitycontrol'))
                    {
                        trigger_error('FORM_INVALID');
                    }
                    $config->set('min_posts_for_links', $request->variable('min_posts_for_links', 0));
                    $config->set('ac_quarantine_posts', $request->variable('ac_quarantine_posts', 0));
                    $config->set('ac_remove_sig_links_posts', $request->variable('ac_remove_sig_links_posts', 0));
                    $config->set('ac_remove_profile_links_posts', $request->variable('ac_remove_profile_links_posts', 0));
                    
                    // IP Reporting settings
                    $config->set('ac_enable_ip_reporting', $request->variable('ac_enable_ip_reporting', 0));
                    $config->set('ac_central_server_url', $request->variable('ac_central_server_url', ''));
                    
                    // IP Sync settings
                    $config->set('ac_enable_ip_sync', $request->variable('ac_enable_ip_sync', 0));
                    $config->set('ac_ip_sync_interval', $request->variable('ac_ip_sync_interval', 3600));
                    $config->set('ac_ban_reason', $request->variable('ac_ban_reason', ''));

                    trigger_error($user->lang('ACP_ACTIVITY_CONTROL_SETTING_SAVED') . adm_back_link($this->u_action));
                }

                $template->assign_vars([
                    'U_ACTION'                     => $this->u_action,
                    'MIN_POSTS_FOR_LINKS'          => $config['min_posts_for_links'],
                    'AC_QUARANTINE_POSTS'          => $config['ac_quarantine_posts'],
                    'AC_REMOVE_SIG_LINKS_POSTS'    => $config['ac_remove_sig_links_posts'],
                    'AC_REMOVE_PROFILE_LINKS_POSTS'=> $config['ac_remove_profile_links_posts'],
                    
                    // IP Reporting
                    'AC_ENABLE_IP_REPORTING'       => $config['ac_enable_ip_reporting'],
                    'AC_CENTRAL_SERVER_URL'        => $config['ac_central_server_url'],
                    
                    // IP Sync
                    'AC_ENABLE_IP_SYNC'            => $config['ac_enable_ip_sync'],
                    'AC_IP_SYNC_INTERVAL'          => $config['ac_ip_sync_interval'],
                    'AC_BAN_REASON'                => $config['ac_ban_reason'],
                    'AC_LAST_IP_SYNC'              => $config['ac_last_ip_sync'] ? $user->format_date($config['ac_last_ip_sync']) : $user->lang('NEVER'),
                    'AC_IP_LIST_VERSION'           => $config['ac_ip_list_version'],
                ]);
                break;
            case 'logs':
                $this->page_title = $user->lang['ACP_ACTIVITY_CONTROL_LOGS'];
                $this->tpl_name = 'acp_activitycontrol_logs';
                global $table_prefix;
                $sql = 'SELECT l.*, u.username, u.user_colour FROM ' . $table_prefix . 'ac_logs l
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
                break;
            case 'ip_bans':
                /**
                 * Mode ACP: Gestion des IP bannies (locales et synchronisées)
                 * - Affiche la liste des IP bannies (table ac_remote_ip_bans)
                 * - Permet d'ajouter une IP bannie locale
                 * - Permet de lancer la synchronisation manuelle avec le serveur central
                 * - Documenté inline pour faciliter la maintenance
                 */
                $this->page_title = $user->lang('ACP_ACTIVITY_CONTROL_IP_BANS');
                $this->tpl_name = 'acp_activitycontrol_ip_bans';
                add_form_key('linkguarder/activitycontrol');
                $template->assign_var('U_ACTION', $this->u_action);
                global $table_prefix;

                // Ajout d'une IP bannie locale
                if ($request->is_set_post('add_ip_ban')) {
                    if (!check_form_key('linkguarder/activitycontrol')) {
                        trigger_error('FORM_INVALID');
                    }
                    $ip = $request->variable('ip', '', true);
                    $cidr = $request->variable('cidr', 32);
                    $reason = $request->variable('reason', '', true);
                    $expires_at = strtotime($request->variable('expires_at', ''));
                    $now = time();
                    if ($ip) {
                        $sql_ary = [
                            'ip' => $ip,
                            'cidr' => $cidr,
                            'reason' => $reason,
                            'source' => 'local',
                            'action' => 'add',
                            'hash' => md5($ip . $cidr . $reason . $now),
                            'banned_at' => $now,
                            'expires_at' => $expires_at ?: 0,
                            'last_sync_at' => 0,
                            'status' => 'active',
                        ];
                        $db->sql_query('INSERT INTO ' . $table_prefix . 'ac_remote_ip_bans ' . $db->sql_build_array('INSERT', $sql_ary));
                        // Appliquer le ban dans phpbb_banlist
                        $ban_ary = [
                            'ban_ip' => $ip,
                            'ban_start' => $now,
                            'ban_end' => $expires_at ?: 0,
                            'ban_exclude' => 0,
                            'ban_reason' => 'Local: ' . $reason,
                            'ban_give_reason' => $reason,
                        ];
                        $db->sql_query('INSERT INTO ' . BANLIST_TABLE . ' ' . $db->sql_build_array('INSERT', $ban_ary));
                        trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
                    }
                }

                // Suppression d'une IP bannie locale
                if ($request->is_set_post('delete_ip_ban_id')) {
                    if (!check_form_key('linkguarder/activitycontrol')) {
                        trigger_error('FORM_INVALID');
                    }
                    $ban_id = $request->variable('delete_ip_ban_id', 0);
                    if ($ban_id) {
                        // Récupérer l'IP pour supprimer aussi du banlist core
                        $sql = 'SELECT ip FROM ' . $table_prefix . 'ac_remote_ip_bans WHERE id = ' . (int) $ban_id;
                        $result = $db->sql_query($sql);
                        $ip_row = $db->sql_fetchrow($result);
                        $db->sql_freeresult($result);
                        // Supprimer de la table d'appoint
                        $db->sql_query('DELETE FROM ' . $table_prefix . 'ac_remote_ip_bans WHERE id = ' . (int) $ban_id);
                        if ($ip_row && !empty($ip_row['ip'])) {
                            $db->sql_query("DELETE FROM " . BANLIST_TABLE . " WHERE ban_ip = '" . $db->sql_escape($ip_row['ip']) . "'");
                        }
                        trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
                    }
                }

                // Synchronisation manuelle avec le serveur central
                if ($request->is_set_post('sync_ip_bans')) {
                    if (!check_form_key('linkguarder/activitycontrol')) {
                        trigger_error('FORM_INVALID');
                    }
                    
                    // Appeler le service de synchronisation
                    $ip_ban_sync = $phpbb_container->get('linkguarder.activitycontrol.ip_ban_sync');
                    $result = $ip_ban_sync->sync();
                    
                    if ($result['success']) {
                        trigger_error($result['message'] . adm_back_link($this->u_action));
                    } else {
                        trigger_error('Error: ' . $result['message'] . adm_back_link($this->u_action), E_USER_WARNING);
                    }
                }

                // Affichage de la liste des IP bannies (locales et distantes)
                $sql = 'SELECT * FROM ' . $table_prefix . 'ac_remote_ip_bans ORDER BY banned_at DESC';
                $result = $db->sql_query_limit($sql, 100);
                while ($row = $db->sql_fetchrow($result)) {
                    $template->assign_block_vars('ip_bans', [
                        'ID' => $row['id'],
                        'IP' => $row['ip'],
                        'CIDR' => $row['cidr'],
                        'REASON' => $row['reason'],
                        'SOURCE' => $row['source'],
                        'STATUS' => $row['status'],
                        'BANNED_AT' => $user->format_date($row['banned_at']),
                        'EXPIRES_AT' => $row['expires_at'] ? $user->format_date($row['expires_at']) : '',
                        'ACTIONS' => '', // À compléter: bouton supprimer, éditer, etc.
                    ]);
                }
                $db->sql_freeresult($result);
                // Documentation inline:
                // - Pour supprimer/éditer une IP, ajouter des boutons/actions POST et gérer ici
                // - Pour la synchronisation, implémenter le service ip_ban_sync (voir README)
                break;
        }
    }
}