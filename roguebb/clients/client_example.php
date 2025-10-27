#!/usr/bin/env php
<?php
/**
 * Client PHP pour soumettre des IPs au serveur central
 * avec signature cryptographique RSA.
 * 
 * Usage:
 *     php client_example.php <ip_address>
 * 
 * Exemple:
 *     php client_example.php 192.168.1.100
 */

// Configuration
define('SERVER_URL', 'http://localhost:5000');
define('PRIVATE_KEY_PATH', 'private_key.pem');

/**
 * Charge la clé privée depuis le fichier PEM
 * 
 * @return resource|false La clé privée ou false en cas d'erreur
 */
function loadPrivateKey() {
    if (!file_exists(PRIVATE_KEY_PATH)) {
        echo "ERREUR: Fichier " . PRIVATE_KEY_PATH . " introuvable!\n";
        echo "Exécutez generate_keys.py pour créer les clés.\n";
        return false;
    }
    
    $privateKeyContent = file_get_contents(PRIVATE_KEY_PATH);
    $privateKey = openssl_pkey_get_private($privateKeyContent);
    
    if ($privateKey === false) {
        echo "ERREUR: Impossible de charger la clé privée!\n";
        echo openssl_error_string() . "\n";
        return false;
    }
    
    return $privateKey;
}

/**
 * Signe les données avec la clé privée RSA
 * 
 * @param resource $privateKey La clé privée
 * @param string $data Les données à signer
 * @return string|false La signature encodée en base64 ou false
 */
function signData($privateKey, $data) {
    $signature = '';
    
    // Signer avec RSA-PSS SHA256
    // Note: openssl_sign utilise PKCS#1 v1.5 par défaut
    // Pour PSS, on utilise openssl_sign avec OPENSSL_ALGO_SHA256
    $success = openssl_sign(
        $data,
        $signature,
        $privateKey,
        OPENSSL_ALGO_SHA256
    );
    
    if (!$success) {
        echo "ERREUR: Impossible de signer les données!\n";
        echo openssl_error_string() . "\n";
        return false;
    }
    
    // Encoder en base64 pour transmission
    return base64_encode($signature);
}

/**
 * Soumet une IP au serveur avec signature
 * 
 * @param string $ipAddress L'adresse IP à soumettre
 * @param resource $privateKey La clé privée pour signer
 * @return bool True si succès, false sinon
 */
function submitIP($ipAddress, $privateKey) {
    // Signer l'IP
    $signature = signData($privateKey, $ipAddress);
    
    if ($signature === false) {
        return false;
    }
    
    // Préparer le payload JSON
    $payload = [
        'ip' => $ipAddress,
        'signature' => $signature
    ];
    
    $jsonPayload = json_encode($payload);
    
    // Configurer le contexte HTTP
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                       "Content-Length: " . strlen($jsonPayload) . "\r\n",
            'content' => $jsonPayload,
            'timeout' => 10,
            'ignore_errors' => true // Pour récupérer le corps même en cas d'erreur HTTP
        ]
    ];
    
    $context = stream_context_create($options);
    
    // Envoyer la requête
    $response = @file_get_contents(SERVER_URL . '/api/submit_ip', false, $context);
    
    if ($response === false) {
        echo "ERREUR lors de l'envoi: Impossible de contacter le serveur\n";
        echo "Vérifiez que le serveur tourne sur " . SERVER_URL . "\n";
        return false;
    }
    
    // Extraire le code de statut HTTP
    $httpCode = 500;
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = isset($matches[1]) ? (int)$matches[1] : 500;
    }
    
    // Afficher la réponse
    echo "Statut HTTP: $httpCode\n";
    
    $responseData = json_decode($response, true);
    if ($responseData !== null) {
        echo "Réponse:\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo "Réponse brute: $response\n";
    }
    
    return $httpCode === 200;
}

/**
 * Fonction principale
 */
function main($argc, $argv) {
    if ($argc !== 2) {
        echo "Usage: php client_example.php <ip_address>\n";
        echo "Exemple: php client_example.php 192.168.1.100\n";
        exit(1);
    }
    
    $ipToSubmit = $argv[1];
    
    echo str_repeat('=', 60) . "\n";
    echo "Client PHP de soumission d'IP avec signature RSA\n";
    echo str_repeat('=', 60) . "\n\n";
    
    echo "ℹ️  ARCHITECTURE:\n";
    echo "   - Le client signe l'IP avec sa clé PRIVÉE\n";
    echo "   - Le serveur vérifie avec la clé PUBLIQUE\n";
    echo "   - Seuls les clients autorisés peuvent soumettre des IPs\n\n";
    
    // Charger la clé privée
    $privateKey = loadPrivateKey();
    
    if ($privateKey === false) {
        exit(1);
    }
    
    echo "✓ Clé privée chargée depuis " . PRIVATE_KEY_PATH . "\n";
    echo "📤 Soumission de l'IP: $ipToSubmit\n\n";
    
    // Soumettre l'IP
    $success = submitIP($ipToSubmit, $privateKey);
    
    // Libérer la clé
    openssl_pkey_free($privateKey);
    
    echo "\n";
    
    if ($success) {
        echo "✓ IP soumise avec succès!\n";
        exit(0);
    } else {
        echo "✗ Échec de la soumission\n";
        exit(1);
    }
}

// Point d'entrée
main($argc, $argv);
?>
