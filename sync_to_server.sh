#!/bin/bash

# Script de synchronisation de l'extension vers le serveur phpBB
# Usage: ./sync_to_server.sh

SOURCE_DIR="/home/nox/Documents/phpbb-ext/activitycontrol"
TARGET_DIR="/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol"

echo "Synchronisation de l'extension vers le serveur..."
rsync -av --delete "$SOURCE_DIR/" "$TARGET_DIR/"

if [ $? -eq 0 ]; then
    echo "✓ Synchronisation réussie !"
    echo "L'extension a été mise à jour sur le serveur."
else
    echo "✗ Erreur lors de la synchronisation"
    exit 1
fi
