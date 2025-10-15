<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: main_listener.php
 */

namespace linkguarder\activitycontrol\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\auth\auth */
    protected $auth;
    
    /** @var \phpbb\language\language */
    protected $language;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;
    protected $group_helper;

    public function __construct(
        \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\config\config $config,
        \phpbb\user $user, \phpbb\auth\auth $auth, \phpbb\language\language $language,
        \phpbb\db\driver\driver_interface $db, \phpbb\group\helper $group_helper
    ) {
        $this->helper = $helper;
        $this->template = $template;
        $this->config = $config;
        $this->user = $user;
        $this->auth = $auth;
        $this->language = $language;
        $this->db = $db;
        $this->group_helper = $group_helper;
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.user_setup'	                => 'load_language_on_setup',
            'core.page_footer_after'            => 'add_footer_logo',
            'core.submit_post_start'            => 'process_post_content',
            'core.ucp_profile_info_modify_sql_ary' => 'process_profile_and_signature',
            'core.member_register_after'        => 'set_initial_group',
            'core.submit_post_end'              => 'update_user_group_status',
            'core.acp_page_header'              => 'load_acp_stylesheet',
            'core.mcp_page_header'              => 'load_mcp_stylesheet',
            'core.page_header_after'            => 'inject_menu_logo_js',
            'core.message_parser_check_message' => 'process_message_links',
        ];
    }

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'linkguarder/activitycontrol',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

    public function process_post_content($event)
    {
        // Ne pas appliquer aux modérateurs et administrateurs
        if ($this->auth->acl_gets('m_', 'a_')) {
            return;
        }

        $min_posts = (int) $this->config['min_posts_for_links'];
        $user_posts = (int) $this->user->data['user_posts'];

        // Si l'utilisateur n'a pas atteint le minimum de messages requis
        if ($user_posts < $min_posts) {
            $post_data = $event->get_data();
            $message = $post_data['message'];
            
            // Vérifier si le message contient des liens
            if ($this->contains_links($message)) {
                $cleaned_message = $this->remove_links($message, 'post');
                $post_data['message'] = $cleaned_message;
                
                // Enregistrer l'action
                $this->log_action('post_links_removed', [
                    'subject' => $post_data['subject'],
                    'original_message' => $message,
                    'cleaned_message' => $cleaned_message
                ]);

                // Si la quarantaine est activée, mettre le post en attente de modération
                if ($this->config['ac_quarantine_posts']) {
                    $post_data['post_approval'] = 1;
                    $this->log_action('post_quarantined', [
                        'subject' => $post_data['subject'],
                        'reason' => 'Links removed and quarantined'
                    ]);
                }
                
                $event->set_data($post_data);
            }
        }
    }

    /**
     * Charge la feuille de style pour l'ACP
     */
    public function load_acp_stylesheet($event)
    {
        $this->template->link_stylesheet('@linkguarder_activitycontrol/acp.css');
    }

    /**
     * Charge la feuille de style pour le MCP
     */
    public function load_mcp_stylesheet($event)
    {
        $this->template->link_stylesheet('@linkguarder_activitycontrol/mcp.css');
    }
    
    /**
     * Traite le contenu de la signature et du profil
     */
    public function process_profile_and_signature($event)
    {
        // Ne pas appliquer aux modérateurs et administrateurs
        if ($this->auth->acl_gets('m_', 'a_')) {
            return;
        }

        $user_posts = (int) $this->user->data['user_posts'];
        $sql_ary = $event->get_sql_ary();
        $changes_made = false;

        // Traitement de la signature
        $min_posts_sig = (int) $this->config['ac_remove_sig_links_posts'];
        if ($min_posts_sig > 0 && $user_posts < $min_posts_sig && isset($sql_ary['user_sig'])) {
            if ($this->contains_links($sql_ary['user_sig'])) {
                $original_sig = $sql_ary['user_sig'];
                $sql_ary['user_sig'] = $this->remove_links($sql_ary['user_sig'], 'signature');
                $this->log_action('signature_links_removed', [
                    'original_signature' => $original_sig,
                    'cleaned_signature' => $sql_ary['user_sig']
                ]);
                $changes_made = true;
            }
        }

        // Traitement du site web
        $min_posts_profile = (int) $this->config['ac_remove_profile_links_posts'];
        if ($min_posts_profile > 0 && $user_posts < $min_posts_profile && isset($sql_ary['user_website'])) {
            if ($this->contains_links($sql_ary['user_website'])) {
                $original_website = $sql_ary['user_website'];
                $sql_ary['user_website'] = ''; // Vider complètement le champ site web
                $this->log_action('website_link_removed', [
                    'original_website' => $original_website,
                    'reason' => 'User below minimum post count for profile links'
                ]);
                $changes_made = true;
            }
        }

        // Traitement d'autres champs de profil qui pourraient contenir des liens
        $profile_fields = ['user_occ', 'user_from', 'user_interests'];
        foreach ($profile_fields as $field) {
            if (isset($sql_ary[$field]) && $this->contains_links($sql_ary[$field])) {
                $original_value = $sql_ary[$field];
                $sql_ary[$field] = $this->remove_links($sql_ary[$field], 'profile_field');
                $this->log_action('profile_field_links_removed', [
                    'field' => $field,
                    'original_value' => $original_value,
                    'cleaned_value' => $sql_ary[$field]
                ]);
                $changes_made = true;
            }
        }

        if ($changes_made) {
            $event->set_sql_ary($sql_ary);
        }
    }

    public function inject_menu_logo_js($event)
    {
        // Ne s'exécute que dans l'ACP ou le MCP
        if (!$this->user->page['is_acp'] && !$this->user->page['is_mcp']) {
            return;
        }
        
        // Ce script trouvera le menu de notre extension et y ajoutera le logo
        $script = '
            document.addEventListener("DOMContentLoaded", function() {
                // Chercher le lien du menu Activity Control
                const menuLinks = document.querySelectorAll(\'a[href*="activitycontrol"]\');
                for (let menuLink of menuLinks) {
                    if (menuLink.textContent.includes("Activity Control")) {
                        const menuBlock = menuLink.closest(".menu-block");
                        if (menuBlock) {
                            const menuHeader = menuBlock.querySelector("a.header");
                            if (menuHeader && !menuHeader.querySelector("img.ac-logo")) {
                                const logoImg = document.createElement("img");
                                logoImg.src = "' . $this->helper->route('linkguarder_activitycontrol_controller', array('name' => 'logo')) . '/../styles/prosilver/theme/images/logo.svg";
                                logoImg.className = "ac-logo";
                                logoImg.style.height = "18px";
                                logoImg.style.verticalAlign = "middle";
                                logoImg.style.marginRight = "6px";
                                logoImg.style.marginLeft = "5px";
                                menuHeader.insertBefore(logoImg, menuHeader.firstChild);
                            }
                        }
                        break;
                    }
                }
            });
        ';
        
        // Injecte le script dans le pied de page
        $this->template->assign_var('S_FOOTER_JS', $script);
    }

    /**
     * Place un nouvel utilisateur dans le groupe restreint
     */
    public function set_initial_group($event)
    {
        $user_id = $event->get_user_id();
        $this->ensure_groups_exist();
        $this->group_helper->add_user_to_group_by_name($user_id, 'AC - Utilisateurs restreints');
        $this->log_action('user_added_to_restricted_group', ['user_id' => $user_id]);
    }

    /**
     * Met à jour le statut du groupe de l'utilisateur
     */
    public function update_user_group_status($event)
    {
        $user_id = $event->get_data()['poster_id'];
        $user_posts = (int) $this->user->data['user_posts'] + 1; // +1 car le post vient d'être validé
        
        $this->ensure_groups_exist();
        $this->update_user_group_based_on_posts($user_id, $user_posts);
    }

    /**
     * Met à jour le groupe d'un utilisateur en fonction de son nombre de posts
     */
    private function update_user_group_based_on_posts($user_id, $user_posts)
    {
        $min_posts = (int) $this->config['min_posts_for_links'];
        $min_sig_posts = (int) $this->config['ac_remove_sig_links_posts'];
        $min_profile_posts = (int) $this->config['ac_remove_profile_links_posts'];
        
        // Déterminer le groupe approprié
        if ($user_posts >= $min_posts && $user_posts >= $min_sig_posts && $user_posts >= $min_profile_posts) {
            // Utilisateur complètement vérifié
            $new_group = 'AC - Utilisateurs vérifiés';
        } elseif ($user_posts >= $min_posts) {
            // Utilisateur peut poster des liens mais pas dans signature/profil
            $new_group = 'AC - Utilisateurs partiellement vérifiés';
        } else {
            // Utilisateur restreint
            $new_group = 'AC - Utilisateurs restreints';
        }
        
        // Supprimer de tous les groupes AC
        $ac_groups = ['AC - Utilisateurs restreints', 'AC - Utilisateurs partiellement vérifiés', 'AC - Utilisateurs vérifiés'];
        foreach ($ac_groups as $group_name) {
            $this->group_helper->remove_user_from_group_by_name($user_id, $group_name);
        }
        
        // Ajouter au nouveau groupe
        $this->group_helper->add_user_to_group_by_name($user_id, $new_group);
        
        $this->log_action('user_group_updated', [
            'user_id' => $user_id,
            'new_group' => $new_group,
            'user_posts' => $user_posts,
            'min_posts' => $min_posts
        ]);
    }

    /**
     * S'assure que tous les groupes nécessaires existent
     */
    private function ensure_groups_exist()
    {
        $groups_to_create = [
            'AC - Utilisateurs restreints' => [
                'group_name' => 'AC - Utilisateurs restreints',
                'group_desc' => 'Utilisateurs avec restrictions sur les liens',
                'group_type' => GROUP_SPECIAL,
                'group_colour' => 'red'
            ],
            'AC - Utilisateurs partiellement vérifiés' => [
                'group_name' => 'AC - Utilisateurs partiellement vérifiés',
                'group_desc' => 'Utilisateurs pouvant poster des liens mais avec restrictions sur signature/profil',
                'group_type' => GROUP_SPECIAL,
                'group_colour' => 'orange'
            ],
            'AC - Utilisateurs vérifiés' => [
                'group_name' => 'AC - Utilisateurs vérifiés',
                'group_desc' => 'Utilisateurs avec tous les privilèges de liens',
                'group_type' => GROUP_SPECIAL,
                'group_colour' => 'green'
            ]
        ];

        foreach ($groups_to_create as $group_name => $group_data) {
            try {
                // Vérifier si le groupe existe
                $sql = 'SELECT group_id FROM ' . GROUPS_TABLE . ' WHERE group_name = \'' . $this->db->sql_escape($group_name) . '\'';
                $result = $this->db->sql_query($sql);
                $group_id = $this->db->sql_fetchfield('group_id');
                $this->db->sql_freeresult($result);

                if (!$group_id) {
                    // Créer le groupe avec les champs obligatoires
                    $group_data['group_legend'] = $group_data['group_name'];
                    $group_data['group_rank'] = 0;
                    $group_data['group_display'] = 1;
                    $group_data['group_receive_pm'] = 1;
                    $group_data['group_message_limit'] = 0;
                    $group_data['group_max_recipients'] = 5;
                    $group_data['group_avatar'] = '';
                    $group_data['group_avatar_type'] = 0;
                    $group_data['group_avatar_width'] = 0;
                    $group_data['group_avatar_height'] = 0;
                    
                    $sql = 'INSERT INTO ' . GROUPS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $group_data);
                    $this->db->sql_query($sql);
                    $this->log_action('group_created', ['group_name' => $group_name]);
                }
            } catch (\Exception $e) {
                // En cas d'erreur, continuer avec les autres groupes
                $this->log_action('group_creation_failed', [
                    'group_name' => $group_name,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Traite les liens dans les messages (commentaires, réponses, etc.)
     */
    public function process_message_links($event)
    {
        // Ne pas appliquer aux modérateurs et administrateurs
        if ($this->auth->acl_gets('m_', 'a_')) {
            return;
        }

        $min_posts = (int) $this->config['min_posts_for_links'];
        $user_posts = (int) $this->user->data['user_posts'];

        if ($user_posts < $min_posts) {
            $message = $event['message'];
            
            if ($this->contains_links($message)) {
                $cleaned_message = $this->remove_links($message, 'message');
                $event['message'] = $cleaned_message;
                
                $this->log_action('message_links_removed', [
                    'original_message' => $message,
                    'cleaned_message' => $cleaned_message,
                    'context' => 'message_parser'
                ]);
            }
        }
    }

    public function add_footer_logo($event)
    {
		$this->template->assign_vars([
			'U_DEMO_PAGE'	=> $this->helper->route('linkguarder_activitycontrol_controller', array('name' => 'world')),
		]);
    }
    
    /**
     * Vérifie si un texte contient des liens
     */
    private function contains_links($text)
    {
        $link_patterns = [
            '/(https?:\/\/[^\s<>"\'\[\]]+)/i',
            '/(www\.[^\s<>"\'\[\]]+\.[a-z]{2,})/i',
            '/\[url[=]?([^\]]*)\]([^\[]+)\[\/url\]/i',
            '/\[url=([^\]]+)\]([^\[]+)\[\/url\]/i',
            '/([a-z0-9-]+\.([a-z]{2,}\.)*[a-z]{2,})/i'
        ];
        
        foreach ($link_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Fonction générique pour supprimer les liens d'un texte
     */
    private function remove_links($text, $type = 'generic')
    {
        // Patterns pour détecter différents types de liens
        $link_patterns = [
            // URLs complètes avec http/https
            '/(https?:\/\/[^\s<>"\'\[\]]+)/i',
            // URLs avec www.
            '/(www\.[^\s<>"\'\[\]]+\.[a-z]{2,})/i',
            // Liens BBCode [url]
            '/\[url[=]?([^\]]*)\]([^\[]+)\[\/url\]/i',
            // Liens BBCode [url=...]...[/url]
            '/\[url=([^\]]+)\]([^\[]+)\[\/url\]/i',
            // Domaines simples (ex: google.com)
            '/([a-z0-9-]+\.([a-z]{2,}\.)*[a-z]{2,})/i'
        ];
        
        $link_replaced = false;
        $cleaned_text = $text;
        
        foreach ($link_patterns as $pattern) {
            if (preg_match($pattern, $cleaned_text)) {
                $cleaned_text = preg_replace($pattern, '[' . $this->language->lang('AC_LINK_REMOVED') . ']', $cleaned_text);
                $link_replaced = true;
            }
        }
        
        return $cleaned_text;
    }

    /**
     * Fonction privée pour enregistrer les actions
     */
    private function log_action($action, $data = [])
    {
        global $table_prefix;
        $log_row = [
            'user_id'       => (int) $this->user->data['user_id'],
            'log_time'      => time(),
            'log_action'    => $action,
            'log_data'      => json_encode($data),
        ];
        $sql = 'INSERT INTO ' . $table_prefix . 'ac_logs ' . $this->db->sql_build_array('INSERT', $log_row);
        $this->db->sql_query($sql);
    }
}