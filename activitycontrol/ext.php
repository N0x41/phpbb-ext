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
        if ($old_state === false)
        {
            // Première étape : enregistrer au serveur RogueBB
            if ($this->container->has('linkguarder.activitycontrol.server_registration'))
            {
                try {
                    $registration_service = $this->container->get('linkguarder.activitycontrol.server_registration');
                    $result = $registration_service->register_to_server();
                    
                    // Le serveur envoie les IPs via /notify de manière synchrone
                    // Les configs ac_last_ip_sync et ac_ip_list_version seront mises à jour automatiquement
                    
                } catch (\Exception $e) {
                    // Erreur silencieuse - ne pas bloquer l'activation
                }
            }
            
            return 'registered';
        }
        
        return parent::enable_step($old_state);
    }
}