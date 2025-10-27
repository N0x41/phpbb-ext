# Système d'authentification cryptographique RSA

[← Retour au README principal](../README.md)

## 📋 Vue d'ensemble

Le système d'authentification permet au serveur RogueBB de créer des fichiers de manière sécurisée sur les nœuds phpBB en utilisant la cryptographie asymétrique RSA avec signature numérique.

## 🔐 Architecture de sécurité

### Composants

```
┌─────────────────────────────────────────────────────────┐
│  Serveur RogueBB (Possède la clé privée)                │
│  ┌──────────────────────────────────────────────────┐   │
│  │  1. Génère un token avec timestamp                │   │
│  │  2. Signe le token avec la clé privée RSA         │   │
│  │  3. Envoie token + signature + données au nœud    │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼ HTTPS POST
┌─────────────────────────────────────────────────────────┐
│  Nœud phpBB (Possède la clé publique)                   │
│  ┌──────────────────────────────────────────────────┐   │
│  │  1. Reçoit token + signature + données            │   │
│  │  2. Vérifie la signature avec la clé publique     │   │
│  │  3. Vérifie le timestamp (anti-replay)            │   │
│  │  4. Vérifie le serveur_id (optionnel)             │   │
│  │  5. Crée le fichier SI tout est valide            │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### Avantages de cette approche

✅ **Asymétrique** : Seul le serveur RogueBB peut signer (clé privée)  
✅ **Vérifiable** : N'importe quel nœud peut vérifier (clé publique)  
✅ **Anti-replay** : Les tokens ont un timestamp limité (5 minutes par défaut)  
✅ **Robuste** : Basé sur RSA-2048 + SHA256  
✅ **Traçable** : Tous les événements sont loggés  

## 🔑 Génération des clés

### Clés déjà existantes

Le projet inclut déjà une paire de clés RSA :

```
roguebb/server/
├── private_key.pem  → Clé privée (GARDEZ SECRÈTE !)
└── public_key.pem   → Clé publique (distribuez-la)
```

### Régénérer les clés (si nécessaire)

```bash
cd roguebb/server
python3 generate_keys.py
```

Cela génère :
- **private_key.pem** : Clé privée RSA 2048 bits (à protéger !)
- **public_key.pem** : Clé publique RSA (à distribuer aux nœuds)

### Distribution de la clé publique

La clé publique est automatiquement copiée dans l'extension :

```bash
cp roguebb/server/public_key.pem activitycontrol/data/roguebb_public_key.pem
```

## 📡 API: Écriture authentifiée

### Endpoint

```
POST /app.php/ac_authenticated_write
```

### Format de requête

```json
{
  "filename": "server_config.json",
  "content": "{\"server\":\"roguebb-main\",\"version\":\"1.0.0\"}",
  "token": "{\"timestamp\":1698765432,\"server_id\":\"roguebb-main\"}",
  "signature": "base64_encoded_signature_here..."
}
```

### Champs requis

| Champ | Type | Description |
|-------|------|-------------|
| `filename` | string | Nom du fichier (alphanum + `-_.` uniquement) |
| `content` | string | Contenu du fichier (peut être JSON, texte, etc.) |
| `token` | string | Token JSON avec `timestamp` et `server_id` |
| `signature` | string | Signature RSA du token encodée en Base64 |

### Réponses

#### Succès (200 OK)

```json
{
  "status": "ok",
  "message": "File created successfully",
  "filename": "server_config.json",
  "size": 45,
  "hash": "sha256_hash_of_file",
  "timestamp": 1698765432
}
```

#### Erreurs

**403 Forbidden** - Authentification échouée
```json
{
  "status": "error",
  "message": "Authentication failed or file creation error",
  "filename": "server_config.json"
}
```

**400 Bad Request** - Données invalides
```json
{
  "status": "error",
  "message": "Missing required field: signature"
}
```

**405 Method Not Allowed** - Méthode HTTP invalide
```json
{
  "status": "error",
  "message": "Only POST requests are allowed"
}
```

## 🐍 Exemple Python (Serveur RogueBB)

### Code complet d'écriture authentifiée

```python
import json
import time
import base64
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding
import requests

# Charger la clé privée
with open('private_key.pem', 'rb') as f:
    private_key = serialization.load_pem_private_key(f.read(), password=None)

# Créer le token
token_data = {
    'timestamp': int(time.time()),
    'server_id': 'roguebb-main'
}
token_json = json.dumps(token_data, separators=(',', ':'))

# Signer le token
signature = private_key.sign(
    token_json.encode('utf-8'),
    padding.PKCS1v15(),
    hashes.SHA256()
)
signature_b64 = base64.b64encode(signature).decode('utf-8')

# Préparer les données du fichier
file_content = json.dumps({
    'server': 'roguebb-main',
    'version': '1.0.0',
    'last_update': int(time.time())
})

# Envoyer la requête
response = requests.post(
    'http://forum.example.com/app.php/ac_authenticated_write',
    json={
        'filename': 'server_config.json',
        'content': file_content,
        'token': token_json,
        'signature': signature_b64
    },
    headers={'Content-Type': 'application/json'}
)

print(response.json())
```

### Fonction réutilisable

```python
def write_authenticated_file(node_url, filename, content, private_key_path='private_key.pem'):
    """
    Écrit un fichier sur un nœud phpBB de manière authentifiée
    
    Args:
        node_url: URL du nœud phpBB (ex: http://forum.example.com)
        filename: Nom du fichier à créer
        content: Contenu du fichier (string)
        private_key_path: Chemin vers la clé privée RSA
        
    Returns:
        dict: Réponse du serveur
    """
    # Charger la clé privée
    with open(private_key_path, 'rb') as f:
        private_key = serialization.load_pem_private_key(f.read(), password=None)
    
    # Créer et signer le token
    token_data = {
        'timestamp': int(time.time()),
        'server_id': 'roguebb-main'
    }
    token_json = json.dumps(token_data, separators=(',', ':'))
    
    signature = private_key.sign(
        token_json.encode('utf-8'),
        padding.PKCS1v15(),
        hashes.SHA256()
    )
    signature_b64 = base64.b64encode(signature).decode('utf-8')
    
    # Envoyer la requête
    response = requests.post(
        f'{node_url}/app.php/ac_authenticated_write',
        json={
            'filename': filename,
            'content': content,
            'token': token_json,
            'signature': signature_b64
        },
        headers={'Content-Type': 'application/json'},
        timeout=10
    )
    
    return response.json()

# Utilisation
result = write_authenticated_file(
    'http://forum.example.com',
    'sync_status.json',
    json.dumps({'synced': True, 'timestamp': int(time.time())})
)
print(result)
```

## 🔒 Sécurité

### Protection de la clé privée

```bash
# Permissions strictes
chmod 600 roguebb/server/private_key.pem

# Ne jamais committer la clé privée
echo "roguebb/server/private_key.pem" >> .gitignore
```

### Validations côté phpBB

Le service `server_authenticator` vérifie :

1. **Signature valide** : La signature correspond-elle au token ?
2. **Timestamp récent** : Le token a-t-il moins de 5 minutes ?
3. **Timestamp cohérent** : Le timestamp n'est pas dans le futur
4. **Server ID** : L'ID du serveur correspond (optionnel)
5. **Nom de fichier sûr** : Pas de `../`, uniquement alphanum + `-_.`
6. **Extension autorisée** : `.json`, `.txt`, `.log`, `.dat` uniquement

### Logs de sécurité

Tous les événements sont loggés dans phpBB :

```php
// Logs critiques
'AC_AUTH_SIGNATURE_MISMATCH'    // Signature invalide
'AC_AUTH_TOKEN_EXPIRED'         // Token trop vieux
'AC_AUTH_TOKEN_FUTURE'          // Token du futur
'AC_AUTH_SERVER_ID_MISMATCH'    // ID serveur incorrect
'AC_AUTH_UNSAFE_FILENAME'       // Nom de fichier dangereux
'AC_AUTH_FILE_EXISTS'           // Fichier existe déjà

// Logs admin
'AC_AUTH_FILE_CREATED'          // Fichier créé avec succès
'AC_AUTH_KEY_INSTALLED'         // Nouvelle clé publique installée
'AC_AUTH_KEY_REVOKED'           // Clé publique révoquée
```

## 🧪 Tests

### Test manuel avec curl

```bash
# Vous aurez besoin du token et signature générés en Python
curl -X POST http://localhost:8080/forum/app.php/ac_authenticated_write \
  -H "Content-Type: application/json" \
  -d '{
    "filename": "test.json",
    "content": "{\"test\":true}",
    "token": "{\"timestamp\":1698765432,\"server_id\":\"roguebb-main\"}",
    "signature": "YOUR_BASE64_SIGNATURE_HERE"
  }'
```

### Script de test complet

```python
#!/usr/bin/env python3
"""
Test du système d'authentification RSA
"""
import sys
sys.path.append('roguebb/server')

from write_authenticated_file import write_authenticated_file

# Test 1: Fichier simple
print("Test 1: Création d'un fichier simple")
result = write_authenticated_file(
    'http://localhost:8080/forum',
    'test_simple.json',
    '{"test": "hello world"}'
)
print(f"  Résultat: {result}\n")

# Test 2: Fichier avec données complexes
print("Test 2: Fichier avec données complexes")
import json
import time
complex_data = json.dumps({
    'server_info': {
        'name': 'roguebb-main',
        'version': '1.0.0',
        'nodes_count': 5
    },
    'stats': {
        'total_ips': 1234,
        'active_bans': 567
    },
    'timestamp': int(time.time())
})
result = write_authenticated_file(
    'http://localhost:8080/forum',
    'server_status.json',
    complex_data
)
print(f"  Résultat: {result}\n")

# Test 3: Token expiré (devrait échouer)
print("Test 3: Token expiré (devrait échouer)")
# Modifier manuellement le timestamp dans le code pour tester

print("✓ Tests terminés")
```

## 📚 Référence PHP

### Service `server_authenticator`

#### Méthodes publiques

```php
// Vérifier une signature
bool verify_signature(string $data, string $signature)

// Vérifier un token avec timestamp
bool verify_token(string $token, string $signature, int $max_age = 300)

// Créer un fichier authentifié
bool create_authenticated_file(string $filename, string $content, string $token, string $signature)

// Obtenir le hash d'un fichier
string|false get_file_hash(string $filename)

// Révoquer la clé publique actuelle
bool revoke_public_key()

// Installer une nouvelle clé publique
bool install_public_key(string $public_key_content)
```

### Configuration

```php
// Autoriser l'écrasement de fichiers existants
$config->set('ac_allow_file_overwrite', 1);

// Définir l'ID du serveur attendu
$config->set('ac_roguebb_server_id', 'roguebb-main');
```

## 🚀 Cas d'usage

### 1. Synchronisation de configuration

Le serveur RogueBB peut pousser sa configuration vers tous les nœuds :

```python
config = json.dumps({
    'sync_interval': 3600,
    'webhook_enabled': True,
    'reporting_enabled': True
})

for node in nodes:
    write_authenticated_file(node['url'], 'roguebb_config.json', config)
```

### 2. Mise à jour de liste d'IPs

```python
ips_data = json.dumps({
    'version': 123,
    'ips': ['1.2.3.4', '5.6.7.8'],
    'updated_at': time.time()
})

for node in nodes:
    write_authenticated_file(node['url'], 'ip_list_v123.json', ips_data)
```

### 3. Déploiement de règles

```python
rules = json.dumps({
    'min_posts_for_links': 10,
    'quarantine_posts': True,
    'auto_ban_threshold': 5
})

for node in nodes:
    write_authenticated_file(node['url'], 'security_rules.json', rules)
```

## 🔄 Révocation et rotation des clés

### Révoquer l'ancienne clé

```php
// Via l'ACP ou script
$authenticator->revoke_public_key();
// Renomme la clé en roguebb_public_key.pem.revoked.1698765432
```

### Installer une nouvelle clé

```php
$new_public_key = file_get_contents('new_public_key.pem');
$authenticator->install_public_key($new_public_key);
```

### Workflow complet de rotation

1. **Générer nouvelle paire** :
   ```bash
   cd roguebb/server
   python3 generate_keys.py
   mv private_key.pem private_key_new.pem
   mv public_key.pem public_key_new.pem
   ```

2. **Distribuer la nouvelle clé publique** aux nœuds

3. **Basculer** sur la nouvelle clé privée pour signer

4. **Révoquer** les anciennes clés publiques sur les nœuds

## 🎯 Avantages vs alternatives

### vs Clés symétriques (HMAC)
- ✅ Pas besoin de secret partagé sur les nœuds
- ✅ La clé publique peut être commitée dans Git
- ✅ Révocation facile sans changer tous les nœuds

### vs API Key simple
- ✅ Impossible de forger un token sans la clé privée
- ✅ Protection anti-replay avec timestamp
- ✅ Signature cryptographiquement prouvable

### vs TLS Client Certificates
- ✅ Plus simple à implémenter
- ✅ Pas besoin de configuration serveur web complexe
- ✅ Fonctionne avec n'importe quel hébergement PHP

---

[← Retour au README principal](../README.md) | [📚 Documentation RogueBB](roguebb/docs/INDEX.md)

**Dernière mise à jour** : 27 octobre 2025
