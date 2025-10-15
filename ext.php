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
     * Cette méthode est appelée lors de l'activation de l'extension.
     */
    public function enable_extension()
    {
        // Logique d'activation ici
    }

    /**
     * Cette méthode est appelée lors de la désactivation de l'extension.
     */
    public function disable_extension()
    {
        // Logique de désactivation ici
    }

    /**
     * Cette méthode est appelée lors de la suppression des données de l'extension.
     */
    public function purge_extension()
    {
        // Logique de suppression ici
    }
}