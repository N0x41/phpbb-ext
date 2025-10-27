#!/usr/bin/env python3
"""
Script client exemple pour soumettre des IPs au serveur central
en utilisant la signature cryptographique avec la clé publique.

Usage:
    python3 client_example.py <ip_to_submit>
    
Exemple:
    python3 client_example.py 192.168.1.100
"""

import sys
import json
import base64
import requests
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.backends import default_backend

# Configuration
SERVER_URL = "http://localhost:5000"
PUBLIC_KEY_PATH = "public_key.pem"

def load_public_key():
    """Charge la clé publique pour signer les requêtes."""
    try:
        with open(PUBLIC_KEY_PATH, 'rb') as key_file:
            public_key = serialization.load_pem_public_key(
                key_file.read(),
                backend=default_backend()
            )
        return public_key
    except FileNotFoundError:
        print(f"ERREUR: Fichier {PUBLIC_KEY_PATH} introuvable!")
        print("Assurez-vous d'avoir la clé publique dans le répertoire.")
        sys.exit(1)
    except Exception as e:
        print(f"ERREUR lors du chargement de la clé: {e}")
        sys.exit(1)

def sign_data(private_key, data):
    """
    Signe les données avec la clé privée.
    
    Note: Dans un cas réel, le client utiliserait la clé PUBLIQUE
    et le serveur vérifierait avec la clé PUBLIQUE.
    Pour signer, le client a besoin de sa propre PAIRE de clés.
    """
    if isinstance(data, str):
        data = data.encode('utf-8')
    
    signature = private_key.sign(
        data,
        padding.PSS(
            mgf=padding.MGF1(hashes.SHA256()),
            salt_length=padding.PSS.MAX_LENGTH
        ),
        hashes.SHA256()
    )
    
    # Encoder en base64 pour transmission
    return base64.b64encode(signature).decode('utf-8')

def submit_ip(ip_address, private_key):
    """Soumet une IP au serveur avec signature."""
    # Signer l'IP
    signature = sign_data(private_key, ip_address)
    
    # Préparer la requête
    payload = {
        'ip': ip_address,
        'signature': signature
    }
    
    # Envoyer au serveur
    try:
        response = requests.post(
            f"{SERVER_URL}/api/submit_ip",
            json=payload,
            timeout=10
        )
        
        print(f"Statut: {response.status_code}")
        print(f"Réponse: {json.dumps(response.json(), indent=2)}")
        
        return response.status_code == 200
    except requests.RequestException as e:
        print(f"ERREUR lors de l'envoi: {e}")
        return False

def main():
    if len(sys.argv) != 2:
        print("Usage: python3 client_example.py <ip_address>")
        print("Exemple: python3 client_example.py 192.168.1.100")
        sys.exit(1)
    
    ip_to_submit = sys.argv[1]
    
    print("=" * 60)
    print("Client de soumission d'IP avec signature cryptographique")
    print("=" * 60)
    print()
    
    # Note importante pour l'architecture
    print("⚠ ARCHITECTURE:")
    print("Dans ce système, chaque client autorisé doit avoir:")
    print("  1. Sa propre paire de clés (privée + publique)")
    print("  2. Le serveur stocke toutes les clés PUBLIQUES des clients autorisés")
    print("  3. Le client signe avec sa clé PRIVÉE")
    print("  4. Le serveur vérifie avec la clé PUBLIQUE du client")
    print()
    print("Pour cet exemple, on utilise la même paire de clés.")
    print()
    
    # Charger la clé privée (dans un vrai système, chaque client a sa propre clé)
    try:
        with open('private_key.pem', 'rb') as key_file:
            private_key = serialization.load_pem_private_key(
                key_file.read(),
                password=None,
                backend=default_backend()
            )
        print(f"✓ Clé privée chargée")
    except FileNotFoundError:
        print("ERREUR: private_key.pem introuvable!")
        print("Exécutez generate_keys.py d'abord.")
        sys.exit(1)
    
    print(f"📤 Soumission de l'IP: {ip_to_submit}")
    print()
    
    success = submit_ip(ip_to_submit, private_key)
    
    if success:
        print()
        print("✓ IP soumise avec succès!")
    else:
        print()
        print("✗ Échec de la soumission")
        sys.exit(1)

if __name__ == '__main__':
    main()
