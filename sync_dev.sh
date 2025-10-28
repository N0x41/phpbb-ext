#!/bin/bash
# Script de synchronisation bidirectionnelle pour le développement phpBB
# Synchronise activitycontrol/ <-> ~/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/

REPO_DIR="/home/nox/Documents/phpbb-ext/activitycontrol"
PHPBB_DIR="/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol"

# Couleurs pour les messages
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour synchroniser du repo vers phpBB
sync_to_phpbb() {
    echo -e "${BLUE}→ Synchronisation: Repo → phpBB${NC}"
    rsync -av --delete \
        --exclude='.git' \
        --exclude='*.backup' \
        --exclude='.DS_Store' \
        "$REPO_DIR/" "$PHPBB_DIR/"
    echo -e "${GREEN}✓ Fichiers synchronisés vers phpBB${NC}"
}

# Fonction pour synchroniser de phpBB vers le repo
sync_from_phpbb() {
    echo -e "${BLUE}← Synchronisation: phpBB → Repo${NC}"
    rsync -av --delete \
        --exclude='.git' \
        --exclude='*.backup' \
        --exclude='.DS_Store' \
        "$PHPBB_DIR/" "$REPO_DIR/"
    echo -e "${GREEN}✓ Fichiers synchronisés vers le repo${NC}"
}

# Fonction pour vider le cache phpBB
clear_cache() {
    echo -e "${YELLOW}🗑️  Vidage du cache phpBB...${NC}"
    rm -rf /home/nox/Documents/NiMP/var/www/forum/cache/*
    echo -e "${GREEN}✓ Cache vidé${NC}"
}

# Mode watch - surveille les changements
watch_mode() {
    echo -e "${GREEN}👁️  Mode surveillance activé${NC}"
    echo -e "${YELLOW}Appuyez sur Ctrl+C pour arrêter${NC}"
    echo ""
    
    # Synchronisation initiale
    sync_to_phpbb
    clear_cache
    
    # Surveillance des changements
    while true; do
        # Attendre un changement dans le repo
        inotifywait -r -e modify,create,delete,move \
            --exclude '\.git|\.backup|\.DS_Store' \
            "$REPO_DIR" 2>/dev/null
        
        if [ $? -eq 0 ]; then
            echo -e "\n${BLUE}Changement détecté!${NC}"
            sync_to_phpbb
            clear_cache
            echo -e "${GREEN}✓ Prêt pour le test${NC}\n"
        fi
        
        sleep 1
    done
}

# Menu principal
case "$1" in
    to-phpbb)
        sync_to_phpbb
        clear_cache
        ;;
    from-phpbb)
        sync_from_phpbb
        ;;
    watch)
        # Vérifier si inotify-tools est installé
        if ! command -v inotifywait &> /dev/null; then
            echo -e "${RED}✗ inotify-tools n'est pas installé${NC}"
            echo -e "${YELLOW}Installation: sudo apt-get install inotify-tools${NC}"
            exit 1
        fi
        watch_mode
        ;;
    *)
        echo "Usage: $0 {to-phpbb|from-phpbb|watch}"
        echo ""
        echo "Commandes:"
        echo "  to-phpbb    - Synchronise du repo vers phpBB"
        echo "  from-phpbb  - Synchronise de phpBB vers le repo"
        echo "  watch       - Mode surveillance (auto-sync + clear cache)"
        echo ""
        echo "Workflow recommandé:"
        echo "  1. Terminal 1: ./sync_dev.sh watch"
        echo "  2. Terminal 2: Éditer dans $REPO_DIR"
        echo "  3. Les changements sont automatiquement synchronisés"
        exit 1
        ;;
esac
