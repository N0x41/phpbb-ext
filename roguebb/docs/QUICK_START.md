# 🔐 Système de Sécurité par Signature Cryptographique

## ✅ Ce qui a été créé

### Fichiers de sécurité

1. **`generate_keys.py`** - Générateur de paires de clés RSA
   - Génère `private_key.pem` (clé privée - SECRÈTE)
   - Génère `public_key.pem` (clé publique - à distribuer)

2. **`server.py`** (modifié) - Serveur avec vérification de signature
   - Charge la clé publique au démarrage
   - Vérifie toutes les signatures sur `/api/submit_ip`
   - Rejette les requêtes sans signature valide

3. **`client_example.py`** - Client exemple autorisé
   - Montre comment signer une requête
   - Soumet des IPs avec signature valide

4. **`test_security.py`** - Tests de sécurité
   - Teste que les requêtes non signées sont rejetées
   - Teste que les signatures invalides sont rejetées

5. **`SECURITY.md`** - Documentation complète de sécurité

6. **`requirements.txt`** - Dépendances Python nécessaires

7. **`.gitignore`** - Protège contre le commit de clés privées

### Clés générées

- ✓ `private_key.pem` - 2048-bit RSA (gardée secrète)
- ✓ `public_key.pem` - Clé publique correspondante

## 🚀 Utilisation rapide

### 1. Installer les dépendances

```bash
cd /home/nox/Documents/roguebb
pip install -r requirements.txt
```

### 2. Les clés sont déjà générées

Les clés ont été créées avec `generate_keys.py` :
- `private_key.pem` - Clé privée du serveur
- `public_key.pem` - Clé publique pour les clients

### 3. Démarrer le serveur sécurisé

```bash
python3 server.py
```

Le serveur va :
- ✓ Charger la clé publique
- ✓ Vérifier toutes les signatures sur `/api/submit_ip`
- ✓ Rejeter les requêtes non autorisées

### 4. Soumettre une IP (client autorisé)

```bash
python3 client_example.py 192.168.1.100
```

### 5. Tester la sécurité

```bash
python3 test_security.py
```

Cela teste que le serveur rejette bien :
- Les requêtes sans signature
- Les requêtes avec signature invalide
- Les requêtes avec signature vide

## 🔒 Comment ça fonctionne

### Architecture

```
┌─────────────────────┐
│  Client Autorisé    │
│                     │
│  1. Signe l'IP avec │
│     clé PRIVÉE      │
│                     │
│  2. Envoie:         │
│     - IP            │
│     - Signature     │
└──────────┬──────────┘
           │
           │ POST /api/submit_ip
           │ {"ip": "...", "signature": "..."}
           │
           ▼
┌─────────────────────┐
│  Serveur Central    │
│                     │
│  3. Vérifie avec    │
│     clé PUBLIQUE    │
│                     │
│  4. Si valide ✓     │
│     → Accepte IP    │
│                     │
│  5. Si invalide ✗   │
│     → Rejette (403) │
└─────────────────────┘
```

### Format de la requête API

**Avant (non sécurisé):**
```json
{
  "ip": "192.168.1.100"
}
```

**Maintenant (sécurisé):**
```json
{
  "ip": "192.168.1.100",
  "signature": "iOKvXGz8jZF9M...base64...K9mNpQ=="
}
```

### Processus de signature (client)

```python
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import padding
import base64

# 1. Signer l'IP avec la clé privée
signature = private_key.sign(
    ip_address.encode('utf-8'),
    padding.PSS(
        mgf=padding.MGF1(hashes.SHA256()),
        salt_length=padding.PSS.MAX_LENGTH
    ),
    hashes.SHA256()
)

# 2. Encoder en base64 pour transmission
signature_b64 = base64.b64encode(signature).decode('utf-8')

# 3. Envoyer au serveur
requests.post(url, json={'ip': ip_address, 'signature': signature_b64})
```

### Processus de vérification (serveur)

```python
# 1. Décoder la signature
signature = base64.b64decode(signature_b64)

# 2. Vérifier avec la clé publique
try:
    public_key.verify(
        signature,
        ip_address.encode('utf-8'),
        padding.PSS(...),
        hashes.SHA256()
    )
    # Signature valide ✓
except InvalidSignature:
    # Signature invalide ✗ - Rejeter
```

## 🎯 Résultat

### ✅ Sécurité garantie

- ❌ Impossible d'envoyer une IP sans signature
- ❌ Impossible de falsifier une signature
- ❌ Impossible d'utiliser une signature d'une autre IP
- ✅ Seuls les possesseurs de la clé publique peuvent soumettre

### 📊 Codes de réponse HTTP

| Code | Signification |
|------|---------------|
| 200 | ✓ IP acceptée (signature valide) |
| 400 | ✗ Données manquantes |
| 401 | ✗ Signature manquante |
| 403 | ✗ Signature invalide (REJETÉ) |

## 🔐 Sécurité des clés

### ⚠️ Clé privée (`private_key.pem`)

- **NE JAMAIS** partager
- **NE JAMAIS** commit dans git
- Garder sur le serveur uniquement
- Protégée par `.gitignore`

### ✅ Clé publique (`public_key.pem`)

- Peut être distribuée librement
- Nécessaire pour signer les requêtes
- Utilisée côté serveur pour vérifier

## 📝 Prochaines étapes possibles

1. **Multi-clients** : Permettre plusieurs clés publiques autorisées
2. **Révocation** : Système pour révoquer des clés compromises
3. **Expiration** : Ajouter un timestamp aux signatures
4. **HTTPS** : Chiffrer la communication réseau
5. **Rate limiting** : Limiter les requêtes par clé

## 🆘 Dépannage

### "Fichier public_key.pem introuvable"
```bash
python3 generate_keys.py
```

### "Invalid signature"
- Vérifiez que vous utilisez la bonne clé privée
- Vérifiez que la signature est bien encodée en base64

### Le serveur accepte des requêtes non signées
- Redémarrez le serveur : `python3 server.py`
- Vérifiez que `public_key.pem` existe dans le répertoire

## 📦 Fichiers créés

```
roguebb/
├── server.py                  # Serveur modifié avec sécurité
├── generate_keys.py          # Générateur de clés ✓
├── client_example.py         # Client exemple ✓
├── test_security.py          # Tests de sécurité ✓
├── SECURITY.md               # Documentation détaillée ✓
├── QUICK_START.md            # Ce fichier ✓
├── requirements.txt          # Dépendances ✓
├── .gitignore                # Protection des clés ✓
├── private_key.pem           # Clé privée (SECRÈTE) ✓
└── public_key.pem            # Clé publique ✓
```

---

**🎉 Votre API est maintenant sécurisée avec la cryptographie RSA !**
