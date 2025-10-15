<?php
namespace linkguarder\activitycontrol\mcp;

class mcp_activitycontrol_info
{
	public function module()
	{
		return [
			'title'	=> 'MCP_ACTIVITY_CONTROL',
			'author' => 'LinkGuarder Team',
			'modes'	=> [
				'logs'	=> ['title' => 'MCP_ACTIVITY_CONTROL_LOGS', 'auth' => 'acl_m_'],
			],
		];
	}
}