# 🆚 Comparaison des Clients Python vs PHP

## Vue d'ensemble

Deux clients sont disponibles pour soumettre des IPs au serveur :
- **Python** (`client_example.py`)
- **PHP** (`client_example.php`)

Les deux utilisent la même clé privée et sont **entièrement compatibles** avec le serveur.

## 🔄 Différences techniques

| Aspect | Python | PHP |
|--------|--------|-----|
| **Algorithme** | RSA-PSS | RSA PKCS#1 v1.5 |
| **Hash** | SHA-256 | SHA-256 |
| **Taille clé** | 2048-bit | 2048-bit |
| **Bibliothèque** | cryptography | OpenSSL (natif) |
| **Installation** | `pip install cryptography` | Extension native |
| **HTTP Client** | requests | file_get_contents + stream |

## 🎯 Compatibilité

✅ **Les deux clients fonctionnent avec le même serveur !**

Le serveur accepte les deux types de signatures :
- PSS (Python)
- PKCS#1 v1.5 (PHP)

## 📊 Performance

### Python
```bash
$ time python3 client_example.py 1.2.3.4
# ~0.15s (dépend du réseau)
```

### PHP
```bash
$ time php client_example.php 1.2.3.4
# ~0.12s (dépend du réseau)
```

⚡ Performances similaires, légèrement plus rapide en PHP car OpenSSL est natif.

## 💻 Exemples d'utilisation

### Python

```bash
# Simple
python3 client_example.py 192.168.1.100

# Dans un script
python3 client_example.py $(hostname -I | awk '{print $1}')

# Multiple IPs
for ip in 10.0.0.1 10.0.0.2 10.0.0.3; do
    python3 client_example.py $ip
done
```

### PHP

```bash
# Simple
php client_example.php 192.168.1.100

# Dans un script
php client_example.php $(hostname -I | awk '{print $1}')

# Multiple IPs
for ip in 10.0.0.1 10.0.0.2 10.0.0.3; do
    php client_example.php $ip
done
```

## 🔐 Code de signature

### Python (PSS)

```python
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import padding

signature = private_key.sign(
    ip_address.encode('utf-8'),
    padding.PSS(
        mgf=padding.MGF1(hashes.SHA256()),
        salt_length=padding.PSS.MAX_LENGTH
    ),
    hashes.SHA256()
)
```

### PHP (PKCS#1 v1.5)

```php
openssl_sign(
    $ipAddress,
    $signature,
    $privateKey,
    OPENSSL_ALGO_SHA256
);
```

## 🚀 Intégration dans vos projets

### Projet Python existant

```python
# Dans votre script Python
import subprocess

def submit_ip(ip_address):
    result = subprocess.run(
        ['python3', 'client_example.py', ip_address],
        capture_output=True,
        text=True
    )
    return result.returncode == 0
```

### Projet PHP existant

```php
<?php
// Dans votre application PHP
function submitIP($ipAddress) {
    $output = [];
    $returnCode = 0;
    
    exec(
        "php client_example.php " . escapeshellarg($ipAddress),
        $output,
        $returnCode
    );
    
    return $returnCode === 0;
}
?>
```

## 🛠️ Quand utiliser quel client ?

### Utilisez le client Python si :
- ✅ Vous avez déjà Python installé
- ✅ Vous préférez pip pour les dépendances
- ✅ Vous développez en Python
- ✅ Vous voulez PSS (plus moderne)

### Utilisez le client PHP si :
- ✅ Vous avez déjà PHP installé
- ✅ Vous ne voulez pas installer de dépendances supplémentaires
- ✅ Vous développez en PHP
- ✅ Votre serveur web utilise déjà PHP
- ✅ Vous voulez PKCS#1 v1.5 (plus standard)

## 📦 Dépendances

### Python
```bash
pip install cryptography requests
```

### PHP
```bash
# Généralement déjà installé
php -m | grep openssl
```

Si OpenSSL manque :
```bash
# Ubuntu/Debian
sudo apt-get install php-openssl

# CentOS/RHEL
sudo yum install php-openssl
```

## 🔍 Débogage

### Python

```python
# Mode verbose
import logging
logging.basicConfig(level=logging.DEBUG)
```

### PHP

```php
// Mode verbose
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🎭 Exemple : Script shell universel

```bash
#!/bin/bash
# submit_ip.sh - Détecte automatiquement Python ou PHP

IP=$1

if command -v python3 &> /dev/null; then
    python3 client_example.py "$IP"
elif command -v php &> /dev/null; then
    php client_example.php "$IP"
else
    echo "Erreur: Python3 ou PHP requis"
    exit 1
fi
```

## 📈 Tests comparatifs

### Test de signature

**Python:**
```bash
$ python3 -c "from cryptography.hazmat.primitives import hashes; print('PSS OK')"
PSS OK
```

**PHP:**
```bash
$ php -r "if (function_exists('openssl_sign')) echo 'PKCS1 OK';"
PKCS1 OK
```

### Test de connexion

**Python:**
```bash
$ python3 -c "import requests; print(requests.get('http://localhost:5000').status_code)"
200
```

**PHP:**
```bash
$ php -r "echo @file_get_contents('http://localhost:5000') ? '200' : 'Error';"
200
```

## ✅ Recommandations

### Pour la production

1. **Choisissez UN client** et restez cohérent
2. **Documentez** lequel vous utilisez
3. **Testez** avant déploiement
4. **Gardez** la clé privée sécurisée

### Pour le développement

1. **Testez les deux** pour comprendre les différences
2. **Comparez** les performances dans votre environnement
3. **Vérifiez** la compatibilité avec vos outils existants

## 🔗 Liens utiles

- **Client Python**: `client_example.py`
- **Client PHP**: `client_example.php`
- **Guide PHP**: `CLIENT_PHP.md`
- **Sécurité**: `SECURITY.md`
- **Serveur**: `server.py`

---

**🎉 Les deux clients sont valides et sécurisés !**

Choisissez celui qui correspond le mieux à votre environnement.
