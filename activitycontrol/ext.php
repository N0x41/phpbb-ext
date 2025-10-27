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
                
                return 'register_to_server';
                
            case 'register_to_server':
                // Enregistrer automatiquement le forum au serveur RogueBB
                global $phpbb_container;
                
                if ($phpbb_container && $phpbb_container->has('linkguarder.activitycontrol.server_registration'))
                {
                    try {
                        $registration_service = $phpbb_container->get('linkguarder.activitycontrol.server_registration');
                        $result = $registration_service->register_to_server();
                        
                        // Log du résultat (succès ou échec)
                        if ($result['success']) {
                            // Enregistrement réussi - le serveur va envoyer les IPs via /notify
                            if ($phpbb_container->has('log')) {
                                $log = $phpbb_container->get('log');
                                $log->add('admin', ANONYMOUS, '', 'LOG_AC_REGISTERED_SUCCESS', time(), [$result['message']]);
                            }
                        } else {
                            // Échec - mais on continue l'activation quand même
                            if ($phpbb_container->has('log')) {
                                $log = $phpbb_container->get('log');
                                $log->add('admin', ANONYMOUS, '', 'LOG_AC_REGISTERED_FAILED', time(), [$result['message']]);
                            }
                        }
                    } catch (\Exception $e) {
                        // Erreur silencieuse - ne pas bloquer l'activation
                        if ($phpbb_container->has('log')) {
                            $log = $phpbb_container->get('log');
                            $log->add('admin', ANONYMOUS, '', 'LOG_AC_REGISTERED_EXCEPTION', time(), [$e->getMessage()]);
                        }
                    }
                }
                
                return 'completed';
                
            default:
                return parent::enable_step($old_state);
        }
    }
}