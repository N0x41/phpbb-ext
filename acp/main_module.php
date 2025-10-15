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
        global $config, $request, $template, $user;

        // Informations générales sur la page
        $user->add_lang_ext('linkguarder/activitycontrol', 'common');
        $this->tpl_name = 'acp_activitycontrol_body';
        $this->page_title = $user->lang['ACP_ACTIVITY_CONTROL_SETTINGS'];
        add_form_key('linkguarder/activitycontrol');

        // Récupération des données du formulaire
        if ($request->is_set_post('submit'))
        {
			if (!check_form_key('linkguarder/activitycontrol'))
			{
				trigger_error('FORM_INVALID');
			}
            // Sauvegarde des nouvelles valeurs
            //foreach ($cfg_array as $config_name => $config_value)
            //{
            //    $config->set($config_name, $config_value);
            //}
            $config->set('min_posts_for_links', $request->variable('min_posts_for_links', 0));

            //trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
            trigger_error($user->lang('ACP_ACTIVITY_CONTROL_SETTING_SAVED') . adm_back_link($this->u_action));
        }
        // Nouvel objet de configuration
        //$cfg_array = [
        //    'min_posts_for_links' => $request->variable('min_posts_for_links', 0),
        //];

        // Affichage des valeurs dans le template
        $template->assign_vars([
            'U_ACTION'              => $this->u_action,
            'MIN_POSTS_FOR_LINKS'   => $config['min_posts_for_links'],
        ]);
    }
}