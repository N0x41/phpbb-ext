<?php
/**
 * @Date: 2025-11-25
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: update_server_ip.php
 * 
 * Migration pour mettre à jour l'URL du serveur central
 */

namespace linkguarder\activitycontrol\migrations\v1_0_2;

class update_server_ip extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\linkguarder\activitycontrol\migrations\v1_0_1\add_first_activation_flag'];
    }

    public function update_data()
    {
        return [
            ['config.update', ['ac_central_server_url', 'http://80.78.28.44:5000']],
        ];
    }
}
