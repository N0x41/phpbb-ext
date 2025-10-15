<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: main_controller.php
 */
namespace linkguarder\activitycontrol\controller;

class main_controller
{
    /** @var \phpbb\template\template */
    protected $template;

    public function __construct(\phpbb\template\template $template)
    {
        $this->template = $template;
    }

    public function handle()
    {
        // Logique pour une future page
        $this->template->set_filenames([
            'body' => '@linkguarder_activitycontrol/body.html',
        ]);
        
        return new \Symfony\Component\HttpFoundation\Response($this->template->render('body'));
    }
}