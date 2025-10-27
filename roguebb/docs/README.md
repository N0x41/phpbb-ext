# 🔐 Serveur Central d'IPs avec Authentification Cryptographique

Serveur Flask distribué pour la gestion d'une liste d'adresses IP avec système de sécurité par signature RSA.

## 🌟 Fonctionnalités

- ✅ **Dashboard web** pour visualiser les statistiques
- ✅ **API RESTful** pour les clients
- ✅ **Mise à jour automatique** de la liste d'IPs depuis une source externe
- 🔐 **Authentification cryptographique** avec signatures RSA 2048-bit
- 🛡️ **Protection contre les requêtes non autorisées**
- 📊 **Suivi des contributions** de chaque nœud

## 🔒 Sécurité

Seuls les clients possédant la **clé publique** peuvent soumettre des IPs au serveur.

### Principe

1. Les clients signent leurs requêtes avec une **clé privée**
2. Le serveur vérifie avec la **clé publique**
3. Les requêtes invalides sont **rejetées** (HTTP 403)

Voir [SECURITY.md](SECURITY.md) pour les détails complets.

## 🚀 Démarrage rapide

### Installation

```bash
# Cloner le projet
cd /home/nox/Documents/roguebb

# Installer les dépendances
pip install -r requirements.txt
```

### Générer les clés (déjà fait)

```bash
python3 generate_keys.py
```

Cela crée :
- `private_key.pem` - Clé privée (SECRÈTE)
- `public_key.pem` - Clé publique (à distribuer)

### Démarrer le serveur

**Méthode simple (recommandée) :**
```bash
./roguebb.sh start
```

**Ou méthode manuelle :**
```bash
python3 server.py
```

Le serveur démarre sur `http://0.0.0.0:5000`

### Soumettre des IPs

**Méthode simple (recommandée) :**
```bash
# Une seule IP
./roguebb.sh submit 192.168.1.100

# Plusieurs IPs depuis un fichier
./roguebb.sh submit-file mes_ips.txt
```

**Ou méthodes alternatives :**

**Python - Une IP:**
```bash
python3 client_example.py 192.168.1.100
```

**Python - Plusieurs IPs:**
```bash
python3 batch_submit_ips.py 192.168.1.1 192.168.1.2 192.168.1.3
python3 batch_submit_ips.py --file ips.txt
cat ips.txt | python3 batch_submit_ips.py --stdin
```

**PHP:**
```bash
php client_example.php 192.168.1.100
```

## 📚 Documentation

- **[QUICK_START.md](QUICK_START.md)** - Guide de démarrage rapide
- **[SECURITY.md](SECURITY.md)** - Documentation de sécurité détaillée
- **[CLIENT_PHP.md](CLIENT_PHP.md)** - Guide du client PHP

## 🔌 API Endpoints

### Dashboard
- `GET /` - Interface web de visualisation

### API Publique (pas de signature requise)
- `GET /api/get_ips` - Récupérer la liste complète des IPs
- `POST /api/heartbeat` - Signal de présence

### API Sécurisée (signature requise)
- `POST /api/submit_ip` - Soumettre une nouvelle IP ⚠️ **Signature requise**

## 📦 Structure du projet

```
roguebb/
├── server.py              # Serveur Flask principal
├── generate_keys.py       # Générateur de clés RSA
├── client_example.py      # Exemple de client Python (une IP)
├── batch_submit_ips.py    # Client Python (soumission en masse) ⭐
├── get_ip_list.py         # Récupération et stats de la liste ⭐
├── roguebb.sh             # Script bash tout-en-un ⭐
├── client_example.php     # Exemple de client PHP
├── test_security.py       # Tests de sécurité
├── requirements.txt       # Dépendances Python
├── private_key.pem        # Clé privée (NE PAS PARTAGER)
├── public_key.pem         # Clé publique
├── example_ips.txt        # Fichier d'exemple d'IPs ⭐
├── SECURITY.md            # Documentation de sécurité
├── QUICK_START.md         # Guide rapide
├── GUIDE_UTILISATION.md   # Guide complet en français ⭐
├── QUICKREF.md            # Référence rapide ⭐
├── RESUME.md              # Résumé des fonctionnalités ⭐
├── CLIENT_PHP.md          # Guide du client PHP
└── .gitignore             # Protection des clés
```

⭐ = Nouveaux fichiers ajoutés

## 🧪 Tests

### Tester la sécurité

```bash
python3 test_security.py
```

Vérifie que le serveur rejette :
- ❌ Requêtes sans signature
- ❌ Requêtes avec signature invalide
- ❌ Requêtes avec signature vide

### Test avec client autorisé

```bash
# Soumettre une IP
python3 client_example.py 10.20.30.40

# Vérifier dans le dashboard
curl http://localhost:5000/
```

## 🔐 Exemple de code client

```python
import base64
import requests
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.backends import default_backend

# Charger la clé privée
with open('private_key.pem', 'rb') as f:
    private_key = serialization.load_pem_private_key(
        f.read(), password=None, backend=default_backend()
    )

# IP à soumettre
ip = "192.168.1.100"

# Signer
signature = private_key.sign(
    ip.encode('utf-8'),
    padding.PSS(
        mgf=padding.MGF1(hashes.SHA256()),
        salt_length=padding.PSS.MAX_LENGTH
    ),
    hashes.SHA256()
)

# Envoyer
response = requests.post(
    'http://localhost:5000/api/submit_ip',
    json={
        'ip': ip,
        'signature': base64.b64encode(signature).decode('utf-8')
    }
)

print(response.json())
```

## ⚙️ Configuration

Dans `server.py` :

```python
IP_SOURCE_URL = "https://..."  # URL source des IPs
UPDATE_INTERVAL_SECONDS = 3600  # Intervalle de mise à jour (1h)
PUBLIC_KEY_PATH = "public_key.pem"  # Chemin de la clé publique
```

## 🛡️ Sécurité des clés

### ⚠️ Clé privée (`private_key.pem`)
- **NE JAMAIS** commit dans git
- **NE JAMAIS** partager
- Gardée **uniquement** sur le serveur

### ✅ Clé publique (`public_key.pem`)
- Peut être distribuée aux clients autorisés
- Nécessaire pour signer les requêtes
- Stockée sur le serveur pour vérification

## 📊 Réponses HTTP

| Code | Signification |
|------|---------------|
| 200 | ✓ Succès - IP acceptée |
| 400 | ✗ Requête mal formée |
| 401 | ✗ Signature manquante |
| 403 | ✗ Signature invalide (non autorisé) |

## 🔧 Dépendances

- `flask` - Serveur web
- `requests` - Client HTTP
- `cryptography` - Cryptographie RSA

Installation :
```bash
pip install -r requirements.txt
```

## 🚦 État du projet

- ✅ Génération de clés RSA
- ✅ Serveur avec vérification de signatures
- ✅ Client exemple fonctionnel
- ✅ Tests de sécurité
- ✅ Documentation complète
- ✅ Protection `.gitignore`

## 📝 TODO / Améliorations futures

- [ ] Support multi-clés (plusieurs clients autorisés)
- [ ] Système de révocation de clés
- [ ] Expiration des signatures (timestamp)
- [ ] Rate limiting par clé
- [ ] HTTPS/TLS
- [ ] Stockage persistant (base de données)
- [ ] Logging avancé des tentatives d'accès

## 📄 Licence

Ce projet est un exemple éducatif de système d'authentification par cryptographie asymétrique.

## 🤝 Contribution

Pour ajouter un nouveau client autorisé :
1. Le client génère sa propre paire de clés
2. Le client vous envoie sa clé **publique** uniquement
3. Vous ajoutez cette clé au serveur
4. Le client peut maintenant soumettre des IPs

---

**Fait avec ❤️ et 🔐 cryptographie RSA**
