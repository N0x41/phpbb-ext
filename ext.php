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
                // Marquer pour synchronisation au premier chargement de page
                // On ne peut pas faire la sync ici car les services ne sont pas encore chargés
                global $phpbb_container;
                
                if ($phpbb_container && $phpbb_container->has('config'))
                {
                    $config = $phpbb_container->get('config');
                    // Forcer ac_last_ip_sync à 0 pour déclencher une sync au premier chargement
                    $config->set('ac_last_ip_sync', 0);
                    // Marquer l'activation pour déclencher sync immédiate
                    $config->set('ac_first_activation', 1);
                }
                
                return 'sync_marked';
                
            default:
                return parent::enable_step($old_state);
        }
    }
}