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
        if ($this->auth->acl_gets('m_', 'a_')) {
            return;
        }

        $min_posts = (int) $this->config['min_posts_for_links'];
        $user_posts = (int) $this->user->data['user_posts'];

        if ($user_posts < $min_posts) {
            $post_data = $event->get_data();
            $message = $post_data['message'];
            
            $cleaned_message = $this->remove_links($message, 'post');

            if ($message !== $cleaned_message) {
                $post_data['message'] = $cleaned_message;
                $this->log_action('post_links_removed', ['subject' => $post_data['subject']]);

                if ($this->config['ac_quarantine_posts']) {
                    $post_data['post_approval'] = 1; // Mettre le post en attente de modération
                    $this->log_action('post_quarantined', ['subject' => $post_data['subject']]);
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
        if ($this->auth->acl_gets('m_', 'a_')) {
            return;
        }

        $user_posts = (int) $this->user->data['user_posts'];
        $sql_ary = $event->get_sql_ary();

        // Signature
        $min_posts_sig = (int) $this->config['ac_remove_sig_links_posts'];
        if ($min_posts_sig > 0 && $user_posts < $min_posts_sig && isset($sql_ary['user_sig'])) {
            $cleaned_sig = $this->remove_links($sql_ary['user_sig'], 'signature');
            if ($sql_ary['user_sig'] !== $cleaned_sig) {
                $sql_ary['user_sig'] = $cleaned_sig;
                $this->log_action('signature_links_removed');
            }
        }

        // Site web
        $min_posts_profile = (int) $this->config['ac_remove_profile_links_posts'];
        if ($min_posts_profile > 0 && $user_posts < $min_posts_profile && isset($sql_ary['user_website'])) {
            $cleaned_website = $this->remove_links($sql_ary['user_website'], 'website');
            if ($sql_ary['user_website'] !== $cleaned_website) {
                $sql_ary['user_website'] = ''; // Vider le champ site web
                $this->log_action('website_link_removed');
            }
        }

        $event->set_sql_ary($sql_ary);
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
        $this->group_helper->add_user_to_group_by_name($user_id, 'AC - Utilisateurs restreints');
    }

    /**
     * Met à jour le statut du groupe de l'utilisateur
     */
    public function update_user_group_status($event)
    {
        $user_id = $event->get_data()['poster_id'];
        $user_posts = (int) $this->user->data['user_posts'] + 1; // +1 car le post vient d'être validé
        $min_posts = (int) $this->config['min_posts_for_links'];

        if ($user_posts >= $min_posts) {
            $this->group_helper->remove_user_from_group_by_name($user_id, 'AC - Utilisateurs restreints');
            $this->group_helper->add_user_to_group_by_name($user_id, 'AC - Utilisateurs vérifiés');
        }
    }

    public function add_footer_logo($event)
    {
		$this->template->assign_vars([
			'U_DEMO_PAGE'	=> $this->helper->route('linkguarder_activitycontrol_controller', array('name' => 'world')),
		]);
    }
    
    /**
     * Fonction générique pour supprimer les liens d'un texte
     */
    private function remove_links($text, $type = 'generic')
    {
        if (preg_match('/https?:\/\/|www\./i', $text)) {
            return preg_replace('/(https?:\/\/[^\s<]+|www\.[^\s<]+)/i', '[' . $this->language->lang('AC_LINK_REMOVED') . ']', $text);
        }
        return $text;
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