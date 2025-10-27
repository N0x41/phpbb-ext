#!/usr/bin/env python3
"""
Script pour récupérer et afficher la liste d'IPs depuis le serveur central.

Usage:
    python3 get_ip_list.py
    python3 get_ip_list.py --save ips_backup.txt
    python3 get_ip_list.py --stats
    python3 get_ip_list.py --count

Exemples:
    python3 get_ip_list.py                    # Affiche toutes les IPs
    python3 get_ip_list.py --count           # Affiche seulement le nombre
    python3 get_ip_list.py --stats           # Affiche les statistiques
    python3 get_ip_list.py --save backup.txt # Sauvegarde dans un fichier
"""

import sys
import json
import requests
from datetime import datetime

# Configuration
SERVER_URL = "http://localhost:5000"


def get_ip_list_from_server(server_url=SERVER_URL):
    """Récupère la liste d'IPs depuis le serveur."""
    try:
        response = requests.get(f"{server_url}/api/get_ips", timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            return data.get('ips', []), data.get('version', 0)
        else:
            print(f"❌ Erreur HTTP {response.status_code}")
            return None, None
            
    except requests.RequestException as e:
        print(f"❌ Erreur de connexion: {e}")
        return None, None


def get_version_from_server(server_url=SERVER_URL):
    """Récupère uniquement la version de la liste."""
    try:
        response = requests.get(f"{server_url}/api/get_version", timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            return data.get('version', 0)
        else:
            return None
            
    except requests.RequestException as e:
        print(f"❌ Erreur de connexion: {e}")
        return None


def display_stats(ips, version):
    """Affiche les statistiques de la liste."""
    print("="*60)
    print("📊 STATISTIQUES DU SERVEUR")
    print("="*60)
    print(f"Version de la liste : {version}")
    print(f"Nombre total d'IPs  : {len(ips)}")
    print(f"Date/Heure          : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    # Analyser les types d'IPs
    ipv4_count = sum(1 for ip in ips if '.' in ip)
    ipv6_count = sum(1 for ip in ips if ':' in ip)
    
    print(f"\nRépartition :")
    print(f"  - IPv4            : {ipv4_count}")
    print(f"  - IPv6            : {ipv6_count}")
    
    # Analyser les plages d'IPs (premiers octets pour IPv4)
    if ipv4_count > 0:
        networks = {}
        for ip in ips:
            if '.' in ip:
                network = '.'.join(ip.split('.')[:2]) + '.x.x'
                networks[network] = networks.get(network, 0) + 1
        
        print(f"\nTop 10 des réseaux IPv4 (/16):")
        sorted_networks = sorted(networks.items(), key=lambda x: x[1], reverse=True)
        for i, (network, count) in enumerate(sorted_networks[:10], 1):
            print(f"  {i}. {network:20s} : {count:5d} IPs")
    
    print("="*60)


def save_to_file(ips, filepath):
    """Sauvegarde la liste d'IPs dans un fichier."""
    try:
        with open(filepath, 'w') as f:
            f.write(f"# Liste d'IPs récupérée le {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"# Nombre total: {len(ips)}\n\n")
            for ip in sorted(ips):
                f.write(f"{ip}\n")
        print(f"✅ Liste sauvegardée dans: {filepath}")
        return True
    except Exception as e:
        print(f"❌ Erreur lors de la sauvegarde: {e}")
        return False


def main():
    print("="*60)
    print("🌐 Récupération de la liste d'IPs depuis le serveur")
    print("="*60)
    
    # Parser les arguments
    show_all = True
    show_stats = False
    show_count = False
    save_file = None
    
    if len(sys.argv) > 1:
        if '--stats' in sys.argv:
            show_stats = True
            show_all = False
        elif '--count' in sys.argv:
            show_count = True
            show_all = False
        elif '--save' in sys.argv:
            idx = sys.argv.index('--save')
            if idx + 1 < len(sys.argv):
                save_file = sys.argv[idx + 1]
            else:
                print("❌ Veuillez spécifier le nom du fichier après --save")
                sys.exit(1)
    
    # Récupérer la liste
    print(f"\n📡 Connexion au serveur: {SERVER_URL}...")
    ips, version = get_ip_list_from_server()
    
    if ips is None:
        print("❌ Impossible de récupérer la liste")
        sys.exit(1)
    
    print(f"✅ Liste récupérée (version {version}, {len(ips)} IPs)\n")
    
    # Afficher selon les options
    if show_count:
        print(f"Nombre total d'IPs: {len(ips)}")
    
    elif show_stats:
        display_stats(ips, version)
    
    elif show_all:
        print("📋 Liste des IPs:")
        print("-" * 60)
        for i, ip in enumerate(sorted(ips), 1):
            print(f"{i:6d}. {ip}")
        print("-" * 60)
        print(f"Total: {len(ips)} IPs")
    
    # Sauvegarder si demandé
    if save_file:
        print()
        save_to_file(ips, save_file)
    
    print("\n✅ Terminé")


if __name__ == '__main__':
    main()
