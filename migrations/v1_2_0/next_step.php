<?php
/**
 * Migration v1.2.0 — Ajout du mode ACP 'logs'
 */
namespace linkguarder\activitycontrol\migrations\v1_2_0;

class next_step extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\\linkguarder\\activitycontrol\\migrations\\v1_1_0\\initial_migration'];
	}

	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_ACTIVITY_CONTROL',
				[
					'module_basename'   => '\\linkguarder\\activitycontrol\\acp\\main_module',
					'modes'             => ['logs'],
				],
			]],
		];
	}
}

