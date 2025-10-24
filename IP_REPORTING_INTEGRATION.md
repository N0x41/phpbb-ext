# Intégration du système de signalement d'IP dans phpBB

## 📋 Description

Cette extension phpBB intègre un système de signalement automatique des IPs suspectes vers un serveur central avec authentification cryptographique RSA.

## 🔧 Installation

### 1. Copier la clé privée

```bash
cp /path/to/private_key.pem /path/to/phpbb/ext/linkguarder/activitycontrol/data/
```

### 2. Protéger le répertoire data

Ajoutez dans le fichier `.htaccess` du répertoire `data/` :

```apache
# Interdire l'accès direct aux fichiers
Order deny,allow
Deny from all
```

### 3. Activer l'extension

Dans l'ACP de phpBB :
- Aller dans `Personnaliser` → `Gérer les extensions`
- Activer `Activity Control`

### 4. Configurer le signalement d'IP

Dans l'ACP, configurer :
- `ac_enable_ip_reporting` : Activer/Désactiver le signalement (0 ou 1)
- `ac_central_server_url` : URL du serveur central (défaut: `http://localhost:5000`)

## 🚀 Fonctionnement

### Déclencheurs de signalement

L'extension signale automatiquement une IP au serveur central quand un utilisateur :

1. **Tente de poster des liens** avec un nombre de posts insuffisant
2. **Tente d'ajouter des liens dans sa signature** avec un nombre de posts insuffisant
3. **Tente d'ajouter des liens dans son profil** avec un nombre de posts insuffisant

### Stockage local

Les IPs sont d'abord stockées localement dans :
```
ext/linkguarder/activitycontrol/data/reported_ips.json
```

Format du fichier JSON :
```json
{
  "md5_hash_of_ip": {
    "ip": "192.168.1.100",
    "reason": "Attempted to post links with insufficient post count",
    "context": {
      "user_id": 123,
      "username": "spammer",
      "user_posts": 2,
      "action": "post_with_links"
    },
    "first_seen": 1698155000,
    "last_seen": 1698155000,
    "count": 1,
    "submitted": true,
    "submitted_time": 1698155005
  }
}
```

### Soumission au serveur central

Si le signalement est activé (`ac_enable_ip_reporting = 1`), l'extension :

1. **Signe l'IP** avec la clé privée RSA
2. **Envoie la requête** au serveur central
3. **Marque l'IP comme soumise** dans le fichier JSON local

## 🔐 Sécurité

### Clé privée

⚠️ **IMPORTANT** : La clé privée doit être protégée !

- Permissions recommandées : `600` (lecture/écriture propriétaire uniquement)
- Le répertoire `data/` doit être protégé par `.htaccess`
- Ne jamais commit la clé privée dans git

```bash
chmod 600 /path/to/phpbb/ext/linkguarder/activitycontrol/data/private_key.pem
```

### Signature RSA

L'extension utilise :
- **Algorithme** : RSA PKCS#1 v1.5
- **Hash** : SHA-256
- **Taille clé** : 2048-bit

## 📊 Logs

Les actions de signalement sont enregistrées dans :
- Le log phpBB standard (table `phpbb_log`)
- Le log personnalisé Activity Control (table `phpbb_ac_logs`)

Types de logs :
- `LOG_AC_IP_SUBMITTED` : IP soumise avec succès
- `LOG_AC_SUBMISSION_FAILED` : Échec de soumission
- `LOG_AC_SERVER_UNREACHABLE` : Serveur injoignable
- `LOG_AC_PRIVATE_KEY_MISSING` : Clé privée manquante
- `LOG_AC_PRIVATE_KEY_INVALID` : Clé privée invalide
- `LOG_AC_SIGNATURE_FAILED` : Échec de signature

## 🔧 Configuration ACP

Vous pouvez ajouter les options dans le module ACP existant.

Exemple de code pour le formulaire ACP :

```php
$form_fields = [
    'ac_enable_ip_reporting' => [
        'lang' => 'AC_ENABLE_IP_REPORTING',
        'validate' => 'bool',
        'type' => 'radio:yes_no',
        'explain' => true,
    ],
    'ac_central_server_url' => [
        'lang' => 'AC_CENTRAL_SERVER_URL',
        'validate' => 'string',
        'type' => 'text:40:255',
        'explain' => true,
    ],
];
```

## 📝 Entrées de langue

Ajoutez dans `language/en/common.php` :

```php
'AC_ENABLE_IP_REPORTING' => 'Enable IP reporting to central server',
'AC_ENABLE_IP_REPORTING_EXPLAIN' => 'When enabled, suspicious IPs will be automatically reported to the central server',
'AC_CENTRAL_SERVER_URL' => 'Central server URL',
'AC_CENTRAL_SERVER_URL_EXPLAIN' => 'URL of the central IP reporting server (e.g., http://localhost:5000)',

// Logs
'LOG_AC_IP_SUBMITTED' => 'IP %s submitted to central server',
'LOG_AC_SUBMISSION_FAILED' => 'Failed to submit IP %s (HTTP %s)',
'LOG_AC_SERVER_UNREACHABLE' => 'Central server unreachable: %s',
'LOG_AC_PRIVATE_KEY_MISSING' => 'Private key not found: %s',
'LOG_AC_PRIVATE_KEY_INVALID' => 'Invalid private key: %s',
'LOG_AC_SIGNATURE_FAILED' => 'Failed to sign IP for submission',
```

## 🧪 Tests

### Test manuel

1. Créer un compte utilisateur avec 0 posts
2. Tenter de poster un message avec un lien
3. Vérifier que :
   - Le lien est supprimé
   - L'IP est ajoutée à `data/reported_ips.json`
   - L'IP est soumise au serveur central (si activé)
   - Les logs sont créés

### Test de la signature

Vous pouvez tester la signature manuellement :

```php
$ip_reporter = $phpbb_container->get('linkguarder.activitycontrol.ip_reporter');
$result = $ip_reporter->report_ip('192.168.1.100', 'Test', []);
var_dump($result); // Should be true if successful
```

## 🔄 Nettoyage automatique

Le service nettoie automatiquement les anciennes entrées (>30 jours) :

```php
$ip_reporter->cleanup_old_entries(2592000); // 30 jours
```

Vous pouvez ajouter cela dans une tâche cron :

```php
// Dans config/services.yml
linkguarder.activitycontrol.ip_cleanup:
    class: linkguarder\activitycontrol\cron\ip_cleanup
    arguments:
        - '@linkguarder.activitycontrol.ip_reporter'
    calls:
        - [set_name, [cron.task.ip_cleanup]]
    tags:
        - { name: cron.task }
```

## 📂 Structure des fichiers

```
ext/linkguarder/activitycontrol/
├── service/
│   ├── ip_reporter.php         # Service de signalement d'IP
│   └── ip_ban_sync.php          # Service existant
├── event/
│   └── listener.php             # Listener modifié avec intégration
├── data/
│   ├── private_key.pem          # Clé privée RSA (SECRÈTE)
│   ├── reported_ips.json        # Liste locale des IPs
│   └── .htaccess                # Protection du répertoire
├── migrations/
│   └── v1_4_0/
│       └── add_ip_reporting_config.php
└── config/
    └── services.yml             # Services Symfony
```

## 🆘 Dépannage

### "Private key not found"

```bash
# Vérifier que la clé existe
ls -l ext/linkguarder/activitycontrol/data/private_key.pem

# Si elle n'existe pas, la copier
cp /path/to/private_key.pem ext/linkguarder/activitycontrol/data/
chmod 600 ext/linkguarder/activitycontrol/data/private_key.pem
```

### "Failed to submit IP"

- Vérifier que le serveur central est accessible
- Vérifier l'URL dans `ac_central_server_url`
- Vérifier les logs phpBB pour plus de détails

### "Invalid signature"

- Vérifier que la clé privée correspond à la clé publique du serveur
- Régénérer les clés si nécessaire

### Permissions du fichier JSON

```bash
# Le fichier doit être accessible en écriture par le serveur web
chmod 664 ext/linkguarder/activitycontrol/data/reported_ips.json
chown www-data:www-data ext/linkguarder/activitycontrol/data/reported_ips.json
```

## 🎯 Résumé

✅ **Installation**
- Copier la clé privée dans `data/`
- Protéger le répertoire avec `.htaccess`
- Activer l'extension
- Configurer l'URL du serveur

✅ **Fonctionnement**
- Détecte automatiquement les tentatives de spam
- Stocke les IPs localement en JSON
- Soumet les IPs au serveur central avec signature RSA
- Logs toutes les actions

✅ **Sécurité**
- Clé privée protégée
- Signature RSA 2048-bit
- Transmission sécurisée
- Compatible avec le serveur Python central

---

**Développé par LinkGuarder Team** 🔐
