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
                // ...existing code...
                break;
            case 'logs':
                // ...existing code...
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
                        $db->sql_query('INSERT INTO ' . $db->sql_escape($config['table_prefix'] . 'ac_remote_ip_bans') . ' ' . $db->sql_build_array('INSERT', $sql_ary));
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

                // Synchronisation manuelle avec le serveur central (squelette)
                if ($request->is_set_post('sync_ip_bans')) {
                    // Ici, on appellerait le service de synchronisation (voir README)
                    // Exemple: $phpbb_container->get('linkguarder.activitycontrol.ip_ban_sync')->sync();
                    // Pour la documentation, on logue l'action
                    // $db->sql_query('INSERT INTO ...ac_logs ... ip_ban_sync_started ...');
                    // Afficher un message de succès
                    trigger_error('Synchronisation IP bans lancée (squelette, à implémenter).' . adm_back_link($this->u_action));
                }

                // Affichage de la liste des IP bannies (locales et distantes)
                $sql = 'SELECT * FROM ' . $db->sql_escape($config['table_prefix'] . 'ac_remote_ip_bans') . ' ORDER BY banned_at DESC';
                $result = $db->sql_query_limit($sql, 100);
                while ($row = $db->sql_fetchrow($result)) {
                    $template->assign_block_vars('ip_bans', [
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