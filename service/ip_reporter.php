<?php
/**
 * @Date: 2025-10-24
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: ip_reporter.php
 * 
 * Service pour soumettre les IPs suspectes au serveur central
 */

namespace linkguarder\activitycontrol\service;

class ip_reporter
{
    /** @var string Chemin vers le fichier JSON des IPs */
    protected $ip_list_file;
    
    /** @var string Chemin vers la clé privée */
    protected $private_key_path;
    
    /** @var string URL du serveur central */
    protected $server_url;
    
    /** @var \phpbb\config\config */
    protected $config;
    
    /** @var \phpbb\log\log */
    protected $log;
    
    /** @var string Chemin racine de l'extension */
    protected $ext_path;
    
    public function __construct(
        \phpbb\config\config $config,
        \phpbb\log\log $log,
        $ext_path
    ) {
        $this->config = $config;
        $this->log = $log;
        $this->ext_path = $ext_path;
        
        // Configuration des chemins
        $this->ip_list_file = $this->ext_path . '/data/reported_ips.json';
        $this->private_key_path = $this->ext_path . '/data/private_key.pem';
        $this->server_url = $this->config['ac_central_server_url'] ?? 'http://localhost:5000';
    }
    
    /**
     * Ajoute une IP à la liste locale et la soumet au serveur central
     * 
     * @param string $ip_address L'adresse IP à signaler
     * @param string $reason La raison du signalement
     * @param array $context Contexte additionnel (user_id, action, etc.)
     * @return bool True si succès, false sinon
     */
    public function report_ip($ip_address, $reason = '', $context = [])
    {
        // Valider l'IP
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        // Ajouter à la liste locale
        $this->add_to_local_list($ip_address, $reason, $context);
        
        // Soumettre au serveur central si activé
        if ($this->config['ac_enable_ip_reporting'] ?? false) {
            return $this->submit_to_central_server($ip_address);
        }
        
        return true;
    }
    
    /**
     * Ajoute une IP à la liste locale JSON
     * 
     * @param string $ip_address
     * @param string $reason
     * @param array $context
     */
    protected function add_to_local_list($ip_address, $reason, $context)
    {
        // Créer le répertoire data s'il n'existe pas
        $data_dir = dirname($this->ip_list_file);
        if (!is_dir($data_dir)) {
            @mkdir($data_dir, 0755, true);
        }
        
        // Charger la liste existante
        $ip_list = [];
        if (file_exists($this->ip_list_file)) {
            $json_content = file_get_contents($this->ip_list_file);
            $ip_list = json_decode($json_content, true) ?: [];
        }
        
        // Ajouter la nouvelle IP si elle n'existe pas déjà
        $ip_hash = md5($ip_address);
        if (!isset($ip_list[$ip_hash])) {
            $ip_list[$ip_hash] = [
                'ip' => $ip_address,
                'reason' => $reason,
                'context' => $context,
                'first_seen' => time(),
                'last_seen' => time(),
                'count' => 1,
                'submitted' => false
            ];
        } else {
            // Mettre à jour l'entrée existante
            $ip_list[$ip_hash]['last_seen'] = time();
            $ip_list[$ip_hash]['count']++;
        }
        
        // Sauvegarder
        file_put_contents($this->ip_list_file, json_encode($ip_list, JSON_PRETTY_PRINT));
    }
    
    /**
     * Soumet une IP au serveur central avec signature RSA
     * 
     * @param string $ip_address
     * @return bool
     */
    protected function submit_to_central_server($ip_address)
    {
        // Vérifier que la clé privée existe
        if (!file_exists($this->private_key_path)) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_PRIVATE_KEY_MISSING', false, [
                'key_path' => $this->private_key_path
            ]);
            return false;
        }
        
        // Charger la clé privée
        $private_key = openssl_pkey_get_private(file_get_contents($this->private_key_path));
        
        if ($private_key === false) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_PRIVATE_KEY_INVALID', false, [
                'error' => openssl_error_string()
            ]);
            return false;
        }
        
        // Signer l'IP
        $signature = '';
        $success = openssl_sign(
            $ip_address,
            $signature,
            $private_key,
            OPENSSL_ALGO_SHA256
        );
        
        // Libérer la clé
        openssl_pkey_free($private_key);
        
        if (!$success) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_SIGNATURE_FAILED');
            return false;
        }
        
        // Préparer le payload
        $payload = [
            'ip' => $ip_address,
            'signature' => base64_encode($signature)
        ];
        
        $json_payload = json_encode($payload);
        
        // Configurer le contexte HTTP
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n" .
                           "Content-Length: " . strlen($json_payload) . "\r\n",
                'content' => $json_payload,
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        
        // Envoyer la requête
        $response = @file_get_contents($this->server_url . '/api/submit_ip', false, $context);
        
        if ($response === false) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_SERVER_UNREACHABLE', false, [
                'server_url' => $this->server_url,
                'ip' => $ip_address
            ]);
            return false;
        }
        
        // Vérifier le code de réponse
        $http_code = 500;
        if (isset($http_response_header)) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $http_code = isset($matches[1]) ? (int)$matches[1] : 500;
        }
        
        if ($http_code === 200) {
            // Marquer comme soumise dans la liste locale
            $this->mark_as_submitted($ip_address);
            
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_IP_SUBMITTED', false, [
                'ip' => $ip_address,
                'server' => $this->server_url
            ]);
            
            return true;
        } else {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_SUBMISSION_FAILED', false, [
                'ip' => $ip_address,
                'http_code' => $http_code,
                'response' => $response
            ]);
            
            return false;
        }
    }
    
    /**
     * Marque une IP comme soumise dans la liste locale
     * 
     * @param string $ip_address
     */
    protected function mark_as_submitted($ip_address)
    {
        if (!file_exists($this->ip_list_file)) {
            return;
        }
        
        $ip_list = json_decode(file_get_contents($this->ip_list_file), true) ?: [];
        $ip_hash = md5($ip_address);
        
        if (isset($ip_list[$ip_hash])) {
            $ip_list[$ip_hash]['submitted'] = true;
            $ip_list[$ip_hash]['submitted_time'] = time();
            file_put_contents($this->ip_list_file, json_encode($ip_list, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Récupère la liste des IPs signalées
     * 
     * @return array
     */
    public function get_reported_ips()
    {
        if (!file_exists($this->ip_list_file)) {
            return [];
        }
        
        $json_content = file_get_contents($this->ip_list_file);
        return json_decode($json_content, true) ?: [];
    }
    
    /**
     * Nettoie les anciennes entrées (plus de 30 jours)
     * 
     * @param int $max_age Âge maximum en secondes (défaut: 30 jours)
     */
    public function cleanup_old_entries($max_age = 2592000)
    {
        if (!file_exists($this->ip_list_file)) {
            return;
        }
        
        $ip_list = json_decode(file_get_contents($this->ip_list_file), true) ?: [];
        $current_time = time();
        $cleaned = false;
        
        foreach ($ip_list as $hash => $entry) {
            if (($current_time - $entry['last_seen']) > $max_age) {
                unset($ip_list[$hash]);
                $cleaned = true;
            }
        }
        
        if ($cleaned) {
            file_put_contents($this->ip_list_file, json_encode($ip_list, JSON_PRETTY_PRINT));
        }
    }
}
