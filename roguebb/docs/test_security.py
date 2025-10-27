#!/usr/bin/env python3
"""
Script de test pour démontrer le rejet des requêtes non autorisées.
Ce script tente d'envoyer une IP SANS signature valide.
"""

import requests
import json

SERVER_URL = "http://localhost:5000"

def test_without_signature():
    """Tente d'envoyer une IP sans signature."""
    print("=" * 60)
    print("Test 1: Requête SANS signature")
    print("=" * 60)
    
    payload = {
        'ip': '10.0.0.1'
    }
    
    try:
        response = requests.post(
            f"{SERVER_URL}/api/submit_ip",
            json=payload,
            timeout=10
        )
        
        print(f"Statut: {response.status_code}")
        print(f"Réponse: {json.dumps(response.json(), indent=2)}")
        
        if response.status_code == 401:
            print("\n✓ ATTENDU: Le serveur a rejeté la requête (401 Unauthorized)")
        else:
            print("\n✗ PROBLÈME: Le serveur a accepté une requête non signée!")
            
    except requests.RequestException as e:
        print(f"Erreur: {e}")
    
    print()

def test_with_invalid_signature():
    """Tente d'envoyer une IP avec une signature invalide."""
    print("=" * 60)
    print("Test 2: Requête avec signature INVALIDE")
    print("=" * 60)
    
    payload = {
        'ip': '10.0.0.2',
        'signature': 'fake_signature_base64_encoded_string'
    }
    
    try:
        response = requests.post(
            f"{SERVER_URL}/api/submit_ip",
            json=payload,
            timeout=10
        )
        
        print(f"Statut: {response.status_code}")
        print(f"Réponse: {json.dumps(response.json(), indent=2)}")
        
        if response.status_code == 403:
            print("\n✓ ATTENDU: Le serveur a rejeté la signature invalide (403 Forbidden)")
        else:
            print("\n✗ PROBLÈME: Le serveur a accepté une signature invalide!")
            
    except requests.RequestException as e:
        print(f"Erreur: {e}")
    
    print()

def test_with_empty_signature():
    """Tente d'envoyer une IP avec une signature vide."""
    print("=" * 60)
    print("Test 3: Requête avec signature VIDE")
    print("=" * 60)
    
    payload = {
        'ip': '10.0.0.3',
        'signature': ''
    }
    
    try:
        response = requests.post(
            f"{SERVER_URL}/api/submit_ip",
            json=payload,
            timeout=10
        )
        
        print(f"Statut: {response.status_code}")
        print(f"Réponse: {json.dumps(response.json(), indent=2)}")
        
        if response.status_code == 403:
            print("\n✓ ATTENDU: Le serveur a rejeté la signature vide (403 Forbidden)")
        else:
            print("\n✗ PROBLÈME: Le serveur a accepté une signature vide!")
            
    except requests.RequestException as e:
        print(f"Erreur: {e}")
    
    print()

def main():
    print("\n" + "=" * 60)
    print("Tests de sécurité - Requêtes non autorisées")
    print("=" * 60)
    print("\nCe script teste que le serveur rejette correctement")
    print("les requêtes non autorisées (sans signature valide).")
    print("\n")
    
    test_without_signature()
    test_with_invalid_signature()
    test_with_empty_signature()
    
    print("=" * 60)
    print("Résumé: Tous les tests ci-dessus DOIVENT être rejetés")
    print("=" * 60)
    print("\nPour envoyer une requête VALIDE, utilisez:")
    print("  python3 client_example.py <ip_address>")
    print()

if __name__ == '__main__':
    main()
