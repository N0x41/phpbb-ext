<?php
/**
 * Test de l'enregistrement automatique
 */

define('IN_PHPBB', true);
$phpbb_root_path = '../NiMP/var/www/forum/';
$phpEx = 'php';

require($phpbb_root_path . 'common.' . $phpEx);

// Vérifier la config actuelle
echo "Config AVANT enregistrement:\n";
echo "  ac_last_ip_sync: " . $config['ac_last_ip_sync'] . "\n";
echo "  ac_ip_list_version: " . ($config['ac_ip_list_version'] ?? 'N/A') . "\n\n";

// Tenter l'enregistrement si nécessaire
if ($config['ac_last_ip_sync'] == 0)
{
    echo "⚠️  ac_last_ip_sync = 0, tentative d'enregistrement...\n\n";
    
    if ($phpbb_container->has('linkguarder.activitycontrol.server_registration'))
    {
        try {
            $registration_service = $phpbb_container->get('linkguarder.activitycontrol.server_registration');
            $result = $registration_service->register_to_server();
            
            if ($result['success']) {
                echo "✅ Enregistrement réussi!\n";
                echo "   Message: " . $result['message'] . "\n";
            } else {
                echo "❌ Échec de l'enregistrement\n";
                echo "   Message: " . $result['message'] . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Service server_registration non disponible\n";
    }
    
    // Attendre un peu que le serveur envoie les IPs
    echo "\n⏳ Attente de 3 secondes pour la réception des IPs...\n";
    sleep(3);
}

// Recharger la config
$config = $phpbb_container->get('config');

echo "\nConfig APRÈS enregistrement:\n";
echo "  ac_last_ip_sync: " . $config['ac_last_ip_sync'];
if ($config['ac_last_ip_sync'] > 0) {
    echo " (" . date('Y-m-d H:i:s', $config['ac_last_ip_sync']) . ")";
}
echo "\n";
echo "  ac_ip_list_version: " . ($config['ac_ip_list_version'] ?? 'N/A') . "\n";

// Vérifier si le fichier existe
$data_file = $phpbb_root_path . 'ext/linkguarder/activitycontrol/data/reported_ips.json';
if (file_exists($data_file)) {
    $size = filesize($data_file);
    echo "\n✅ Fichier reported_ips.json créé (" . number_format($size / 1024, 2) . " KB)\n";
} else {
    echo "\n❌ Fichier reported_ips.json non créé\n";
}

garbage_collection();
