#!/usr/bin/env python3
"""
Client pour écrire des fichiers authentifiés sur les nœuds phpBB
Utilise la cryptographie RSA pour signer les requêtes

Usage:
    python write_file_to_node.py <node_url> <filename> <content>
    
Example:
    python write_file_to_node.py http://localhost:8080/forum test.json '{"test":true}'
"""

import json
import time
import base64
import sys
import os
from pathlib import Path

try:
    from cryptography.hazmat.primitives import hashes, serialization
    from cryptography.hazmat.primitives.asymmetric import padding
    import requests
except ImportError:
    print("Erreur: Dépendances manquantes")
    print("Installer avec: pip install cryptography requests")
    sys.exit(1)


def load_private_key(key_path='private_key.pem'):
    """Charge la clé privée RSA"""
    if not os.path.exists(key_path):
        print(f"Erreur: Clé privée introuvable: {key_path}")
        sys.exit(1)
    
    with open(key_path, 'rb') as f:
        private_key = serialization.load_pem_private_key(
            f.read(),
            password=None
        )
    return private_key


def create_signed_token(private_key, server_id='roguebb-main'):
    """Crée un token signé avec timestamp"""
    token_data = {
        'timestamp': int(time.time()),
        'server_id': server_id
    }
    token_json = json.dumps(token_data, separators=(',', ':'))
    
    # Signer le token
    signature = private_key.sign(
        token_json.encode('utf-8'),
        padding.PKCS1v15(),
        hashes.SHA256()
    )
    
    signature_b64 = base64.b64encode(signature).decode('utf-8')
    
    return token_json, signature_b64


def write_authenticated_file(node_url, filename, content, private_key_path='private_key.pem', server_id='roguebb-main'):
    """
    Écrit un fichier sur un nœud phpBB de manière authentifiée
    
    Args:
        node_url: URL du nœud phpBB (ex: http://forum.example.com)
        filename: Nom du fichier à créer
        content: Contenu du fichier (string)
        private_key_path: Chemin vers la clé privée RSA
        server_id: ID du serveur RogueBB
        
    Returns:
        dict: Réponse du serveur
    """
    # Charger la clé privée
    private_key = load_private_key(private_key_path)
    
    # Créer et signer le token
    token_json, signature_b64 = create_signed_token(private_key, server_id)
    
    # Préparer la requête
    endpoint = f'{node_url.rstrip("/")}/app.php/notify'
    payload = {
        'filename': filename,
        'content': content,
        'token': token_json,
        'signature': signature_b64
    }
    
    try:
        # Envoyer la requête
        response = requests.post(
            endpoint,
            json=payload,
            headers={'Content-Type': 'application/json'},
            timeout=10
        )
        
        # Vérifier la réponse
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
    if len(sys.argv) < 4:
        print("Usage: python write_file_to_node.py <node_url> <filename> <content>")
        print("")
        print("Arguments:")
        print("  node_url  : URL du nœud phpBB (ex: http://localhost:8080/forum)")
        print("  filename  : Nom du fichier à créer (ex: config.json)")
        print("  content   : Contenu du fichier (ex: '{\"test\":true}')")
        print("")
        print("Exemple:")
        print("  python write_file_to_node.py http://localhost:8080/forum test.json '{\"test\":true}'")
        sys.exit(1)
    
    node_url = sys.argv[1]
    filename = sys.argv[2]
    content = sys.argv[3]
    
    # Déterminer le chemin de la clé privée
    script_dir = Path(__file__).parent
    private_key_path = script_dir / '../server/private_key.pem'
    
    if not private_key_path.exists():
        private_key_path = Path('private_key.pem')
    
    if not private_key_path.exists():
        print(f"Erreur: Clé privée introuvable")
        print(f"Cherché dans: {private_key_path.absolute()}")
        sys.exit(1)
    
    print(f"📝 Écriture de fichier authentifiée")
    print(f"   URL:      {node_url}")
    print(f"   Fichier:  {filename}")
    print(f"   Taille:   {len(content)} octets")
    print(f"   Clé:      {private_key_path}")
    print("")
    
    # Écrire le fichier
    result = write_authenticated_file(
        node_url,
        filename,
        content,
        str(private_key_path)
    )
    
    # Afficher le résultat
    print(f"📡 Réponse (HTTP {result['http_status']}):")
    print(json.dumps(result, indent=2))
    
    # Code de sortie
    if result.get('status') == 'ok':
        print("")
        print("✓ Fichier créé avec succès !")
        sys.exit(0)
    else:
        print("")
        print("✗ Échec de création du fichier")
        sys.exit(1)


if __name__ == '__main__':
    main()
