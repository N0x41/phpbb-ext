#!/bin/bash

# Script de préparation de release pour LinkGuarder Activity Control
# Ce script crée un package ZIP prêt pour publication

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
EXTENSION_DIR="activitycontrol"
BUILD_DIR="build"
RELEASE_DIR="release"
VERSION="1.0.0"
PACKAGE_NAME="linkguarder_activitycontrol_${VERSION}"

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   LinkGuarder Activity Control - Build Release        ║${NC}"
echo -e "${BLUE}║   Version: ${VERSION}                                     ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

# Fonction pour afficher les erreurs
error() {
    echo -e "${RED}✗ Erreur: $1${NC}" >&2
    exit 1
}

# Fonction pour afficher les succès
success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Fonction pour afficher les infos
info() {
    echo -e "${YELLOW}➜ $1${NC}"
}

# Vérifier que le dossier activitycontrol existe
if [ ! -d "$EXTENSION_DIR" ]; then
    error "Le dossier $EXTENSION_DIR n'existe pas"
fi

# Créer les dossiers de build
info "Préparation des dossiers de build..."
rm -rf "$BUILD_DIR" "$RELEASE_DIR"
mkdir -p "$BUILD_DIR" "$RELEASE_DIR"
success "Dossiers créés"

# Configuration du serveur central
echo ""
echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}Configuration du serveur central RogueBB${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
echo ""
echo "Entrez l'URL du serveur central RogueBB pour cette release"
echo -e "${YELLOW}Exemples:${NC}"
echo "  - http://localhost:5000 (développement)"
echo "  - http://votre-serveur.com:5000 (production)"
echo "  - https://roguebb.example.com (production avec SSL)"
echo ""
read -p "URL du serveur central [http://localhost:5000]: " CENTRAL_SERVER_URL
CENTRAL_SERVER_URL=${CENTRAL_SERVER_URL:-http://localhost:5000}

echo ""
info "URL configurée: ${CENTRAL_SERVER_URL}"
echo ""

# Copier les fichiers de l'extension
info "Copie des fichiers de l'extension..."
cp -r "$EXTENSION_DIR" "$BUILD_DIR/"
success "Fichiers copiés"

# Configurer l'URL du serveur dans les fichiers PHP
info "Configuration de l'URL du serveur central..."

# ip_ban_sync.php
sed -i "s|const CENTRAL_SERVER_URL = '[^']*'|const CENTRAL_SERVER_URL = '${CENTRAL_SERVER_URL}'|g" \
    "$BUILD_DIR/$EXTENSION_DIR/service/ip_ban_sync.php"

# ip_reporter.php
sed -i "s|const CENTRAL_SERVER_URL = '[^']*'|const CENTRAL_SERVER_URL = '${CENTRAL_SERVER_URL}'|g" \
    "$BUILD_DIR/$EXTENSION_DIR/service/ip_reporter.php"

success "URL du serveur configurée dans les fichiers"

# Vérifier que la modification a été appliquée
info "Vérification de la configuration..."
if grep -q "const CENTRAL_SERVER_URL = '${CENTRAL_SERVER_URL}'" "$BUILD_DIR/$EXTENSION_DIR/service/ip_ban_sync.php"; then
    success "Configuration vérifiée dans ip_ban_sync.php"
else
    error "La configuration n'a pas été appliquée correctement dans ip_ban_sync.php"
fi

if grep -q "const CENTRAL_SERVER_URL = '${CENTRAL_SERVER_URL}'" "$BUILD_DIR/$EXTENSION_DIR/service/ip_reporter.php"; then
    success "Configuration vérifiée dans ip_reporter.php"
else
    error "La configuration n'a pas été appliquée correctement dans ip_reporter.php"
fi

# Créer le fichier README pour l'installation
info "Création du README d'installation..."
cat > "$BUILD_DIR/README_INSTALLATION.txt" << EOF
╔════════════════════════════════════════════════════════════════╗
║  LinkGuarder Activity Control - Extension phpBB 3.3.x         ║
║  Version: ${VERSION}                                              ║
╚════════════════════════════════════════════════════════════════╝

INSTALLATION
============

1. Extraire le contenu de ce ZIP dans le dossier ext/linkguarder/
   de votre installation phpBB

   Structure attendue:
   phpBB/
   └── ext/
       └── linkguarder/
           └── activitycontrol/
               ├── acp/
               ├── adm/
               ├── config/
               └── ...

2. Se connecter à l'ACP (Administration Control Panel) de phpBB

3. Aller dans : Personnaliser > Gérer les extensions

4. Trouver "Activity Control" et cliquer sur "Activer"

5. Configurer l'extension :
   - Aller dans : Extensions > Activity Control > Settings
   - Configurer les paramètres selon vos besoins
   - Activer la synchronisation IP si vous utilisez RogueBB

CONFIGURATION DU SERVEUR CENTRAL
=================================

Cette release est préconfigurée avec l'URL du serveur central :
${CENTRAL_SERVER_URL}

Si vous devez changer cette URL plus tard, éditez ces fichiers :
- ext/linkguarder/activitycontrol/service/ip_ban_sync.php
- ext/linkguarder/activitycontrol/service/ip_reporter.php

Cherchez : const CENTRAL_SERVER_URL = '...'

CONFIGURATION NGINX (IMPORTANT)
================================

Pour que l'API REST fonctionne, vous devez configurer nginx pour
utiliser app.php au lieu de index.php pour le routing.

Dans votre configuration nginx, changez :
  rewrite ^(.*)$ /forum/index.php/\$1

En :
  rewrite ^(.*)$ /forum/app.php/\$1

Puis redémarrez nginx : sudo systemctl restart nginx

PRÉREQUIS
=========

- phpBB 3.3.1 ou supérieur
- PHP 7.4 ou supérieur
- Extension PHP OpenSSL (pour le signalement d'IP)
- Serveur web : Apache ou Nginx
- Base de données : MySQL, MariaDB ou PostgreSQL

FONCTIONNALITÉS
===============

✓ Filtrage automatique des liens pour utilisateurs à faible nombre de posts
✓ Synchronisation automatique des IP bannies avec serveur central
✓ Signalement d'IP suspectes au serveur central
✓ Groupes utilisateurs automatiques (restreints/partiellement vérifiés/vérifiés)
✓ API REST pour communication avec RogueBB
✓ Webhooks pour notifications temps réel
✓ Logs détaillés des actions

SUPPORT
=======

Documentation complète : https://github.com/N0x41/phpbb-ext
Issues : https://github.com/N0x41/phpbb-ext/issues

═══════════════════════════════════════════════════════════════════
Bon déploiement !
═══════════════════════════════════════════════════════════════════
EOF
success "README créé"

# Créer le fichier CHANGELOG
info "Création du CHANGELOG..."
cat > "$BUILD_DIR/CHANGELOG.txt" << EOF
╔════════════════════════════════════════════════════════════════╗
║  LinkGuarder Activity Control - CHANGELOG                     ║
╚════════════════════════════════════════════════════════════════╝

Version ${VERSION} - $(date +%Y-%m-%d)
═══════════════════════════════════════

✨ Fonctionnalités
  • Filtrage automatique des liens dans posts/signatures/profils
  • Synchronisation automatique avec serveur central RogueBB
  • Signalement d'IP suspectes avec signatures RSA
  • Système de groupes utilisateurs automatiques
  • API REST complète pour communication bidirectionnelle
  • Webhooks pour notifications en temps réel
  • Module d'administration complet
  • Logs détaillés de toutes les actions

🔒 Sécurité
  • Signatures RSA pour toutes les soumissions d'IP
  • Vérification des signatures côté serveur
  • Protection contre les abus de signalement

🛠️ Technique
  • Compatible phpBB 3.3.1+
  • PHP 7.4+
  • Extension OpenSSL requise
  • API REST via app.php routing
  • Support MySQL/MariaDB/PostgreSQL

📚 Documentation
  • README complet
  • Guide d'installation
  • Documentation API
  • Guide de dépannage

═══════════════════════════════════════════════════════════════════
EOF
success "CHANGELOG créé"

# Créer le fichier de configuration exemple pour RogueBB
info "Création du fichier de configuration RogueBB..."
cat > "$BUILD_DIR/ROGUEBB_CONFIG.txt" << EOF
╔════════════════════════════════════════════════════════════════╗
║  Configuration du serveur RogueBB pour cette installation     ║
╚════════════════════════════════════════════════════════════════╝

CONFIGURATION DE L'URL DE WEBHOOK
==================================

Dans votre serveur RogueBB (server.py), ajoutez l'URL de webhook
de votre forum dans la liste WEBHOOK_URLS :

WEBHOOK_URLS = [
    "http://votre-forum.com/app.php/notify"
]

URL DE NODE QUERY
=================

Pour que RogueBB puisse interroger votre forum, utilisez cette URL :

http://votre-forum.com/app.php/ac_node_query

Exemples de requêtes :
  • Status : {"query":"status"}
  • Stats : {"query":"stats"}
  • Sync : {"query":"sync_now"}

CLÉS RSA
========

1. Générer les clés sur le serveur RogueBB :
   cd roguebb/server/
   python3 generate_keys.py

2. Copier public_key.pem vers votre forum :
   ext/linkguarder/activitycontrol/data/public_key.pem

3. Copier private_key.pem (GARDEZ-LE SECRET) :
   ext/linkguarder/activitycontrol/data/private_key.pem

PERMISSIONS
===========

Les fichiers de clés doivent être lisibles par le serveur web :

chmod 644 ext/linkguarder/activitycontrol/data/public_key.pem
chmod 600 ext/linkguarder/activitycontrol/data/private_key.pem
chown www-data:www-data ext/linkguarder/activitycontrol/data/*.pem

SERVEUR CENTRAL CONFIGURÉ
==========================

Cette release est préconfigurée avec :
URL : ${CENTRAL_SERVER_URL}

═══════════════════════════════════════════════════════════════════
EOF
success "Configuration RogueBB créée"

# Créer l'archive ZIP
info "Création du package ZIP..."
cd "$BUILD_DIR"
zip -r "../$RELEASE_DIR/${PACKAGE_NAME}.zip" . -q
cd ..
success "Package créé : ${PACKAGE_NAME}.zip"

# Calculer le hash SHA256
info "Calcul du hash SHA256..."
SHA256=$(sha256sum "$RELEASE_DIR/${PACKAGE_NAME}.zip" | cut -d' ' -f1)
echo "$SHA256" > "$RELEASE_DIR/${PACKAGE_NAME}.zip.sha256"
success "Hash SHA256 : $SHA256"

# Créer un fichier de métadonnées
info "Création des métadonnées..."
cat > "$RELEASE_DIR/${PACKAGE_NAME}.json" << EOF
{
  "name": "linkguarder/activitycontrol",
  "version": "${VERSION}",
  "release_date": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "phpbb_version": ">=3.3.1",
  "php_version": ">=7.4",
  "package": "${PACKAGE_NAME}.zip",
  "sha256": "${SHA256}",
  "central_server_url": "${CENTRAL_SERVER_URL}",
  "files": {
    "extension": "${PACKAGE_NAME}.zip",
    "readme": "README_INSTALLATION.txt",
    "changelog": "CHANGELOG.txt",
    "roguebb_config": "ROGUEBB_CONFIG.txt"
  },
  "requirements": [
    "phpBB 3.3.1+",
    "PHP 7.4+",
    "PHP OpenSSL extension"
  ],
  "features": [
    "Automatic link filtering",
    "IP synchronization with central server",
    "Suspicious IP reporting",
    "User group management",
    "REST API endpoints",
    "Webhook notifications",
    "Complete ACP module"
  ]
}
EOF
success "Métadonnées créées"

# Afficher le résumé
echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Build terminé avec succès !                          ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}Package créé :${NC}"
echo "  📦 ${RELEASE_DIR}/${PACKAGE_NAME}.zip"
echo ""
echo -e "${GREEN}Fichiers inclus :${NC}"
echo "  📄 README_INSTALLATION.txt"
echo "  📄 CHANGELOG.txt"
echo "  📄 ROGUEBB_CONFIG.txt"
echo "  📁 activitycontrol/ (extension complète)"
echo ""
echo -e "${GREEN}Vérification :${NC}"
echo "  🔒 SHA256 : $SHA256"
echo "  💾 Taille : $(du -h "$RELEASE_DIR/${PACKAGE_NAME}.zip" | cut -f1)"
echo ""
echo -e "${GREEN}Configuration :${NC}"
echo "  🌐 Serveur central : ${CENTRAL_SERVER_URL}"
echo ""
echo -e "${YELLOW}Prochaines étapes :${NC}"
echo "  1. Tester l'installation du package"
echo "  2. Publier sur GitHub Releases"
echo "  3. Mettre à jour la documentation"
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"

# Nettoyer le dossier de build
read -p "Nettoyer le dossier de build ? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    rm -rf "$BUILD_DIR"
    success "Dossier de build nettoyé"
fi

echo ""
echo -e "${GREEN}✓ Release prête à être publiée !${NC}"
