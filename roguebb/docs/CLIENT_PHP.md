# Client PHP pour l'API de soumission d'IP

## 📋 Description

Client PHP qui soumet des adresses IP au serveur central avec authentification par signature RSA.

## ✅ Prérequis

- PHP 7.0 ou supérieur
- Extension OpenSSL activée (généralement incluse par défaut)

### Vérifier l'installation PHP

```bash
php --version
php -m | grep openssl
```

Si OpenSSL n'est pas disponible, installez-le :

**Ubuntu/Debian:**
```bash
sudo apt-get install php-openssl
```

**CentOS/RHEL:**
```bash
sudo yum install php-openssl
```

## 🚀 Utilisation

### Syntaxe

```bash
php client_example.php <ip_address>
```

### Exemples

```bash
# Soumettre une IP
php client_example.php 192.168.1.100

# Soumettre une autre IP
php client_example.php 10.20.30.40
```

## 📝 Format de sortie

```
============================================================
Client PHP de soumission d'IP avec signature RSA
============================================================

ℹ️  ARCHITECTURE:
   - Le client signe l'IP avec sa clé PRIVÉE
   - Le serveur vérifie avec la clé PUBLIQUE
   - Seuls les clients autorisés peuvent soumettre des IPs

✓ Clé privée chargée depuis private_key.pem
📤 Soumission de l'IP: 192.168.1.100

Statut HTTP: 200
Réponse:
{
    "status": "added",
    "submitted_ip": "192.168.1.100",
    "total_ips": 18170
}

✓ IP soumise avec succès!
```

## 🔧 Configuration

Dans le fichier `client_example.php` :

```php
define('SERVER_URL', 'http://localhost:5000');
define('PRIVATE_KEY_PATH', 'private_key.pem');
```

## 🔐 Sécurité

### Algorithme de signature

Le client PHP utilise **RSA avec SHA-256** (PKCS#1 v1.5).

Le serveur accepte deux types de signatures :
- **PSS** (utilisé par le client Python)
- **PKCS#1 v1.5** (utilisé par le client PHP)

### Clé privée requise

Le client a besoin de `private_key.pem` dans le même répertoire.

```bash
# Vérifier que la clé existe
ls -l private_key.pem
```

Si la clé n'existe pas, générez-la :

```bash
python3 generate_keys.py
```

## 📊 Codes de réponse

| Code | Signification |
|------|---------------|
| 200 | ✓ IP acceptée (signature valide) |
| 400 | ✗ Requête mal formée |
| 401 | ✗ Signature manquante |
| 403 | ✗ Signature invalide |

## 🔍 Dépannage

### "Fichier private_key.pem introuvable"

```bash
python3 generate_keys.py
```

### "Call to undefined function openssl_sign"

L'extension OpenSSL n'est pas activée. Installez-la :

```bash
sudo apt-get install php-openssl
```

### "ERREUR lors de l'envoi: Impossible de contacter le serveur"

Vérifiez que le serveur tourne :

```bash
# Démarrer le serveur
python3 server.py

# Vérifier qu'il répond
curl http://localhost:5000/api/heartbeat -X POST
```

### "Statut HTTP: 403" (Signature invalide)

- Vérifiez que vous utilisez la bonne clé privée
- Vérifiez que le serveur a la clé publique correspondante

## 💻 Intégration dans votre code PHP

```php
<?php
require_once 'path/to/client_functions.php';

// Charger la clé
$privateKey = loadPrivateKey('private_key.pem');

// Soumettre une IP
$success = submitIP('192.168.1.100', $privateKey);

if ($success) {
    echo "IP soumise avec succès\n";
} else {
    echo "Échec de la soumission\n";
}

// Libérer la clé
openssl_pkey_free($privateKey);
?>
```

## 🆚 Comparaison Python vs PHP

| Caractéristique | Python | PHP |
|----------------|--------|-----|
| Algorithme | RSA-PSS | RSA PKCS#1 v1.5 |
| Hash | SHA-256 | SHA-256 |
| Bibliothèque | cryptography | OpenSSL |
| Dépendances | pip install | Extension native |

Les deux sont **acceptés par le serveur** ! 🎉

## 📄 Structure du code

```php
// 1. Charger la clé privée
$privateKey = openssl_pkey_get_private(file_get_contents('private_key.pem'));

// 2. Signer les données
openssl_sign($ipAddress, $signature, $privateKey, OPENSSL_ALGO_SHA256);

// 3. Encoder en base64
$signatureB64 = base64_encode($signature);

// 4. Créer le payload JSON
$payload = json_encode([
    'ip' => $ipAddress,
    'signature' => $signatureB64
]);

// 5. Envoyer au serveur
$response = file_get_contents(
    'http://localhost:5000/api/submit_ip',
    false,
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $payload
        ]
    ])
);
```

## ✨ Fonctionnalités

- ✅ Signature RSA avec SHA-256
- ✅ Encodage base64 automatique
- ✅ Gestion des erreurs
- ✅ Affichage formaté des réponses JSON
- ✅ Pas de dépendances externes (utilise OpenSSL natif)
- ✅ Compatible avec le serveur Python

## 🔗 Voir aussi

- **Python client**: `client_example.py`
- **Serveur**: `server.py`
- **Documentation**: `SECURITY.md`

---

**Fait avec 🔐 PHP et OpenSSL**
