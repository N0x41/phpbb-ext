<?php
/**
 * @Date: 2025-10-24
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: add_first_activation_flag.php
 * 
 * Migration pour ajouter le flag ac_first_activation aux installations existantes
 */

namespace linkguarder\activitycontrol\migrations\v1_0_1;

class add_first_activation_flag extends \phpbb\db\migration\migration
{
    /**
     * Dépendances - doit s'exécuter après l'installation initiale
     */
    static public function depends_on()
    {
        return ['\linkguarder\activitycontrol\migrations\install_v1_0_0'];
    }

    /**
     * Mise à jour des données
     */
    public function update_data()
    {
        return [
            // Ajouter le flag ac_first_activation s'il n'existe pas
            ['config.add', ['ac_first_activation', 0]],
        ];
    }

    /**
     * Restauration (vide car c'est juste un ajout)
     */
    public function revert_data()
    {
        return [
            ['config.remove', ['ac_first_activation']],
        ];
    }
}
