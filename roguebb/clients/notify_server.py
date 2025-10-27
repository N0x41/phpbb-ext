#!/usr/bin/env python3
"""
Client pour notifier le serveur RogueBB d'une mise à jour depuis un nœud phpBB
Le serveur RogueBB propagera ensuite la mise à jour vers tous les autres nœuds

Usage:
    python notify_server.py <server_url> <event_type> [data_json]
    
Example:
    python notify_server.py http://localhost:5000 ip_list_updated '{"version":123}'
"""

import json
import sys
import requests
from datetime import datetime


def notify_roguebb_server(server_url, event_type, data=None):
    """
    Notifie le serveur RogueBB d'un événement depuis un nœud
    
    Args:
        server_url: URL du serveur RogueBB (ex: http://localhost:5000)
        event_type: Type d'événement (ip_list_updated, config_changed, etc.)
        data: Données supplémentaires (dict)
        
    Returns:
        dict: Réponse du serveur
    """
    endpoint = f'{server_url.rstrip("/")}/node_notification'
    
    payload = {
        'event': event_type,
        'timestamp': int(datetime.now().timestamp()),
        'data': data or {}
    }
    
    try:
        response = requests.post(
            endpoint,
            json=payload,
            headers={'Content-Type': 'application/json'},
            timeout=10
        )
        
        try:
            result = response.json()
        except json.JSONDecodeError:
            result = {
                'status': 'error',
                'message': f'Invalid JSON response: {response.text[:200]}'
            }
        
        result['http_status'] = response.status_code
        return result
        
    except requests.exceptions.RequestException as e:
        return {
            'status': 'error',
            'message': f'Request failed: {str(e)}',
            'http_status': 0
        }


def main():
    """Point d'entrée principal"""
    if len(sys.argv) < 3:
        print("Usage: python notify_server.py <server_url> <event_type> [data_json]")
        print("")
        print("Arguments:")
        print("  server_url  : URL du serveur RogueBB (ex: http://localhost:5000)")
        print("  event_type  : Type d'événement (ip_list_updated, config_changed, etc.)")
        print("  data_json   : Données JSON optionnelles (ex: '{\"version\":123}')")
        print("")
        print("Exemples:")
        print("  python notify_server.py http://localhost:5000 ip_list_updated")
        print("  python notify_server.py http://localhost:5000 config_changed '{\"key\":\"value\"}'")
        sys.exit(1)
    
    server_url = sys.argv[1]
    event_type = sys.argv[2]
    
    # Parser les données si fournies
    data = None
    if len(sys.argv) > 3:
        try:
            data = json.loads(sys.argv[3])
        except json.JSONDecodeError as e:
            print(f"Erreur: JSON invalide - {e}")
            sys.exit(1)
    
    print(f"📢 Notification du serveur RogueBB")
    print(f"   URL:        {server_url}")
    print(f"   Événement:  {event_type}")
    print(f"   Données:    {json.dumps(data) if data else 'Aucune'}")
    print("")
    
    # Envoyer la notification
    result = notify_roguebb_server(server_url, event_type, data)
    
    # Afficher le résultat
    print(f"📡 Réponse (HTTP {result['http_status']}):")
    print(json.dumps(result, indent=2))
    
    # Code de sortie
    if result.get('status') == 'ok':
        print("")
        print("✓ Notification envoyée avec succès !")
        print("Le serveur propagera la mise à jour vers tous les nœuds...")
        sys.exit(0)
    else:
        print("")
        print("✗ Échec de la notification")
        sys.exit(1)


if __name__ == '__main__':
    main()
