#!/usr/bin/env python3
"""
Script pour soumettre plusieurs IPs au serveur central en masse.

Usage:
    python3 batch_submit_ips.py <ip1> <ip2> <ip3> ...
    python3 batch_submit_ips.py --file ips.txt
    echo "192.168.1.1\n10.0.0.1" | python3 batch_submit_ips.py --stdin

Exemples:
    python3 batch_submit_ips.py 192.168.1.100 10.20.30.40
    python3 batch_submit_ips.py --file suspicious_ips.txt
    cat ips.txt | python3 batch_submit_ips.py --stdin
"""

import sys
import json
import base64
import requests
import time
from pathlib import Path
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.backends import default_backend

# Configuration
SERVER_URL = "http://localhost:5000"
PRIVATE_KEY_PATH = "private_key.pem"
BATCH_SIZE = 10  # Nombre d'IPs à soumettre avant une pause
DELAY_BETWEEN_BATCHES = 1  # Secondes de pause entre les batches

class IPSubmitter:
    def __init__(self, server_url=SERVER_URL, private_key_path=PRIVATE_KEY_PATH):
        self.server_url = server_url
        self.private_key = self._load_private_key(private_key_path)
        self.stats = {
            'total': 0,
            'added': 0,
            'already_exists': 0,
            'failed': 0,
            'invalid': 0
        }
    
    def _load_private_key(self, key_path):
        """Charge la clé privée pour signer les requêtes."""
        try:
            with open(key_path, 'rb') as key_file:
                return serialization.load_pem_private_key(
                    key_file.read(),
                    password=None,
                    backend=default_backend()
                )
        except FileNotFoundError:
            print(f"❌ ERREUR: Fichier {key_path} introuvable!")
            sys.exit(1)
        except Exception as e:
            print(f"❌ ERREUR lors du chargement de la clé: {e}")
            sys.exit(1)
    
    def _sign_data(self, data):
        """Signe les données avec la clé privée."""
        if isinstance(data, str):
            data = data.encode('utf-8')
        
        signature = self.private_key.sign(
            data,
            padding.PSS(
                mgf=padding.MGF1(hashes.SHA256()),
                salt_length=padding.PSS.MAX_LENGTH
            ),
            hashes.SHA256()
        )
        
        return base64.b64encode(signature).decode('utf-8')
    
    def _validate_ip(self, ip_address):
        """Valide une adresse IP."""
        import ipaddress
        try:
            ipaddress.ip_address(ip_address.strip())
            return True
        except ValueError:
            return False
    
    def submit_single_ip(self, ip_address, verbose=True):
        """Soumet une seule IP au serveur."""
        ip_address = ip_address.strip()
        
        # Valider l'IP
        if not self._validate_ip(ip_address):
            if verbose:
                print(f"  ⚠️  {ip_address}: IP invalide")
            self.stats['invalid'] += 1
            return False
        
        # Signer l'IP
        try:
            signature = self._sign_data(ip_address)
        except Exception as e:
            if verbose:
                print(f"  ❌ {ip_address}: Erreur de signature - {e}")
            self.stats['failed'] += 1
            return False
        
        # Préparer la requête
        payload = {
            'ip': ip_address,
            'signature': signature
        }
        
        # Envoyer au serveur
        try:
            response = requests.post(
                f"{self.server_url}/api/submit_ip",
                json=payload,
                timeout=10
            )
            
            if response.status_code == 200:
                result = response.json()
                status = result.get('status', 'unknown')
                
                if status == 'added':
                    if verbose:
                        print(f"  ✅ {ip_address}: Ajoutée (version {result.get('new_version', '?')})")
                    self.stats['added'] += 1
                    return True
                elif status == 'already_exists':
                    if verbose:
                        print(f"  ℹ️  {ip_address}: Existe déjà")
                    self.stats['already_exists'] += 1
                    return True
            else:
                if verbose:
                    print(f"  ❌ {ip_address}: Erreur HTTP {response.status_code}")
                self.stats['failed'] += 1
                return False
                
        except requests.RequestException as e:
            if verbose:
                print(f"  ❌ {ip_address}: Erreur réseau - {e}")
            self.stats['failed'] += 1
            return False
    
    def submit_batch(self, ip_list, verbose=True):
        """Soumet un lot d'IPs."""
        if verbose:
            print(f"\n📤 Soumission de {len(ip_list)} IP(s)...\n")
        
        for i, ip in enumerate(ip_list, 1):
            self.stats['total'] += 1
            
            if verbose:
                print(f"[{i}/{len(ip_list)}]", end=" ")
            
            self.submit_single_ip(ip, verbose)
            
            # Pause entre les batches pour ne pas surcharger le serveur
            if i % BATCH_SIZE == 0 and i < len(ip_list):
                if verbose:
                    print(f"\n⏸️  Pause de {DELAY_BETWEEN_BATCHES}s...\n")
                time.sleep(DELAY_BETWEEN_BATCHES)
    
    def print_stats(self):
        """Affiche les statistiques finales."""
        print("\n" + "="*60)
        print("📊 STATISTIQUES")
        print("="*60)
        print(f"Total traité      : {self.stats['total']}")
        print(f"✅ Ajoutées       : {self.stats['added']}")
        print(f"ℹ️  Déjà existantes : {self.stats['already_exists']}")
        print(f"❌ Échecs         : {self.stats['failed']}")
        print(f"⚠️  Invalides      : {self.stats['invalid']}")
        
        success_rate = 0
        if self.stats['total'] > 0:
            success_rate = ((self.stats['added'] + self.stats['already_exists']) / 
                          self.stats['total'] * 100)
        print(f"\nTaux de succès    : {success_rate:.1f}%")
        print("="*60)


def read_ips_from_file(filepath):
    """Lit les IPs depuis un fichier (une par ligne)."""
    try:
        with open(filepath, 'r') as f:
            # Ignorer les lignes vides et les commentaires
            return [line.strip() for line in f 
                   if line.strip() and not line.strip().startswith('#')]
    except FileNotFoundError:
        print(f"❌ Fichier introuvable: {filepath}")
        sys.exit(1)
    except Exception as e:
        print(f"❌ Erreur lors de la lecture du fichier: {e}")
        sys.exit(1)


def read_ips_from_stdin():
    """Lit les IPs depuis stdin."""
    return [line.strip() for line in sys.stdin 
           if line.strip() and not line.strip().startswith('#')]


def main():
    print("="*60)
    print("🔐 Soumission en masse d'IPs au serveur central")
    print("="*60)
    
    # Parser les arguments
    if len(sys.argv) < 2:
        print("\n❌ Usage:")
        print("  python3 batch_submit_ips.py <ip1> <ip2> <ip3> ...")
        print("  python3 batch_submit_ips.py --file ips.txt")
        print("  cat ips.txt | python3 batch_submit_ips.py --stdin")
        sys.exit(1)
    
    # Créer le submitter
    submitter = IPSubmitter()
    
    # Déterminer la source des IPs
    ips_to_submit = []
    
    if sys.argv[1] == '--file':
        if len(sys.argv) < 3:
            print("❌ Veuillez spécifier le nom du fichier")
            sys.exit(1)
        filepath = sys.argv[2]
        print(f"📂 Lecture depuis le fichier: {filepath}")
        ips_to_submit = read_ips_from_file(filepath)
    
    elif sys.argv[1] == '--stdin':
        print("📥 Lecture depuis stdin...")
        ips_to_submit = read_ips_from_stdin()
    
    else:
        # IPs passées en arguments
        ips_to_submit = sys.argv[1:]
    
    if not ips_to_submit:
        print("❌ Aucune IP à soumettre")
        sys.exit(1)
    
    # Soumettre
    submitter.submit_batch(ips_to_submit)
    
    # Afficher les stats
    submitter.print_stats()
    
    # Code de sortie
    if submitter.stats['failed'] > 0:
        sys.exit(1)
    else:
        sys.exit(0)


if __name__ == '__main__':
    main()
