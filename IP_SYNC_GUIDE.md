# Guide de Synchronisation Automatique des IP

## Vue d'ensemble

L'extension Activity Control inclut maintenant un système complet de synchronisation automatique des IP bannies avec un serveur central. Ce système permet de maintenir votre liste de bans à jour automatiquement sans intervention manuelle.

## Fonctionnement

### Architecture

```
Serveur Central (Flask)
        ↓
   [API /api/get_ips]
        ↓
Extension phpBB → ip_ban_sync service
        ↓
   phpbb_banlist (table native)
```

### Flux de synchronisation

1. **Déclenchement automatique** : À chaque chargement de page (événement `core.page_header`)
2. **Vérification d'intervalle** : Le service vérifie si l'intervalle configuré est écoulé
3. **Récupération distante** : Appel HTTP GET vers `/api/get_ips` du serveur central
4. **Comparaison** : Différence entre liste locale et liste distante
5. **Synchronisation** : 
   - Ajout des nouvelles IP via `user_ban()`
   - Suppression des IP absentes via `user_unban()`
6. **Logging** : Enregistrement dans `phpbb_ac_logs`

### Première synchronisation

La première synchronisation se déclenche **dès la première requête** après activation de l'extension :

- `ac_last_ip_sync` initialisé à 0 dans la migration
- Premier chargement de page → `should_sync()` retourne `true`
- Synchronisation immédiate avec le serveur central
- Mise à jour de `ac_last_ip_sync` avec le timestamp actuel

## Configuration

### Dans l'ACP (Extensions → Activity Control → Settings)

| Paramètre | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| **Enable IP synchronization** | Active/désactive la synchronisation | Désactivé |
| **Central server URL** | URL du serveur Flask | `http://localhost:5000` |
| **Synchronization interval** | Délai entre deux syncs (secondes) | 3600 (1 heure) |
| **Default ban reason** | Raison affichée pour les bans automatiques | "Activity Control - Central Ban List" |

### Variables de configuration (base de données)

```sql
-- Activation
ac_enable_ip_sync = 0/1

-- URL du serveur
ac_central_server_url = 'http://your-server:5000'

-- Intervalle en secondes
ac_ip_sync_interval = 3600

-- Timestamp dernière sync
ac_last_ip_sync = 1729785600

-- Version de la liste
ac_ip_list_version = 42

-- Raison du ban
ac_ban_reason = 'Activity Control - Central Ban List'
```

## API du serveur central

### Endpoint : GET /api/get_ips

**Requête :**
```bash
curl http://localhost:5000/api/get_ips
```

**Réponse :**
```json
{
  "version": 15,
  "ips": [
    "192.168.1.100",
    "10.20.30.40",
    "172.16.254.1"
  ]
}
```

### Champs de réponse

- **version** (int) : Version incrémentale de la liste (pour tracking des changements)
- **ips** (array) : Liste complète des IP à bannir

## Service ip_ban_sync

### Méthodes principales

#### `sync()` : Synchronisation complète
```php
$ip_ban_sync = $phpbb_container->get('linkguarder.activitycontrol.ip_ban_sync');
$result = $ip_ban_sync->sync();

// Résultat :
[
    'success' => true,
    'added' => 5,      // Nombre d'IP ajoutées
    'removed' => 2,    // Nombre d'IP supprimées
    'total' => 150,    // Total d'IP dans la liste
    'message' => 'Sync completed: 5 added, 2 removed, 150 total'
]
```

#### `should_sync()` : Vérification de nécessité
```php
if ($ip_ban_sync->should_sync()) {
    // Synchronisation nécessaire
}
```

### Méthodes internes

- `fetch_remote_ip_list($server_url)` : Récupération HTTP
- `sync_ip_list($remote_ips, $version)` : Synchronisation BD
- `add_ip_ban($ip)` : Ajout d'un ban (utilise `user_ban()`)
- `remove_ip_ban($ban_id)` : Retrait d'un ban (utilise `user_unban()`)

## Intégration dans le listener

### Événement : core.page_header

```php
public function check_ip_sync($event)
{
    if ($this->ip_ban_sync->should_sync())
    {
        try 
        {
            $this->ip_ban_sync->sync();
        }
        catch (\Exception $e)
        {
            // Erreurs loggées dans le service
        }
    }
}
```

**Pourquoi `core.page_header` ?**
- Événement déclenché à chaque page
- Exécution avant le rendu du contenu
- Permet de vérifier les bans avant traitement de la requête

## Synchronisation manuelle

### Via l'ACP

1. Aller dans **Extensions → Activity Control → IP Bans**
2. Cliquer sur le bouton **"Synchronize now"**
3. Le service est appelé immédiatement
4. Message de confirmation avec statistiques

### Via code PHP

```php
global $phpbb_container;
$ip_ban_sync = $phpbb_container->get('linkguarder.activitycontrol.ip_ban_sync');
$result = $ip_ban_sync->sync();
```

## Logging

### Logs de succès

**Clé de langue** : `LOG_AC_IP_SYNC_SUCCESS`

**Format** : "IP sync completed: %d added, %d removed, %d total"

**Exemple** : "IP sync completed: 5 added, 2 removed, 150 total"

### Logs d'erreur

**Clé de langue** : `LOG_AC_IP_SYNC_FAILED`

**Format** : "IP sync failed: %s"

**Exemples** :
- "IP sync failed: Failed to connect to central server"
- "IP sync failed: Invalid response format from server"

### Consultation des logs

**ACP** → Extensions → Activity Control → Logs

## Performance et optimisation

### Fréquence de synchronisation

**Recommandations** :

| Type de forum | Intervalle recommandé | Raison |
|---------------|----------------------|--------|
| Petit forum (<1000 users) | 3600s (1h) | Charge minimale |
| Forum moyen (1000-10000) | 7200s (2h) | Équilibre charge/fraîcheur |
| Grand forum (>10000) | 21600s (6h) | Minimiser impact performance |

### Impact sur les performances

- **Requête HTTP** : ~100-300ms par sync
- **Mise à jour BD** : ~10ms par IP modifiée
- **Total** : Généralement <500ms pour une liste de 200 IPs

**Note** : La synchronisation bloque la requête en cours pendant l'exécution. Pour les forums à fort trafic, envisagez un cron job séparé.

## Production : Cron Job (recommandé)

Pour les forums à fort trafic, utilisez un cron job au lieu de la vérification à chaque page.

### Désactiver la vérification automatique

Dans `event/listener.php`, commentez l'appel :

```php
public function check_ip_sync($event)
{
    // Désactivé - utiliser cron job
    return;
}
```

### Créer un script cron

**Fichier** : `/path/to/phpbb/cron_ip_sync.php`

```php
<?php
define('IN_PHPBB', true);
$phpbb_root_path = './';
$phpEx = 'php';

include($phpbb_root_path . 'common.' . $phpEx);

$ip_ban_sync = $phpbb_container->get('linkguarder.activitycontrol.ip_ban_sync');
$result = $ip_ban_sync->sync();

if ($result['success']) {
    echo $result['message'] . "\n";
} else {
    echo "Error: " . $result['message'] . "\n";
}
```

### Configuration crontab

```bash
# Synchronisation toutes les heures
0 * * * * /usr/bin/php /path/to/phpbb/cron_ip_sync.php >> /var/log/phpbb_ip_sync.log 2>&1
```

## Dépannage

### La synchronisation ne se déclenche pas

**Vérifications** :

1. `ac_enable_ip_sync = 1` dans la configuration
2. `ac_central_server_url` correctement configurée
3. Le serveur central est accessible : `curl http://your-server:5000/api/get_ips`
4. Pas d'erreur dans les logs phpBB

### Erreur "Failed to connect to central server"

**Causes possibles** :
- Serveur Flask éteint
- Firewall bloquant le port 5000
- URL incorrecte dans la configuration
- Timeout réseau (>10 secondes)

**Solutions** :
```bash
# Vérifier que le serveur tourne
curl http://localhost:5000/api/get_ips

# Tester depuis le serveur phpBB
php -r "echo file_get_contents('http://localhost:5000/api/get_ips');"

# Vérifier les logs du serveur Flask
cd /home/nox/Documents/roguebb
python server.py
```

### Les IP ne sont pas bannies

**Vérifications** :

1. Vérifier que `user_ban()` fonctionne :
```sql
SELECT * FROM phpbb_banlist WHERE ban_reason LIKE '%Activity Control%';
```

2. Vérifier les permissions du fichier `includes/functions_user.php`

3. Activer le debug phpBB dans `config.php` :
```php
@define('DEBUG', true);
```

### Performance dégradée

**Symptômes** : Pages lentes après activation de la sync

**Solutions** :

1. Augmenter l'intervalle de sync :
```php
ac_ip_sync_interval = 7200  // 2 heures au lieu de 1
```

2. Utiliser un cron job (voir section Production)

3. Désactiver temporairement :
```php
ac_enable_ip_sync = 0
```

## Sécurité

### Protection contre les attaques

Le service inclut plusieurs protections :

1. **Timeout HTTP** : 10 secondes maximum par requête
2. **Validation de réponse** : Vérification du format JSON
3. **Erreurs silencieuses** : Pas d'interruption de l'affichage en cas d'erreur
4. **Logging centralisé** : Traçabilité complète des actions

### Recommandations

- ✅ Utiliser HTTPS pour la connexion au serveur central (pas HTTP)
- ✅ Restreindre l'accès réseau au serveur central (firewall)
- ✅ Monitorer les logs pour détecter les anomalies
- ✅ Mettre en place une alerte si la sync échoue >3 fois

## Monitoring

### Vérifier l'état de la synchronisation

**Via ACP** : Extensions → Activity Control → Settings

Affiche :
- **Last synchronization** : Date/heure de la dernière sync réussie
- **IP list version** : Version actuelle de la liste

### Requête SQL directe

```sql
SELECT 
    FROM_UNIXTIME(config_value) as last_sync,
    TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(config_value), NOW()) as seconds_ago
FROM phpbb_config 
WHERE config_name = 'ac_last_ip_sync';

SELECT config_value as version
FROM phpbb_config 
WHERE config_name = 'ac_ip_list_version';
```

### Métriques importantes

- **Fréquence de sync** : Doit correspondre à `ac_ip_sync_interval`
- **Taux de succès** : >95% (consulter logs)
- **Nombre d'IP** : Cohérent avec le serveur central
- **Version de liste** : Doit augmenter régulièrement

## Exemple complet

### Mise en place initiale

```bash
# 1. Démarrer le serveur central
cd /home/nox/Documents/roguebb
python server.py &

# 2. Vérifier l'API
curl http://localhost:5000/api/get_ips

# 3. Configurer dans phpBB ACP
# - Enable IP synchronization: Yes
# - Central server URL: http://localhost:5000
# - Synchronization interval: 3600
# - Ban reason: "Blocked by Activity Control"

# 4. Forcer une première sync
# Charger n'importe quelle page du forum OU cliquer "Sync now" dans ACP

# 5. Vérifier les résultats
mysql -u user -p phpbb -e "
SELECT ban_ip, ban_reason FROM phpbb_banlist 
WHERE ban_reason LIKE '%Activity Control%' LIMIT 10;
"
```

### Résultat attendu

```
+----------------+------------------------------------+
| ban_ip         | ban_reason                         |
+----------------+------------------------------------+
| 192.168.1.100  | Activity Control - Central Ban List|
| 10.20.30.40    | Activity Control - Central Ban List|
| 172.16.254.1   | Activity Control - Central Ban List|
+----------------+------------------------------------+
```

## Support

### Logs à consulter en cas de problème

1. **phpBB error logs** : `/path/to/phpbb/store/`
2. **Apache/Nginx logs** : `/var/log/apache2/error.log`
3. **Flask server logs** : Console où `python server.py` tourne
4. **Activity Control logs** : ACP → Extensions → Activity Control → Logs

### Commandes de diagnostic

```bash
# Test connexion serveur
curl -v http://localhost:5000/api/get_ips

# Vérifier config phpBB
mysql -u user -p phpbb -e "
SELECT * FROM phpbb_config WHERE config_name LIKE 'ac_%ip%';
"

# Compter les bans Activity Control
mysql -u user -p phpbb -e "
SELECT COUNT(*) FROM phpbb_banlist 
WHERE ban_reason LIKE '%Activity Control%';
"

# Logs récents
mysql -u user -p phpbb -e "
SELECT * FROM phpbb_ac_logs 
WHERE log_action LIKE '%sync%' 
ORDER BY log_time DESC LIMIT 10;
"
```

## Conclusion

Le système de synchronisation automatique des IP :

✅ **Fonctionne dès la première requête** après activation  
✅ **Se met à jour automatiquement** selon l'intervalle configuré  
✅ **Utilise les fonctions natives phpBB** (`user_ban`, `user_unban`)  
✅ **Logue toutes les actions** pour audit et debug  
✅ **Gère les erreurs gracieusement** sans casser l'affichage  
✅ **Supporte la synchronisation manuelle** depuis l'ACP  

Pour toute question ou problème, consultez `TROUBLESHOOTING.md` ou les logs de l'extension.
