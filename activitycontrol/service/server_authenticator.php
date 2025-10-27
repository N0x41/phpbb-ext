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
                // Log disabled: 'critical', null, null, 'AC_AUTH_NO_PUBLIC_KEY');
                // Log disabled: 'critical', null, null, 'AC_AUTH_INVALID_SIGNATURE_FORMAT');
                // Log disabled: 'critical', null, null, 'AC_AUTH_SIGNATURE_MISMATCH');
                // Log disabled: 'critical', null, null, 'AC_AUTH_OPENSSL_ERROR', $error);
            // Log disabled: 'critical', null, null, 'AC_AUTH_EXCEPTION', $e->getMessage());
            // Log disabled: 'critical', null, null, 'AC_AUTH_INVALID_TOKEN_FORMAT');
            // Log disabled: 'critical', null, null, 'AC_AUTH_FILE_CREATION_DENIED', [
            return false;
        }

        // Valider le nom du fichier (sécurité)
        if (!$this->is_safe_filename($filename)) {
            // Log disabled: 'critical', null, null, 'AC_AUTH_UNSAFE_FILENAME', [
            return false;
        }

        // Construire le chemin complet
        $data_dir = dirname($this->public_key_path);
        $file_path = $data_dir . '/' . $filename;

        // Vérifier que le fichier n'existe pas déjà (optionnel)
        if (file_exists($file_path) && !$this->config['ac_allow_file_overwrite']) {
            // Log disabled: 'critical', null, null, 'AC_AUTH_FILE_EXISTS', [
            return false;
        }

        // Écrire le fichier
        try {
            $bytes_written = file_put_contents($file_path, $content, LOCK_EX);
            
            if ($bytes_written === false) {
                // Log disabled: 'critical', null, null, 'AC_AUTH_FILE_WRITE_FAILED', [
                return false;
            }

            // Enregistrer l'événement
            // Log disabled: 'admin', null, null, 'AC_AUTH_FILE_CREATED', [

            return true;
        } catch (\Exception $e) {
            // Log disabled: 'critical', null, null, 'AC_AUTH_FILE_EXCEPTION', [
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
                // Log disabled: 'admin', null, null, 'AC_AUTH_KEY_REVOKED');
            // Log disabled: 'critical', null, null, 'AC_AUTH_INVALID_PUBLIC_KEY');
            // Log disabled: 'admin', null, null, 'AC_AUTH_KEY_INSTALLED');
