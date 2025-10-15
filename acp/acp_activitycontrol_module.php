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

    public function main($id, $mode)
    {
        global $config, $request, $template, $user;

        // Informations générales sur la page
        $this->tpl_name = 'acp_activitycontrol_body';
        $this->page_title = $user->lang['ACP_ACTIVITY_CONTROL_SETTINGS'];
        add_form_key('linkguarder/activitycontrol');

        // Récupération des données du formulaire
        $submit = $request->is_set_post('submit');

        // Nouvel objet de configuration
        $cfg_array = [
            'min_posts_for_links' => $request->variable('min_posts_for_links', 0),
        ];

        if ($submit)
        {
            // Vérification du formulaire
            if (!check_form_key('linkguarder/activitycontrol'))
            {
                trigger_error('FORM_INVALID');
            }

            // Sauvegarde des nouvelles valeurs
            foreach ($cfg_array as $config_name => $config_value)
            {
                $this->config->set($config_name, $config_value);
            }

            trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
        }

        // Affichage des valeurs dans le template
        $template->assign_vars([
            'U_ACTION'              => $this->u_action,
            'MIN_POSTS_FOR_LINKS'   => $config['min_posts_for_links'],
        ]);
    }
}