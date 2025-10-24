<?php
/**
 * @Date: 2025-10-24
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: add_ip_reporting_config.php
 */

namespace linkguarder\activitycontrol\migrations\v1_4_0;

class add_ip_reporting_config extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['ac_enable_ip_reporting']);
    }

    static public function depends_on()
    {
        return ['\linkguarder\activitycontrol\migrations\v1_3_2\final_repair'];
    }

    public function update_data()
    {
        return [
            ['config.add', ['ac_enable_ip_reporting', 0]],
            ['config.add', ['ac_central_server_url', 'http://localhost:5000']],
        ];
    }
}
