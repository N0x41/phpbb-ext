<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: ext.php
 */

namespace linkguarder\activitycontrol;

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
    exit;
}

class ext extends \phpbb\extension\base
{
    public function is_enableable()
    {
        if (version_compare(PHP_VERSION, '7.4.0', '<'))
        {
            return [
                'AC_PHP_VERSION_ERROR' => 'Cette extension nécessite PHP 7.4 ou supérieur.'
            ];
        }
        
        if (!function_exists('openssl_sign'))
        {
            return [
                'AC_OPENSSL_ERROR' => 'L\'extension OpenSSL PHP est requise pour le signalement d\'IP.'
            ];
        }
        
        return true;
    }
    
    public function enable_step($old_state)
    {
        switch ($old_state)
        {
            case '':
                global $phpbb_container;
                
                if ($phpbb_container && $phpbb_container->has('config'))
                {
                    $config = $phpbb_container->get('config');
                    $config->set('ac_last_ip_sync', 0);
                    $config->set('ac_first_activation', 1);
                }
                
                return 'sync_marked';
                
            default:
                return parent::enable_step($old_state);
        }
    }
}