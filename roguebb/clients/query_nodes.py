#!/usr/bin/env python3
"""
Script pour interroger les nœuds phpBB connectés.

Usage:
    python3 query_nodes.py status
    python3 query_nodes.py stats
    python3 query_nodes.py sync_now
    python3 query_nodes.py local_ips
    python3 query_nodes.py reported_ips
    python3 query_nodes.py <query_type> --node <url>

Exemples:
    python3 query_nodes.py status
    python3 query_nodes.py stats
    python3 query_nodes.py sync_now --node http://forum1.com/app.php/activitycontrol/api/query
    python3 query_nodes.py reported_ips
"""

import sys
import json
import requests
from datetime import datetime

# Configuration
SERVER_URL = "http://localhost:5000"


def get_registered_nodes():
    """Récupère la liste des nœuds enregistrés depuis le serveur."""
    try:
        response = requests.get(f"{SERVER_URL}/api/webhooks", timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            webhooks = data.get('webhooks', [])
            
            # Convertir les URLs webhook en URLs de requête
            nodes = []
            for webhook_url in webhooks:
                # Remplacer /webhook/notify par /api/query
                node_url = webhook_url.replace('/webhook/notify', '/api/query')
                nodes.append(node_url)
            
            return nodes
        else:
            print(f"❌ Erreur lors de la récupération des nœuds: HTTP {response.status_code}")
            return []
            
    except requests.RequestException as e:
        print(f"❌ Erreur de connexion au serveur RogueBB: {e}")
        return []


def query_node(node_url, query_type):
    """Envoie une requête à un nœud phpBB."""
    payload = {'query': query_type}
    
    try:
        response = requests.post(
            node_url,
            json=payload,
            timeout=10,
            headers={'Content-Type': 'application/json'}
        )
        
        if response.status_code == 200:
            return {
                'success': True,
                'data': response.json(),
                'node_url': node_url
            }
        else:
            return {
                'success': False,
                'error': f"HTTP {response.status_code}",
                'message': response.text[:200],
                'node_url': node_url
            }
            
    except requests.RequestException as e:
        return {
            'success': False,
            'error': 'Connection error',
            'message': str(e),
            'node_url': node_url
        }


def format_timestamp(timestamp):
    """Formate un timestamp Unix en date lisible."""
    try:
        return datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S')
    except:
        return 'N/A'


def display_status_results(results):
    """Affiche les résultats de la requête status."""
    print("=" * 80)
    print("📊 STATUT DES NŒUDS")
    print("=" * 80)
    
    for i, result in enumerate(results, 1):
        print(f"\n🔸 Nœud {i}: {result['node_url']}")
        print("-" * 80)
        
        if result['success']:
            data = result['data']
            print(f"  ✅ Statut          : {data.get('status', 'unknown')}")
            print(f"  📱 Type            : {data.get('node_type', 'unknown')}")
            print(f"  🏷️  Nom du forum    : {data.get('forum_name', 'N/A')}")
            print(f"  📦 Version phpBB   : {data.get('phpbb_version', 'N/A')}")
            print(f"  🔧 Version ext.    : {data.get('extension_version', 'N/A')}")
            print(f"  🔄 Sync activée    : {'✅ Oui' if data.get('sync_enabled') else '❌ Non'}")
            print(f"  📡 Report activé   : {'✅ Oui' if data.get('reporting_enabled') else '❌ Non'}")
            print(f"  🕒 Dernière sync   : {format_timestamp(data.get('last_sync', 0))}")
            print(f"  📋 Version liste   : {data.get('ip_list_version', 0)}")
            print(f"  ⏰ Timestamp       : {format_timestamp(data.get('timestamp', 0))}")
        else:
            print(f"  ❌ Erreur          : {result['error']}")
            print(f"  💬 Message         : {result['message']}")
    
    print("\n" + "=" * 80)


def display_stats_results(results):
    """Affiche les résultats de la requête stats."""
    print("=" * 80)
    print("📈 STATISTIQUES DES NŒUDS")
    print("=" * 80)
    
    for i, result in enumerate(results, 1):
        print(f"\n🔸 Nœud {i}: {result['node_url']}")
        print("-" * 80)
        
        if result['success']:
            data = result['data']
            stats = data.get('stats', {})
            
            print(f"  🛡️  IPs bannies     : {stats.get('banned_ips', 0):,}")
            print(f"  👥 Utilisateurs    : {stats.get('total_users', 0):,}")
            print(f"  📝 Messages        : {stats.get('total_posts', 0):,}")
            print(f"  💬 Sujets          : {stats.get('total_topics', 0):,}")
            print(f"  🕒 Dernière sync   : {format_timestamp(stats.get('last_sync', 0))}")
            print(f"  📋 Version liste   : {stats.get('ip_list_version', 0)}")
            print(f"  ⏰ Timestamp       : {format_timestamp(data.get('timestamp', 0))}")
        else:
            print(f"  ❌ Erreur          : {result['error']}")
            print(f"  💬 Message         : {result['message']}")
    
    print("\n" + "=" * 80)


def display_sync_results(results):
    """Affiche les résultats de la requête sync_now."""
    print("=" * 80)
    print("🔄 SYNCHRONISATION DES NŒUDS")
    print("=" * 80)
    
    for i, result in enumerate(results, 1):
        print(f"\n🔸 Nœud {i}: {result['node_url']}")
        print("-" * 80)
        
        if result['success']:
            data = result['data']
            stats = data.get('stats', {})
            
            print(f"  ✅ Statut          : {data.get('status', 'unknown')}")
            print(f"  💬 Message         : {data.get('message', 'N/A')}")
            print(f"  ➕ IPs ajoutées    : {stats.get('added', 0)}")
            print(f"  ➖ IPs retirées    : {stats.get('removed', 0)}")
            print(f"  📊 Total IPs       : {stats.get('total', 0):,}")
            print(f"  ⏰ Timestamp       : {format_timestamp(data.get('timestamp', 0))}")
        else:
            print(f"  ❌ Erreur          : {result['error']}")
            print(f"  💬 Message         : {result['message']}")
    
    print("\n" + "=" * 80)


def display_ips_results(results, title="IPs"):
    """Affiche les résultats des requêtes d'IPs."""
    print("=" * 80)
    print(f"🌐 {title}")
    print("=" * 80)
    
    for i, result in enumerate(results, 1):
        print(f"\n🔸 Nœud {i}: {result['node_url']}")
        print("-" * 80)
        
        if result['success']:
            data = result['data']
            ips = data.get('ips', [])
            
            print(f"  📊 Nombre d'IPs    : {data.get('count', 0)}")
            
            if 'note' in data:
                print(f"  ℹ️  Note           : {data['note']}")
            
            if ips:
                print(f"\n  📋 Liste des IPs:")
                for j, ip_data in enumerate(ips[:20], 1):  # Limiter à 20 pour l'affichage
                    if isinstance(ip_data, dict):
                        # Pour reported_ips
                        ip = ip_data.get('ip', 'N/A')
                        reason = ip_data.get('reason', 'N/A')
                        count = ip_data.get('count', 0)
                        submitted = '✅' if ip_data.get('submitted') else '❌'
                        print(f"    {j:3d}. {ip:20s} - {reason[:30]:30s} (x{count}) [{submitted}]")
                    else:
                        # Pour local_ips
                        print(f"    {j:3d}. {ip_data}")
                
                if len(ips) > 20:
                    print(f"    ... et {len(ips) - 20} de plus")
            else:
                print(f"  ℹ️  Aucune IP trouvée")
            
            print(f"  ⏰ Timestamp       : {format_timestamp(data.get('timestamp', 0))}")
        else:
            print(f"  ❌ Erreur          : {result['error']}")
            print(f"  💬 Message         : {result['message']}")
    
    print("\n" + "=" * 80)


def main():
    if len(sys.argv) < 2:
        print("=" * 80)
        print("🔍 Interrogation des Nœuds phpBB")
        print("=" * 80)
        print()
        print("Usage: python3 query_nodes.py <query_type> [options]")
        print()
        print("Types de requêtes:")
        print("  status        Statut et configuration du nœud")
        print("  stats         Statistiques du forum")
        print("  sync_now      Déclencher une synchronisation immédiate")
        print("  local_ips     IPs bannies localement sur le forum")
        print("  reported_ips  IPs signalées par ce nœud à RogueBB")
        print()
        print("Options:")
        print("  --node <url>  Interroger un nœud spécifique uniquement")
        print()
        print("Exemples:")
        print("  python3 query_nodes.py status")
        print("  python3 query_nodes.py stats")
        print("  python3 query_nodes.py sync_now")
        print("  python3 query_nodes.py local_ips --node http://forum.com/app.php/activitycontrol/api/query")
        print("=" * 80)
        sys.exit(1)
    
    query_type = sys.argv[1]
    
    # Vérifier si une URL spécifique est fournie
    specific_node = None
    if '--node' in sys.argv:
        try:
            node_index = sys.argv.index('--node')
            specific_node = sys.argv[node_index + 1]
        except (IndexError, ValueError):
            print("❌ Erreur: URL manquante après --node")
            sys.exit(1)
    
    # Déterminer les nœuds à interroger
    if specific_node:
        nodes = [specific_node]
        print(f"🔍 Interrogation d'un nœud spécifique: {specific_node}")
    else:
        print("🔍 Récupération de la liste des nœuds...")
        nodes = get_registered_nodes()
        
        if not nodes:
            print("⚠️  Aucun nœud enregistré trouvé")
            print("💡 Ajoutez des webhooks avec: python3 manage_webhooks.py add <url>")
            sys.exit(1)
        
        print(f"✅ {len(nodes)} nœud(s) trouvé(s)")
    
    print()
    
    # Interroger tous les nœuds
    results = []
    for node_url in nodes:
        print(f"📡 Interrogation de: {node_url}...")
        result = query_node(node_url, query_type)
        results.append(result)
    
    print()
    
    # Afficher les résultats selon le type de requête
    if query_type == 'status':
        display_status_results(results)
    elif query_type == 'stats':
        display_stats_results(results)
    elif query_type == 'sync_now':
        display_sync_results(results)
    elif query_type == 'local_ips':
        display_ips_results(results, "IPs BANNIES LOCALEMENT")
    elif query_type == 'reported_ips':
        display_ips_results(results, "IPs SIGNALÉES PAR LES NŒUDS")
    else:
        print(f"❌ Type de requête inconnu: {query_type}")
        sys.exit(1)
    
    # Compter les succès et échecs
    success_count = sum(1 for r in results if r['success'])
    failure_count = len(results) - success_count
    
    print(f"✅ Succès: {success_count} | ❌ Échecs: {failure_count}")
    
    sys.exit(0 if failure_count == 0 else 1)


if __name__ == '__main__':
    main()
