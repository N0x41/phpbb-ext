#!/usr/bin/env python3
"""
Script pour générer une paire de clés RSA publique/privée
pour l'authentification de l'API du serveur central.
"""

from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.backends import default_backend

def generate_keys():
    # Générer la clé privée
    private_key = rsa.generate_private_key(
        public_exponent=65537,
        key_size=2048,
        backend=default_backend()
    )
    
    # Obtenir la clé publique
    public_key = private_key.public_key()
    
    # Sérialiser la clé privée
    private_pem = private_key.private_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PrivateFormat.PKCS8,
        encryption_algorithm=serialization.NoEncryption()
    )
    
    # Sérialiser la clé publique
    public_pem = public_key.public_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PublicFormat.SubjectPublicKeyInfo
    )
    
    # Sauvegarder les clés
    with open('private_key.pem', 'wb') as f:
        f.write(private_pem)
    print("✓ Clé privée générée: private_key.pem")
    
    with open('public_key.pem', 'wb') as f:
        f.write(public_pem)
    print("✓ Clé publique générée: public_key.pem")
    
    print("\n⚠ IMPORTANT:")
    print("  - Gardez private_key.pem SECRÈTE sur le serveur central")
    print("  - Distribuez public_key.pem aux clients autorisés")

if __name__ == '__main__':
    generate_keys()
