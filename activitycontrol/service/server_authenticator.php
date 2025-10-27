<?php
/**
 * @Date: 2025-10-27
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: server_authenticator.php
 * @Description: Service d'authentification cryptographique du serveur RogueBB
 */

namespace linkguarder\activitycontrol\service;

class server_authenticator
{
    /** @var string Chemin vers la clé publique du serveur */
    protected $public_key_path;
    
    /** @var \phpbb\log\log */
    protected $log;
    
    /** @var \phpbb\config\config */
    protected $config;
    
    /** @var string Clé publique en cache */
    protected $public_key_cache = null;

    /**
     * Constructor
     *
     * @param \phpbb\log\log $log
     * @param \phpbb\config\config $config
     * @param string $ext_root_path Chemin racine de l'extension
     */
    public function __construct(\phpbb\log\log $log, \phpbb\config\config $config, $ext_root_path)
    {
        $this->log = $log;
        $this->config = $config;
        $this->public_key_path = $ext_root_path . '/data/pkem';
    }

    /**
     * Vérifie l'authenticité d'une signature
     *
     * @param string $data Données signées
     * @param string $signature Signature en base64
     * @return bool True si la signature est valide
     */
    public function verify_signature($data, $signature)
    {
        try {
            // Charger la clé publique
            $public_key = $this->get_public_key();
            if (!$public_key) {
                $this->log->add('critical', null, null, 'AC_AUTH_NO_PUBLIC_KEY');
                return false;
            }

            // Décoder la signature
            $signature_decoded = base64_decode($signature);
            if ($signature_decoded === false) {
                $this->log->add('critical', null, null, 'AC_AUTH_INVALID_SIGNATURE_FORMAT');
                return false;
            }

            // Vérifier la signature
            $result = openssl_verify(
                $data,
                $signature_decoded,
                $public_key,
                OPENSSL_ALGO_SHA256
            );

            if ($result === 1) {
                return true;
            } elseif ($result === 0) {
                $this->log->add('critical', null, null, 'AC_AUTH_SIGNATURE_MISMATCH');
                return false;
            } else {
                $error = openssl_error_string();
                $this->log->add('critical', null, null, 'AC_AUTH_OPENSSL_ERROR', $error);
                return false;
            }
        } catch (\Exception $e) {
            $this->log->add('critical', null, null, 'AC_AUTH_EXCEPTION', $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie un jeton d'authentification avec timestamp
     *
     * @param string $token Jeton reçu
     * @param string $signature Signature du jeton
     * @param int $max_age Âge maximum du jeton en secondes (défaut: 300s = 5min)
     * @return bool True si le jeton est valide
     */
    public function verify_token($token, $signature, $max_age = 300)
    {
        // Vérifier la signature
        if (!$this->verify_signature($token, $signature)) {
            return false;
        }

        // Décoder le jeton
        $token_data = json_decode($token, true);
        if (!$token_data || !isset($token_data['timestamp']) || !isset($token_data['server_id'])) {
            $this->log->add('critical', null, null, 'AC_AUTH_INVALID_TOKEN_FORMAT');
            return false;
        }

        // Vérifier le timestamp (protection contre replay attacks)
        $current_time = time();
        $token_time = (int) $token_data['timestamp'];
        
        if ($token_time > $current_time) {
            $this->log->add('critical', null, null, 'AC_AUTH_TOKEN_FUTURE', [
                'token_time' => $token_time,
                'current_time' => $current_time
            ]);
            return false;
        }

        if (($current_time - $token_time) > $max_age) {
            $this->log->add('critical', null, null, 'AC_AUTH_TOKEN_EXPIRED', [
                'age' => $current_time - $token_time,
                'max_age' => $max_age
            ]);
            return false;
        }

        // Vérifier l'ID du serveur (optionnel mais recommandé)
        $expected_server_id = $this->config['ac_roguebb_server_id'] ?? null;
        if ($expected_server_id && $token_data['server_id'] !== $expected_server_id) {
            $this->log->add('critical', null, null, 'AC_AUTH_SERVER_ID_MISMATCH', [
                'expected' => $expected_server_id,
                'received' => $token_data['server_id']
            ]);
            return false;
        }

        // Tout est OK
        return true;
    }

    /**
     * Crée un fichier sécurisé authentifié
     *
     * @param string $filename Nom du fichier
     * @param string $content Contenu du fichier
     * @param string $token Jeton d'authentification
     * @param string $signature Signature du jeton
     * @return bool True si le fichier a été créé
     */
    public function create_authenticated_file($filename, $content, $token, $signature)
    {
        // Vérifier l'authentification
        if (!$this->verify_token($token, $signature)) {
            $this->log->add('critical', null, null, 'AC_AUTH_FILE_CREATION_DENIED', [
                'filename' => $filename
            ]);
            return false;
        }

        // Valider le nom du fichier (sécurité)
        if (!$this->is_safe_filename($filename)) {
            $this->log->add('critical', null, null, 'AC_AUTH_UNSAFE_FILENAME', [
                'filename' => $filename
            ]);
            return false;
        }

        // Construire le chemin complet
        $data_dir = dirname($this->public_key_path);
        $file_path = $data_dir . '/' . $filename;

        // Vérifier que le fichier n'existe pas déjà (optionnel)
        if (file_exists($file_path) && !$this->config['ac_allow_file_overwrite']) {
            $this->log->add('critical', null, null, 'AC_AUTH_FILE_EXISTS', [
                'filename' => $filename
            ]);
            return false;
        }

        // Écrire le fichier
        try {
            $bytes_written = file_put_contents($file_path, $content, LOCK_EX);
            
            if ($bytes_written === false) {
                $this->log->add('critical', null, null, 'AC_AUTH_FILE_WRITE_FAILED', [
                    'filename' => $filename
                ]);
                return false;
            }

            // Enregistrer l'événement
            $this->log->add('admin', null, null, 'AC_AUTH_FILE_CREATED', [
                'filename' => $filename,
                'size' => $bytes_written
            ]);

            return true;
        } catch (\Exception $e) {
            $this->log->add('critical', null, null, 'AC_AUTH_FILE_EXCEPTION', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Charge la clé publique
     *
     * @return resource|false Ressource de clé publique ou false
     */
    protected function get_public_key()
    {
        // Utiliser le cache si disponible
        if ($this->public_key_cache !== null) {
            return $this->public_key_cache;
        }

        // Vérifier que le fichier existe
        if (!file_exists($this->public_key_path)) {
            return false;
        }

        // Charger la clé
        $public_key_content = file_get_contents($this->public_key_path);
        if ($public_key_content === false) {
            return false;
        }

        // Créer la ressource OpenSSL
        $public_key = openssl_pkey_get_public($public_key_content);
        if ($public_key === false) {
            return false;
        }

        // Mettre en cache
        $this->public_key_cache = $public_key;

        return $public_key;
    }

    /**
     * Vérifie si un nom de fichier est sécurisé
     *
     * @param string $filename Nom du fichier
     * @return bool True si le nom est sécurisé
     */
    protected function is_safe_filename($filename)
    {
        // Interdire les chemins relatifs
        if (strpos($filename, '..') !== false) {
            return false;
        }

        // Interdire les slashes (pas de sous-dossiers)
        if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return false;
        }

        // Autoriser seulement les caractères alphanumériques, tirets, underscores et points
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            return false;
        }

        // Vérifier l'extension (optionnel - autoriser seulement certaines extensions)
        $allowed_extensions = ['json', 'txt', 'log', 'dat'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowed_extensions)) {
            return false;
        }

        return true;
    }

    /**
     * Génère un hash de vérification pour un fichier
     *
     * @param string $filename Nom du fichier
     * @return string|false Hash SHA256 ou false
     */
    public function get_file_hash($filename)
    {
        $data_dir = dirname($this->public_key_path);
        $file_path = $data_dir . '/' . $filename;

        if (!file_exists($file_path)) {
            return false;
        }

        return hash_file('sha256', $file_path);
    }

    /**
     * Révoque la clé publique actuelle
     *
     * @return bool True si la clé a été révoquée
     */
    public function revoke_public_key()
    {
        if (file_exists($this->public_key_path)) {
            $backup_path = $this->public_key_path . '.revoked.' . time();
            
            if (rename($this->public_key_path, $backup_path)) {
                $this->public_key_cache = null;
                $this->log->add('admin', null, null, 'AC_AUTH_KEY_REVOKED');
                return true;
            }
        }
        
        return false;
    }

    /**
     * Installe une nouvelle clé publique
     *
     * @param string $public_key_content Contenu de la clé publique
     * @return bool True si la clé a été installée
     */
    public function install_public_key($public_key_content)
    {
        // Vérifier que c'est une clé valide
        $key = openssl_pkey_get_public($public_key_content);
        if ($key === false) {
            $this->log->add('critical', null, null, 'AC_AUTH_INVALID_PUBLIC_KEY');
            return false;
        }

        // Créer le dossier data si nécessaire
        $data_dir = dirname($this->public_key_path);
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }

        // Écrire la clé
        $result = file_put_contents($this->public_key_path, $public_key_content, LOCK_EX);
        
        if ($result !== false) {
            $this->public_key_cache = null; // Invalider le cache
            $this->log->add('admin', null, null, 'AC_AUTH_KEY_INSTALLED');
            return true;
        }

        return false;
    }
}
