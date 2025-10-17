<?php
/**
 * Service de synchronisation des IP bannies avec le serveur central
 * Squelette pour extension future — voir README pour l'API et la logique attendue
 *
 * @package linkguarder/activitycontrol
 */
namespace linkguarder\activitycontrol\service;

class ip_ban_sync
{
    protected $config;
    protected $db;
    protected $user;
    protected $auth;
    protected $request;

    public function __construct($config, $db, $user, $auth, $request)
    {
        $this->config = $config;
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->request = $request;
    }

    /**
     * Synchronise les IP bannies avec le serveur central
     * - Pull: récupère la liste distante et fusionne
     * - Push: optionnel, reporte les bans locaux
     * - Journalise les actions dans ac_logs
     * - Voir README pour le contrat d'API et la politique de fusion
     */
    public function sync()
    {
        // Squelette: à compléter avec appel HTTP, parsing JSON, fusion, application dans banlist
        // Exemple: $remote_ip_bans = $this->fetch_remote_ip_bans();
        // Fusionner avec la table ac_remote_ip_bans et appliquer dans phpbb_banlist
        // Journaliser dans ac_logs
        // ...
    }

    // protected function fetch_remote_ip_bans() { ... }
    // protected function apply_ip_ban($ip, $cidr, $reason, ...) { ... }
    // protected function remove_ip_ban($ip, $cidr, ...) { ... }
}
