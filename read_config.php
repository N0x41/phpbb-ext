<?php
/**
 * Script pour lire et afficher config.php
 */

// Obtenir le chemin de base du script
$base_path = __DIR__;

// Construire le chemin relatif vers config.php
$config_path = $base_path . '/../../config.php';

// Détection du mode d'affichage (CLI ou web)
$is_cli = php_sapi_name() === 'cli';
$br = $is_cli ? "\n" : "<br>\n";
$bold_start = $is_cli ? "" : "<strong>";
$bold_end = $is_cli ? "" : "</strong>";
$pre_start = $is_cli ? "" : "<pre style='background:#f5f5f5;padding:15px;border-radius:5px;border:1px solid #ddd;'>";
$pre_end = $is_cli ? "" : "</pre>";

if (!$is_cli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Configuration phpBB</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f0f0f0;}";
    echo ".container{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);max-width:900px;margin:0 auto;}";
    echo "h2{color:#333;border-bottom:2px solid #0066cc;padding-bottom:10px;}";
    echo ".info-line{padding:8px;margin:5px 0;background:#f9f9f9;border-left:3px solid #0066cc;}</style></head><body>";
    echo "<div class='container'>";
}

echo "{$bold_start}=== Configuration phpBB ==={$bold_end}{$br}{$br}";

echo "📂 Chemin de base: {$bold_start}{$base_path}{$bold_end}{$br}";
echo "📄 Chemin vers config.php: {$bold_start}{$config_path}{$bold_end}{$br}";
echo "🔗 Chemin absolu: {$bold_start}" . realpath($config_path) . "{$bold_end}{$br}{$br}";

// Vérifier que le fichier existe
if (!file_exists($config_path)) {
    echo "{$bold_start}❌ Erreur:{$bold_end} Le fichier config.php n'existe pas à l'emplacement: {$config_path}{$br}";
    if (!$is_cli) echo "</div></body></html>";
    exit(1);
}

// Inclure le fichier pour accéder aux variables
include($config_path);

echo "{$br}{$bold_start}=== Configuration de la base de données ==={$bold_end}{$br}{$br}";

if (!$is_cli) echo "<div class='info-line'>";
echo "🗄️  Type de base de données: {$bold_start}" . (isset($dbms) ? $dbms : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "🖥️  Hôte: {$bold_start}" . (isset($dbhost) ? $dbhost : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "🔌 Port: {$bold_start}" . (isset($dbport) ? $dbport : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "📊 Nom de la base: {$bold_start}" . (isset($dbname) ? $dbname : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "👤 Utilisateur: {$bold_start}" . (isset($dbuser) ? $dbuser : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "🔐 Mot de passe: {$bold_start}" . (isset($dbpasswd) ? $dbpasswd : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div>";

echo "{$br}{$bold_start}=== Configuration phpBB ==={$bold_end}{$br}{$br}";

if (!$is_cli) echo "<div class='info-line'>";
echo "📦 Préfixe des tables: {$bold_start}" . (isset($table_prefix) ? $table_prefix : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "💾 Type de cache: {$bold_start}" . (isset($acm_type) ? $acm_type : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "✅ phpBB installé: {$bold_start}" . (defined('PHPBB_INSTALLED') ? 'Oui' : 'Non') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div><div class='info-line'>";
echo "🌍 Environnement: {$bold_start}" . (defined('PHPBB_ENVIRONMENT') ? PHPBB_ENVIRONMENT : 'Non défini') . "{$bold_end}{$br}";
if (!$is_cli) echo "</div>";

if (!$is_cli) {
    echo "</div></body></html>";
}

?>
