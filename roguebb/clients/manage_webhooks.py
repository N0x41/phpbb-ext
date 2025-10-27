#!/usr/bin/env python3
"""
Script pour gérer les webhooks du serveur RogueBB.

Usage:
    python3 manage_webhooks.py list
    python3 manage_webhooks.py add <url>
    python3 manage_webhooks.py remove <url>
    python3 manage_webhooks.py test <url>

Exemples:
    python3 manage_webhooks.py list
    python3 manage_webhooks.py add http://forum.local/app.php/activitycontrol/webhook/notify
    python3 manage_webhooks.py test http://forum.local/app.php/activitycontrol/webhook/notify
    python3 manage_webhooks.py remove http://forum.local/app.php/activitycontrol/webhook/notify
"""

import sys
import json
import requests

# Configuration
SERVER_URL = "http://localhost:5000"


def list_webhooks():
    """Liste tous les webhooks configurés."""
    try:
        response = requests.get(f"{SERVER_URL}/api/webhooks", timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            webhooks = data.get('webhooks', [])
            count = data.get('count', 0)
            
            print("=" * 70)
            print(f"📋 WEBHOOKS CONFIGURÉS ({count})")
            print("=" * 70)
            
            if webhooks:
                for i, webhook in enumerate(webhooks, 1):
                    print(f"{i}. {webhook}")
            else:
                print("Aucun webhook configuré.")
            
            print("=" * 70)
            return True
        else:
            print(f"❌ Erreur HTTP {response.status_code}")
            return False
            
    except requests.RequestException as e:
        print(f"❌ Erreur de connexion: {e}")
        return False


def add_webhook(webhook_url):
    """Ajoute un nouveau webhook."""
    try:
        response = requests.post(
            f"{SERVER_URL}/api/webhooks/add",
            json={'url': webhook_url},
            timeout=10
        )
        
        data = response.json()
        status = data.get('status')
        
        if status == 'added':
            print(f"✅ Webhook ajouté avec succès:")
            print(f"   URL: {webhook_url}")
            print(f"   Total: {data.get('total_webhooks', 0)} webhook(s)")
            return True
        elif status == 'already_exists':
            print(f"ℹ️  Ce webhook existe déjà:")
            print(f"   URL: {webhook_url}")
            return True
        else:
            print(f"❌ Erreur: {data.get('message', 'Unknown error')}")
            return False
            
    except requests.RequestException as e:
        print(f"❌ Erreur de connexion: {e}")
        return False


def remove_webhook(webhook_url):
    """Retire un webhook existant."""
    try:
        response = requests.post(
            f"{SERVER_URL}/api/webhooks/remove",
            json={'url': webhook_url},
            timeout=10
        )
        
        data = response.json()
        status = data.get('status')
        
        if status == 'removed':
            print(f"✅ Webhook retiré avec succès:")
            print(f"   URL: {webhook_url}")
            print(f"   Restant: {data.get('total_webhooks', 0)} webhook(s)")
            return True
        elif status == 'not_found':
            print(f"❌ Ce webhook n'existe pas:")
            print(f"   URL: {webhook_url}")
            return False
        else:
            print(f"❌ Erreur: {data.get('message', 'Unknown error')}")
            return False
            
    except requests.RequestException as e:
        print(f"❌ Erreur de connexion: {e}")
        return False


def test_webhook(webhook_url):
    """Teste un webhook en envoyant une notification."""
    print(f"🧪 Test du webhook: {webhook_url}")
    print("   Envoi d'une notification de test...")
    
    try:
        response = requests.post(
            f"{SERVER_URL}/api/webhooks/test",
            json={'url': webhook_url},
            timeout=10
        )
        
        data = response.json()
        status = data.get('status')
        
        if status == 'success':
            print(f"✅ Test réussi!")
            print(f"   HTTP Code: {data.get('http_code', '?')}")
            print(f"   Réponse: {data.get('response', '')[:200]}...")
            return True
        else:
            print(f"❌ Test échoué:")
            print(f"   Message: {data.get('message', 'Unknown error')}")
            return False
            
    except requests.RequestException as e:
        print(f"❌ Erreur de connexion: {e}")
        return False


def show_help():
    """Affiche l'aide."""
    print("=" * 70)
    print("🔔 Gestionnaire de Webhooks RogueBB")
    print("=" * 70)
    print()
    print("Usage: python3 manage_webhooks.py <commande> [arguments]")
    print()
    print("Commandes:")
    print("  list              Liste tous les webhooks configurés")
    print("  add <url>         Ajoute un nouveau webhook")
    print("  remove <url>      Retire un webhook existant")
    print("  test <url>        Teste un webhook")
    print()
    print("Exemples:")
    print("  python3 manage_webhooks.py list")
    print("  python3 manage_webhooks.py add http://forum.local/app.php/activitycontrol/webhook/notify")
    print("  python3 manage_webhooks.py test http://forum.local/app.php/activitycontrol/webhook/notify")
    print("  python3 manage_webhooks.py remove http://forum.local/app.php/activitycontrol/webhook/notify")
    print()
    print("Note:")
    print("  Les webhooks sont notifiés automatiquement quand la liste d'IPs")
    print("  est mise à jour (ajout/suppression d'IPs).")
    print("=" * 70)


def main():
    if len(sys.argv) < 2:
        show_help()
        sys.exit(1)
    
    command = sys.argv[1].lower()
    
    if command == 'list':
        success = list_webhooks()
    
    elif command == 'add':
        if len(sys.argv) < 3:
            print("❌ Erreur: URL manquante")
            print("Usage: python3 manage_webhooks.py add <url>")
            sys.exit(1)
        webhook_url = sys.argv[2]
        success = add_webhook(webhook_url)
    
    elif command == 'remove':
        if len(sys.argv) < 3:
            print("❌ Erreur: URL manquante")
            print("Usage: python3 manage_webhooks.py remove <url>")
            sys.exit(1)
        webhook_url = sys.argv[2]
        success = remove_webhook(webhook_url)
    
    elif command == 'test':
        if len(sys.argv) < 3:
            print("❌ Erreur: URL manquante")
            print("Usage: python3 manage_webhooks.py test <url>")
            sys.exit(1)
        webhook_url = sys.argv[2]
        success = test_webhook(webhook_url)
    
    elif command in ['help', '--help', '-h']:
        show_help()
        success = True
    
    else:
        print(f"❌ Commande inconnue: {command}")
        print()
        show_help()
        sys.exit(1)
    
    sys.exit(0 if success else 1)


if __name__ == '__main__':
    main()
