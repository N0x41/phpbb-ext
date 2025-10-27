#!/bin/bash
# Script utilitaire pour gérer le serveur RogueBB facilement

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Activer l'environnement virtuel si disponible
if [ -d ".venv" ]; then
    source .venv/bin/activate
fi

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions utilitaires
print_header() {
    echo -e "${BLUE}============================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Vérifier si le serveur est en cours d'exécution
check_server() {
    if pgrep -f "python.*server.py" > /dev/null; then
        return 0
    else
        return 1
    fi
}

# Démarrer le serveur
start_server() {
    print_header "Démarrage du serveur RogueBB"
    
    if check_server; then
        print_error "Le serveur est déjà en cours d'exécution"
        print_info "PID: $(pgrep -f 'python.*server.py')"
        return 1
    fi
    
    print_info "Démarrage en arrière-plan..."
    nohup python3 server.py > server.log 2>&1 &
    
    sleep 2
    
    if check_server; then
        print_success "Serveur démarré avec succès"
        print_info "PID: $(pgrep -f 'python.*server.py')"
        print_info "Dashboard: http://localhost:5000"
        print_info "Logs: $SCRIPT_DIR/server.log"
    else
        print_error "Échec du démarrage du serveur"
        print_info "Consultez server.log pour plus de détails"
        return 1
    fi
}

# Arrêter le serveur
stop_server() {
    print_header "Arrêt du serveur RogueBB"
    
    if ! check_server; then
        print_error "Le serveur n'est pas en cours d'exécution"
        return 1
    fi
    
    PID=$(pgrep -f "python.*server.py")
    print_info "Arrêt du serveur (PID: $PID)..."
    
    kill $PID
    sleep 2
    
    if ! check_server; then
        print_success "Serveur arrêté avec succès"
    else
        print_error "Le serveur ne répond pas, forçage de l'arrêt..."
        kill -9 $PID
        sleep 1
        
        if ! check_server; then
            print_success "Serveur forcé à l'arrêt"
        else
            print_error "Impossible d'arrêter le serveur"
            return 1
        fi
    fi
}

# Redémarrer le serveur
restart_server() {
    print_header "Redémarrage du serveur RogueBB"
    stop_server
    sleep 1
    start_server
}

# Afficher le statut
status_server() {
    print_header "Statut du serveur RogueBB"
    
    if check_server; then
        print_success "Le serveur est EN COURS D'EXÉCUTION"
        echo ""
        print_info "PID: $(pgrep -f 'python.*server.py')"
        print_info "Dashboard: http://localhost:5000"
        
        # Récupérer les statistiques
        if command -v curl > /dev/null; then
            echo ""
            print_info "Récupération des statistiques..."
            python3 get_ip_list.py --count 2>/dev/null || print_error "Impossible de récupérer les stats"
        fi
    else
        print_error "Le serveur est ARRÊTÉ"
    fi
}

# Afficher les logs
show_logs() {
    print_header "Logs du serveur RogueBB"
    
    if [ -f "server.log" ]; then
        tail -n 50 server.log
    else
        print_error "Fichier de log introuvable"
    fi
}

# Suivre les logs en temps réel
follow_logs() {
    print_header "Suivi des logs en temps réel"
    
    if [ -f "server.log" ]; then
        print_info "Appuyez sur Ctrl+C pour quitter"
        echo ""
        tail -f server.log
    else
        print_error "Fichier de log introuvable"
    fi
}

# Soumettre une IP
submit_ip() {
    print_header "Soumission d'IP"
    
    if [ -z "$1" ]; then
        print_error "Veuillez spécifier une IP"
        echo "Usage: $0 submit <ip>"
        return 1
    fi
    
    python3 client_example.py "$1"
}

# Soumettre depuis un fichier
submit_file() {
    print_header "Soumission en masse depuis un fichier"
    
    if [ -z "$1" ]; then
        print_error "Veuillez spécifier un fichier"
        echo "Usage: $0 submit-file <fichier.txt>"
        return 1
    fi
    
    if [ ! -f "$1" ]; then
        print_error "Fichier introuvable: $1"
        return 1
    fi
    
    python3 batch_submit_ips.py --file "$1"
}

# Afficher les statistiques
show_stats() {
    print_header "Statistiques du serveur"
    python3 get_ip_list.py --stats
}

# Sauvegarder la liste
backup_list() {
    print_header "Sauvegarde de la liste d'IPs"
    
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).txt"
    
    if [ -n "$1" ]; then
        BACKUP_FILE="$1"
    fi
    
    python3 get_ip_list.py --save "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        print_success "Liste sauvegardée: $BACKUP_FILE"
    fi
}

# Interroger les nœuds
query_nodes() {
    print_header "Interrogation des nœuds phpBB"
    
    if [ -z "$1" ]; then
        print_error "Type de requête non spécifié"
        echo ""
        echo "Types de requêtes disponibles:"
        echo "  status        Statut et configuration des nœuds"
        echo "  stats         Statistiques des forums"
        echo "  sync-now      Déclencher une synchronisation immédiate"
        echo "  local-ips     IPs bannies localement sur les forums"
        echo "  reported-ips  IPs signalées par les nœuds"
        echo ""
        echo "Usage: $0 query <type> [--node <url>]"
        return 1
    fi
    
    QUERY_TYPE="$1"
    shift
    
    # Convertir les tirets en underscores pour Python
    QUERY_TYPE_PY="${QUERY_TYPE//-/_}"
    
    python3 query_nodes.py "$QUERY_TYPE_PY" "$@"
}

# Ouvrir le dashboard
open_dashboard() {
    print_header "Ouverture du dashboard"
    
    if ! check_server; then
        print_error "Le serveur n'est pas en cours d'exécution"
        print_info "Démarrez-le avec: $0 start"
        return 1
    fi
    
    URL="http://localhost:5000"
    print_info "Ouverture de: $URL"
    
    if command -v xdg-open > /dev/null; then
        xdg-open "$URL"
    elif command -v gnome-open > /dev/null; then
        gnome-open "$URL"
    elif command -v firefox > /dev/null; then
        firefox "$URL" &
    else
        print_info "Impossible d'ouvrir le navigateur automatiquement"
        print_info "Ouvrez manuellement: $URL"
    fi
}

# Afficher l'aide
show_help() {
    print_header "RogueBB - Gestionnaire de serveur d'IPs"
    echo ""
    echo "Usage: $0 <commande> [arguments]"
    echo ""
    echo "Commandes de gestion du serveur:"
    echo "  start             Démarrer le serveur"
    echo "  stop              Arrêter le serveur"
    echo "  restart           Redémarrer le serveur"
    echo "  status            Afficher le statut du serveur"
    echo "  logs              Afficher les derniers logs"
    echo "  follow            Suivre les logs en temps réel"
    echo ""
    echo "Commandes de soumission:"
    echo "  submit <ip>       Soumettre une seule IP"
    echo "  submit-file <f>   Soumettre des IPs depuis un fichier"
    echo ""
    echo "Commandes d'information:"
    echo "  stats             Afficher les statistiques"
    echo "  dashboard         Ouvrir le dashboard web"
    echo "  backup [fichier]  Sauvegarder la liste d'IPs"
    echo ""
    echo "Commandes de requête aux nœuds:"
    echo "  query status           Statut et configuration des nœuds"
    echo "  query stats            Statistiques des forums"
    echo "  query sync-now         Déclencher une synchronisation"
    echo "  query local-ips        IPs bannies localement"
    echo "  query reported-ips     IPs signalées par les nœuds"
    echo ""
    echo "Options de requête:"
    echo "  --node <url>      Interroger un nœud spécifique"
    echo ""
    echo "Exemples:"
    echo "  $0 start"
    echo "  $0 submit 192.168.1.100"
    echo "  $0 submit-file mes_ips.txt"
    echo "  $0 stats"
    echo "  $0 query status"
    echo "  $0 query sync-now"
    echo "  $0 query stats --node http://forum.com/app.php/activitycontrol/api/query"
    echo "  $0 backup backup_$(date +%Y%m%d).txt"
    echo ""
}

# Menu principal
case "$1" in
    start)
        start_server
        ;;
    stop)
        stop_server
        ;;
    restart)
        restart_server
        ;;
    status)
        status_server
        ;;
    logs)
        show_logs
        ;;
    follow)
        follow_logs
        ;;
    submit)
        submit_ip "$2"
        ;;
    submit-file)
        submit_file "$2"
        ;;
    stats)
        show_stats
        ;;
    backup)
        backup_list "$2"
        ;;
    dashboard)
        open_dashboard
        ;;
    query)
        shift
        query_nodes "$@"
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        if [ -z "$1" ]; then
            show_help
        else
            print_error "Commande inconnue: $1"
            echo ""
            show_help
        fi
        exit 1
        ;;
esac

exit 0
