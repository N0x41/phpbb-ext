<?php
namespace linkguarder\activitycontrol\mcp;

class mcp_activitycontrol_info
{
	public function module()
	{
		return [
			'title'	=> 'MCP_ACTIVITY_CONTROL', // Ce titre génère l'ID i=mcp_activity_control
			'author' => 'LinkGuarder Team',
			'modes'	=> [
				'logs'	=> ['title' => 'MCP_ACTIVITY_CONTROL_LOGS', 'auth' => 'acl_m_'],
			],
		];
	}
}