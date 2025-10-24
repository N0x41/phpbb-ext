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
    /**
     * Vérifie si l'extension peut être activée
     * 
     * @return bool|array True si OK, array avec message d'erreur sinon
     */
    public function is_enableable()
    {
        // Vérifier la version PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<'))
        {
            return [
                'AC_PHP_VERSION_ERROR' => 'Cette extension nécessite PHP 7.4 ou supérieur.'
            ];
        }
        
        // Vérifier que OpenSSL est disponible pour le signalement d'IP
        if (!function_exists('openssl_sign'))
        {
            return [
                'AC_OPENSSL_ERROR' => 'L\'extension OpenSSL PHP est requise pour le signalement d\'IP.'
            ];
        }
        
        return true;
    }
    
    /**
     * Action à effectuer après l'activation de l'extension
     * Lance une synchronisation immédiate des IP bannies
     */
    public function enable_step($old_state)
    {
        switch ($old_state)
        {
            case '': // Empty means nothing has run yet
                // Forcer une synchronisation immédiate des IP
                try
                {
                    global $phpbb_container;
                    
                    if ($phpbb_container->has('linkguarder.activitycontrol.ip_ban_sync'))
                    {
                        $ip_ban_sync = $phpbb_container->get('linkguarder.activitycontrol.ip_ban_sync');
                        
                        // Forcer la synchronisation en mettant ac_last_ip_sync à 0
                        // (déjà fait dans la migration, mais on s'assure)
                        $config = $phpbb_container->get('config');
                        $config->set('ac_last_ip_sync', 0);
                        
                        // Lancer la synchronisation
                        $result = $ip_ban_sync->sync();
                        
                        // Logger le résultat
                        if ($result['success'])
                        {
                            $log = $phpbb_container->get('log');
                            $user = $phpbb_container->get('user');
                            $log->add('admin', $user->data['user_id'], $user->ip, 'LOG_AC_IP_SYNC_SUCCESS', time(), [
                                $result['added'],
                                $result['removed'],
                                $result['total']
                            ]);
                        }
                    }
                }
                catch (\Exception $e)
                {
                    // En cas d'erreur, on continue l'activation quand même
                    // L'erreur sera visible dans les logs phpBB
                }
                
                return 'sync_complete';
                
            default:
                return parent::enable_step($old_state);
        }
    }
}