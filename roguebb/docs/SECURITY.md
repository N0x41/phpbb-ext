# Système de Sécurité API - Signatures Cryptographiques

## Vue d'ensemble

Ce système utilise la cryptographie asymétrique (RSA) pour s'assurer que seuls les clients autorisés peuvent soumettre des IPs au serveur central.

## Architecture de Sécurité

### Principe de fonctionnement

1. **Chaque client autorisé** possède sa propre paire de clés RSA :
   - **Clé privée** (gardée secrète par le client)
   - **Clé publique** (partagée avec le serveur)

2. **Le serveur central** stocke les clés publiques de tous les clients autorisés

3. **Processus de soumission d'IP** :
   - Le client signe l'IP avec sa clé privée
   - Le client envoie l'IP + la signature au serveur
   - Le serveur vérifie la signature avec la clé publique du client
   - Si la signature est valide → IP acceptée ✓
   - Si la signature est invalide → IP rejetée ✗

## Fichiers générés

- `private_key.pem` - Clé privée (À GARDER SECRÈTE)
- `public_key.pem` - Clé publique (À distribuer aux clients autorisés)
- `generate_keys.py` - Script pour générer les clés
- `client_example.py` - Exemple de client qui signe ses requêtes

## Installation

### Prérequis

```bash
pip install cryptography flask requests
```

### Génération des clés

```bash
python3 generate_keys.py
```

Cela créera deux fichiers :
- `private_key.pem` - Gardez ce fichier secret sur le serveur
- `public_key.pem` - Distribuez ce fichier aux clients autorisés

## Utilisation

### Démarrage du serveur

```bash
python3 server.py
```

Le serveur :
- Charge la clé publique au démarrage
- Vérifie toutes les signatures des requêtes `/api/submit_ip`
- Rejette les requêtes sans signature valide

### Soumission d'IP avec le client exemple

```bash
python3 client_example.py 192.168.1.100
```

## Format de la requête API

### Endpoint: `POST /api/submit_ip`

**Avant (sans sécurité):**
```json
{
  "ip": "192.168.1.100"
}
```

**Maintenant (avec signature):**
```json
{
  "ip": "192.168.1.100",
  "signature": "base64_encoded_signature_here..."
}
```

### Codes de réponse

- `200` - IP acceptée (signature valide)
- `400` - Données manquantes dans la requête
- `401` - Signature manquante
- `403` - Signature invalide (non autorisé)

## Exemple de code client (Python)

```python
import base64
import requests
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.backends import default_backend

# Charger la clé privée du client
with open('private_key.pem', 'rb') as f:
    private_key = serialization.load_pem_private_key(
        f.read(), password=None, backend=default_backend()
    )

# IP à soumettre
ip_address = "192.168.1.100"

# Signer l'IP
signature = private_key.sign(
    ip_address.encode('utf-8'),
    padding.PSS(
        mgf=padding.MGF1(hashes.SHA256()),
        salt_length=padding.PSS.MAX_LENGTH
    ),
    hashes.SHA256()
)

# Encoder en base64
signature_b64 = base64.b64encode(signature).decode('utf-8')

# Envoyer au serveur
response = requests.post(
    'http://localhost:5000/api/submit_ip',
    json={
        'ip': ip_address,
        'signature': signature_b64
    }
)

print(response.json())
```

## Sécurité

### Points importants

1. **Clé privée** : Ne JAMAIS partager ou exposer la clé privée
2. **Clé publique** : Peut être distribuée librement aux clients autorisés
3. **Signature** : Unique pour chaque donnée, impossible à forger sans la clé privée

### Architecture multi-clients

Pour un système avec plusieurs clients autorisés :

1. **Chaque client génère sa propre paire de clés**
2. **Le serveur stocke toutes les clés publiques** (dans un fichier ou base de données)
3. **Le client envoie son identifiant** avec la signature
4. **Le serveur vérifie avec la bonne clé publique**

Exemple de structure pour multi-clients :
```
authorized_keys/
  ├── client1_public.pem
  ├── client2_public.pem
  └── client3_public.pem
```

## Dépannage

### "Fichier public_key.pem introuvable"
→ Exécutez `python3 generate_keys.py`

### "Invalid signature"
→ Vérifiez que le client utilise la bonne clé privée correspondant à la clé publique du serveur

### "Missing signature in JSON payload"
→ Le client doit envoyer le champ `signature` dans sa requête

## Endpoints API

| Endpoint | Méthode | Sécurité | Description |
|----------|---------|----------|-------------|
| `/` | GET | Aucune | Dashboard web |
| `/api/get_ips` | GET | Aucune | Récupérer la liste des IPs |
| `/api/submit_ip` | POST | **Signature requise** | Soumettre une IP |
| `/api/heartbeat` | POST | Aucune | Signal de présence |

## Améliorations futures possibles

- [ ] Système de révocation de clés
- [ ] Expiration des signatures (timestamp)
- [ ] Support de plusieurs clés publiques (multi-clients)
- [ ] Logging des tentatives d'accès non autorisé
- [ ] Rate limiting par clé publique
- [ ] HTTPS/TLS pour chiffrer les communications
