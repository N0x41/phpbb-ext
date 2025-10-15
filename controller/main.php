<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: main_controller.php
 */
namespace linkguarder\activitycontrol\controller;

class main
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

    public function __construct(\phpbb\template\template $template)
    {
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
    }

    public function handle()
    {
        
		$this->template->assign_var('DEMO_MESSAGE', $this->user->lang('MIN_POSTS_FOR_LINKS', $this.config['min_posts_for_links']));
        
        return $this->helper->render('body.html');
    }
}