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

    public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\config\config $config, \phpbb\user $user, \phpbb\auth\auth $auth, \phpbb\language\language $language)
    {
        $this->helper = $helper;
        $this->template = $template;
        $this->config = $config;
        $this->user = $user;
        $this->auth = $auth;
        $this->language = $language;
    }

    static public function getSubscribedEvents()
    {
		return array(
			'core.user_setup'	=> 'load_language_on_setup',
			'core.page_header'	=> 'add_page_header_link',
            'core.submit_post_start' => 'check_links_in_post',
		);
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

    public function check_links_in_post($event)
    {
        // Ne pas appliquer la restriction aux modérateurs et administrateurs
        if ($this->auth->acl_gets('m_', 'a_'))
        {
            return;
        }

        $min_posts = (int) $this->config['min_posts_for_links'];
        $user_posts = (int) $this->user->data['user_posts'];

        // Si l'utilisateur n'a pas atteint le minimum de messages requis
        if ($user_posts < $min_posts)
        {
            $post_data = $event->get_data();
            $message = $post_data['message'];

            // Cherche la présence d'un lien dans le message
            if (preg_match('/https?:\/\//i', $message))
            {
                // Construit le message d'erreur en utilisant la clé de langue
                $error_msg = $this->language->lang('ERROR_MIN_POSTS_FOR_LINKS', $min_posts);
                
                // Bloque la publication et renvoie l'erreur
                $post_data['errors'][] = $error_msg;
                $event->set_data($post_data);
            }
        }
    }

	public function add_page_header_link($event)
	{
		$this->template->assign_vars(array(
			'U_DEMO_PAGE'	=> $this->helper->route('linkguarder_activitycontrol_controller', array('name' => 'world')),
		));
	}
}