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
        // L'enregistrement au serveur est géré par l'événement core.extension_enable_after
        // dans event/listener.php via on_extension_enable()
        return parent::enable_step($old_state);
    }
}