# 📘 Documentation Technique - Activity Control

**Version:** 1.0.0  
**Dernière mise à jour:** 26 octobre 2025

---

## 📋 Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture](#2-architecture)
3. [Base de données](#3-base-de-données)
4. [Services et dépendances](#4-services-et-dépendances)
5. [Événements phpBB](#5-événements-phpbb)
6. [Migrations](#6-migrations)
7. [Module ACP](#7-module-acp)
8. [Signalement d'IP](#8-signalement-dip)
9. [Synchronisation IP bans](#9-synchronisation-ip-bans)
10. [Sécurité](#10-sécurité)
11. [Tests et validation](#11-tests-et-validation)
12. [Développement](#12-développement)

---

## 1. Vue d'ensemble

### 1.1 Identité de l'extension

- **Nom:** linkguarder/activitycontrol
- **Namespace:** `linkguarder\activitycontrol`
- **Version:** 1.0.0
- **Compatibilité:** phpBB >= 3.3.1, PHP >= 7.4
- **Licence:** GPL-2.0-only

### 1.2 Objectifs

L'extension Activity Control vise à :

1. **Prévenir le spam** en limitant les liens des nouveaux utilisateurs
2. **Gérer dynamiquement** les groupes selon l'activité
3. **Signaler automatiquement** les IPs suspectes à un serveur central
4. **Journaliser** toutes les actions de modération
5. **Synchroniser** les bannissements IP (fonctionnalité future)

### 1.3 Points d'entrée principaux

```
ext/linkguarder/activitycontrol/
├── ext.php                    # Point d'entrée principal
├── config/
│   ├── services.yml          # Définition des services
│   └── routing.yml           # Routes (démo)
├── event/
│   └── listener.php          # Écouteur d'événements
├── service/
│   ├── ip_reporter.php       # Signalement d'IP
│   └── ip_ban_sync.php       # Synchronisation (squelette)
├── acp/
│   ├── main_info.php         # Déclaration modules ACP
│   └── main_module.php       # Logique modules ACP
└── migrations/
    └── install_v1_0_0.php    # Installation initiale
```

---

## 2. Architecture

### 2.1 Diagramme de flux

```
┌─────────────────────┐
│   Utilisateur       │
│  (Nouvelle action)  │
└──────────┬──────────┘
           │
           │ Post/Signature/Profil
           ▼
┌─────────────────────────┐
│  phpBB Event System     │
│  (core.submit_post_*)   │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│   Event Listener        │
│  (listener.php)         │
├─────────────────────────┤
│ 1. Vérifie posts count  │
│ 2. Filtre les liens     │
│ 3. Met à jour groupes   │
│ 4. Journalise action    │
│ 5. Signale IP (opt.)    │
└──────────┬──────────────┘
           │
           ├──→ Tables DB (logs, bans)
           │
           └──→ IP Reporter Service
                    │
                    ├──→ Stockage local (JSON)
                    └──→ Serveur central (HTTP)
```

### 2.2 Composants clés

#### 2.2.1 Event Listener

**Fichier:** `event/listener.php`

Classe principale qui écoute les événements phpBB et applique la logique métier :

- Filtrage des liens dans les posts
- Nettoyage des signatures et profils
- Gestion des groupes utilisateurs
- Journalisation des actions
- Signalement des IPs suspectes

#### 2.2.2 IP Reporter Service

**Fichier:** `service/ip_reporter.php`

Service dédié au signalement d'IP :

- Stockage local dans `data/reported_ips.json`
- Signature cryptographique RSA
- Soumission HTTP au serveur central
- Gestion des erreurs et retry

#### 2.2.3 IP Ban Sync Service

**Fichier:** `service/ip_ban_sync.php`

Service pour la synchronisation future (v1.3.0) :

- Pull des IP bannies depuis le serveur central
- Push des bannissements locaux
- Mise à jour de `phpbb_banlist`
- Gestion des conflits

### 2.3 Configuration Symfony (DI)

**Fichier:** `config/services.yml`

```yaml
services:
    linkguarder.activitycontrol.listener:
        class: linkguarder\activitycontrol\event\listener
        arguments:
            - '@config'
            - '@dbal.conn'
            - '@user'
            - '@auth'
            - '@request'
            - '@template'
            - '@language'
            - '@log'
            - '@group_helper'
            - '@linkguarder.activitycontrol.ip_reporter'
        tags:
            - { name: event.listener }
    
    linkguarder.activitycontrol.ip_reporter:
        class: linkguarder\activitycontrol\service\ip_reporter
        arguments:
            - '@config'
            - '@dbal.conn'
            - '@user'
            - '@log'
            - '%core.root_path%'
            - '%core.php_ext%'
    
    linkguarder.activitycontrol.ip_ban_sync:
        class: linkguarder\activitycontrol\service\ip_ban_sync
        arguments:
            - '@config'
            - '@dbal.conn'
            - '@log'
    
    linkguarder.activitycontrol.controller:
        class: linkguarder\activitycontrol\controller\main
        arguments:
            - '@config'
            - '@template'
            - '@user'
```

---

## 3. Base de données

### 3.1 Table : `phpbb_ac_logs`

**Objectif:** Journaliser toutes les actions de l'extension

**Structure:**

| Colonne | Type | Description |
|---------|------|-------------|
| `log_id` | INT (PK, AUTO) | Identifiant unique du log |
| `user_id` | INT | ID de l'utilisateur concerné |
| `log_time` | INT | Timestamp Unix de l'action |
| `log_action` | VARCHAR(255) | Type d'action (ex: `links_removed_post`) |
| `log_data` | TEXT | Données JSON supplémentaires |

**Index:**
- PRIMARY KEY sur `log_id`
- INDEX sur `user_id`
- INDEX sur `log_time`

**Exemple de log_data (JSON):**

```json
{
    "user_posts": 5,
    "links_removed": 3,
    "subject": "Check out my website",
    "forum_id": 2
}
```

### 3.2 Table : `phpbb_ac_remote_ip_bans`

**Objectif:** Gérer les IP bannies synchronisées (prévu v1.3.0)

**Structure:**

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT (PK, AUTO) | Identifiant unique |
| `ip` | VARBINARY(16) | Adresse IP (IPv4/IPv6) |
| `cidr` | TINYINT | Masque CIDR (0-128) |
| `reason` | VARCHAR(255) | Raison du bannissement |
| `source` | VARCHAR(50) | Source (`local` ou `central`) |
| `action` | VARCHAR(20) | Action (`add` ou `remove`) |
| `hash` | VARCHAR(64) | Hash unique pour déduplication |
| `banned_at` | INT | Timestamp du bannissement |
| `expires_at` | INT | Timestamp d'expiration (nullable) |
| `last_sync_at` | INT | Dernier timestamp de sync |
| `status` | VARCHAR(50) | Statut (`active`, `expired`, `conflict`) |

**Index:**
- PRIMARY KEY sur `id`
- UNIQUE KEY sur `hash`
- INDEX sur `ip, cidr`
- INDEX sur `status`

### 3.3 Utilisation des tables natives

L'extension utilise également :

- **`phpbb_groups`** : Gestion des groupes d'utilisateurs
- **`phpbb_user_group`** : Association utilisateurs ↔ groupes
- **`phpbb_banlist`** : Application effective des bans IP
- **`phpbb_log`** : Logs phpBB standards (en plus de `ac_logs`)

---

## 4. Services et dépendances

### 4.1 Dépendances injectées

#### Event Listener

```php
public function __construct(
    \phpbb\config\config $config,              // Configuration phpBB
    \phpbb\db\driver\driver_interface $db,     // Connexion DB
    \phpbb\user $user,                          // Utilisateur courant
    \phpbb\auth\auth $auth,                     // Authentification
    \phpbb\request\request $request,            // Requête HTTP
    \phpbb\template\template $template,         // Moteur de templates
    \phpbb\language\language $language,         // Gestion des langues
    \phpbb\log\log_interface $log,              // Logs phpBB
    \phpbb\group\helper $group_helper,          // Aide groupes
    \linkguarder\activitycontrol\service\ip_reporter $ip_reporter  // Signalement IP
)
```

#### IP Reporter Service

```php
public function __construct(
    \phpbb\config\config $config,           // Configuration
    \phpbb\db\driver\driver_interface $db,  // DB
    \phpbb\user $user,                       // Utilisateur
    \phpbb\log\log_interface $log,          // Logs
    $phpbb_root_path,                        // Chemin racine phpBB
    $php_ext                                 // Extension PHP
)
```

### 4.2 Configuration (clés)

#### Clés existantes (v1.0.0)

```php
// Version
'ac_version' => '1.0.0',

// Contrôle des liens
'min_posts_for_links' => 10,               // Posts min pour liens dans posts
'ac_quarantine_posts' => 0,                // Quarantaine (0=Non, 1=Oui)
'ac_remove_sig_links_posts' => 5,          // Posts min pour liens dans signature
'ac_remove_profile_links_posts' => 5,      // Posts min pour liens dans profil

// Signalement d'IP
'ac_enable_ip_reporting' => 0,             // Activer signalement (0=Non, 1=Oui)
'ac_central_server_url' => 'http://localhost:5000',  // URL serveur central

// Synchronisation IP (prévu v1.3.0)
'ac_ipban_sync_enabled' => 0,              // Activer sync
'ac_ipban_server_url' => '',               // URL serveur
'ac_ipban_server_token' => '',             // Token API
'ac_ipban_sync_interval' => 60,            // Intervalle (minutes)
'ac_ipban_last_sync' => 0,                 // Dernier sync
'ac_ipban_post_local' => 0,                // Reporter bans locaux
```

---

## 5. Événements phpBB

### 5.1 Événements écoutés

L'extension écoute les événements suivants :

#### 5.1.1 Filtrage des posts

**Événement:** `core.submit_post_start`

**Callback:** `process_post_content()`

**Actions:**
- Vérifie le nombre de posts de l'utilisateur
- Détecte et supprime les liens si `user_posts < min_posts_for_links`
- Met en quarantaine si `ac_quarantine_posts = 1`
- Journalise l'action dans `ac_logs`
- Signale l'IP si `ac_enable_ip_reporting = 1`

**Code simplifié:**

```php
public function process_post_content($event)
{
    $message = $event['data']['message'];
    $user_posts = $this->user->data['user_posts'];
    $min_posts = (int) $this->config['min_posts_for_links'];
    
    if ($user_posts < $min_posts && !$this->is_exempt()) {
        // Détecter liens
        preg_match_all('#https?://[^\s<>"]+|www\.[^\s<>"]+#i', $message, $links);
        
        if (!empty($links[0])) {
            // Supprimer liens
            $message = preg_replace('#https?://[^\s<>"]+|www\.[^\s<>"]+#i', '[link removed]', $message);
            
            // Journaliser
            $this->log_action('links_removed_post', [
                'user_posts' => $user_posts,
                'links_count' => count($links[0])
            ]);
            
            // Signaler IP
            if ($this->config['ac_enable_ip_reporting']) {
                $this->ip_reporter->report_ip(
                    $this->user->ip,
                    'Attempted to post links with insufficient post count',
                    ['user_id' => $this->user->data['user_id'], ...]
                );
            }
            
            // Mettre à jour message
            $data = $event['data'];
            $data['message'] = $message;
            $event['data'] = $data;
        }
    }
}
```

#### 5.1.2 Filtrage signature

**Événement:** `core.ucp_profile_info_modify_sql_ary`

**Callback:** `clean_signature()`

**Actions:**
- Nettoie les liens de la signature si nécessaire
- Journalise et signale si applicable

#### 5.1.3 Filtrage profil

**Événement:** `core.ucp_profile_info_modify_sql_ary`

**Callback:** `clean_profile_fields()`

**Actions:**
- Nettoie les liens des champs de profil
- Journalise et signale si applicable

#### 5.1.4 Gestion des groupes

**Événement:** `core.member_register_after`

**Callback:** `add_user_to_restricted_group()`

**Actions:**
- Ajoute l'utilisateur au groupe "AC - Utilisateurs restreints"

**Événement:** `core.submit_post_end`

**Callback:** `update_user_group()`

**Actions:**
- Vérifie les seuils d'activité
- Déplace vers groupe approprié selon le nombre de posts

#### 5.1.5 Interface ACP/MCP

**Événement:** `core.acp_page_header`

**Callback:** `add_acp_assets()`

**Actions:**
- Injecte CSS/JS dans les pages ACP
- Ajoute le logo dans le menu

**Événement:** `core.mcp_page_header`

**Callback:** `add_mcp_assets()`

**Actions:**
- Idem pour le MCP

### 5.2 Méthode `getSubscribedEvents()`

```php
static public function getSubscribedEvents()
{
    return [
        'core.submit_post_start' => 'process_post_content',
        'core.message_parser_check_message' => 'clean_message_content',
        'core.submit_post_end' => 'update_user_group',
        'core.member_register_after' => 'add_user_to_restricted_group',
        'core.ucp_profile_info_modify_sql_ary' => 'clean_signature',
        'core.ucp_profile_info_modify_sql_ary' => 'clean_profile_fields',
        'core.acp_page_header' => 'add_acp_assets',
        'core.mcp_page_header' => 'add_mcp_assets',
        // ... autres événements
    ];
}
```

---

## 6. Migrations

### 6.1 Migration v1.0.0

**Fichier:** `migrations/install_v1_0_0.php`

**Classe:** `\linkguarder\activitycontrol\migrations\install_v1_0_0`

**Objectif:** Installation complète de l'extension

#### 6.1.1 Structure

```php
class install_v1_0_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['ac_version']);
    }
    
    static public function depends_on()
    {
        return ['\phpbb\db\migration\data\v33x\v330'];
    }
    
    public function update_data()
    {
        // Retourne array d'opérations
    }
    
    public function update_schema()
    {
        // Retourne array de modifications DB
    }
}
```

#### 6.1.2 Opérations effectuées

**Configuration:**

```php
// Ajout des clés de config
['config.add', ['ac_version', '1.0.0']],
['config.add', ['min_posts_for_links', 10]],
['config.add', ['ac_quarantine_posts', 0]],
['config.add', ['ac_remove_sig_links_posts', 5]],
['config.add', ['ac_remove_profile_links_posts', 5]],
['config.add', ['ac_enable_ip_reporting', 0]],
['config.add', ['ac_central_server_url', 'http://localhost:5000']],
['config.add', ['ac_ipban_sync_enabled', 0]],
['config.add', ['ac_ipban_server_url', '']],
['config.add', ['ac_ipban_server_token', '']],
['config.add', ['ac_ipban_sync_interval', 60]],
['config.add', ['ac_ipban_last_sync', 0]],
['config.add', ['ac_ipban_post_local', 0]],
```

**Modules ACP:**

```php
// Ajout du module parent
['module.add', [
    'acp',
    'ACP_CAT_DOT_MODS',
    'ACP_ACTIVITYCONTROL_TITLE'
]],

// Ajout des sous-modules
['module.add', [
    'acp',
    'ACP_ACTIVITYCONTROL_TITLE',
    [
        'module_basename' => '\linkguarder\activitycontrol\acp\main_module',
        'modes' => ['settings', 'logs'],
    ]
]],
```

**Tables:**

```php
// Création table ac_logs
'add_tables' => [
    $this->table_prefix . 'ac_logs' => [
        'COLUMNS' => [
            'log_id' => ['UINT', null, 'auto_increment'],
            'user_id' => ['UINT', 0],
            'log_time' => ['UINT:11', 0],
            'log_action' => ['VCHAR:255', ''],
            'log_data' => ['TEXT', ''],
        ],
        'PRIMARY_KEY' => 'log_id',
        'KEYS' => [
            'user_id' => ['INDEX', 'user_id'],
            'log_time' => ['INDEX', 'log_time'],
        ],
    ],
],
```

**Groupes:**

```php
// Création du groupe "AC - Utilisateurs restreints"
['custom', [[$this, 'create_restricted_group']]],
```

### 6.2 Migrations futures

#### v1.3.0 : IP Bans & Synchronisation

Prévisions :

- Création table `ac_remote_ip_bans`
- Ajout module ACP `ip_bans`
- Configuration tâche cron pour sync
- Nouvelles clés de config si nécessaire

---

## 7. Module ACP

### 7.1 Structure

**Fichiers:**

- `acp/main_info.php` : Déclaration des modules
- `acp/main_module.php` : Logique et rendu

**Templates:**

- `adm/style/acp_activitycontrol_body.html` : Page Settings
- `adm/style/acp_activitycontrol_logs.html` : Page Logs
- `adm/style/acp_activitycontrol_ip_bans.html` : Page IP Bans (future)

### 7.2 Mode : Settings

**URL:** `adm/index.php?i=-linkguarder-activitycontrol-acp-main_module&mode=settings`

**Fonctionnalités:**

- Configuration de `min_posts_for_links`
- Activation/désactivation de la quarantaine
- Configuration signature/profil
- Activation signalement d'IP
- URL serveur central

**Template principal:**

```html
<h1>{L_ACP_ACTIVITYCONTROL_SETTINGS}</h1>

<form method="post" action="{U_ACTION}">
    <fieldset>
        <legend>{L_ACP_AC_GENERAL_SETTINGS}</legend>
        
        <dl>
            <dt><label for="min_posts_for_links">{L_MIN_POSTS_FOR_LINKS}</label></dt>
            <dd><input type="number" id="min_posts_for_links" name="min_posts_for_links" value="{MIN_POSTS_FOR_LINKS}" /></dd>
        </dl>
        
        <dl>
            <dt><label for="ac_quarantine_posts">{L_AC_QUARANTINE_POSTS}</label></dt>
            <dd>
                <input type="radio" name="ac_quarantine_posts" value="1" <!-- IF AC_QUARANTINE_POSTS -->checked<!-- ENDIF --> /> {L_YES}
                <input type="radio" name="ac_quarantine_posts" value="0" <!-- IF not AC_QUARANTINE_POSTS -->checked<!-- ENDIF --> /> {L_NO}
            </dd>
        </dl>
        
        <!-- Autres champs... -->
    </fieldset>
    
    <p class="submit-buttons">
        <input type="submit" name="submit" value="{L_SUBMIT}" class="button1" />
    </p>
    
    {S_FORM_TOKEN}
</form>
```

### 7.3 Mode : Logs

**URL:** `adm/index.php?i=-linkguarder-activitycontrol-acp-main_module&mode=logs`

**Fonctionnalités:**

- Affichage des 50 derniers logs
- Filtrage par action
- Recherche par utilisateur
- Export CSV (prévu)

**Requête SQL:**

```php
$sql = 'SELECT l.*, u.username
    FROM ' . $this->ac_logs_table . ' l
    LEFT JOIN ' . USERS_TABLE . ' u ON l.user_id = u.user_id
    ORDER BY l.log_time DESC
    LIMIT 50';
```

**Template:**

```html
<table class="table1">
    <thead>
        <tr>
            <th>{L_LOG_TIME}</th>
            <th>{L_USERNAME}</th>
            <th>{L_LOG_ACTION}</th>
            <th>{L_LOG_DATA}</th>
        </tr>
    </thead>
    <tbody>
        <!-- BEGIN logs -->
        <tr>
            <td>{logs.LOG_TIME}</td>
            <td>{logs.USERNAME}</td>
            <td>{logs.LOG_ACTION}</td>
            <td>{logs.LOG_DATA}</td>
        </tr>
        <!-- END logs -->
    </tbody>
</table>
```

---

## 8. Signalement d'IP

### 8.1 Service IP Reporter

**Fichier:** `service/ip_reporter.php`

**Classe:** `\linkguarder\activitycontrol\service\ip_reporter`

### 8.2 Flux de signalement

```
1. Détection action suspecte (liens avec posts insuffisants)
   ↓
2. Appel ip_reporter->report_ip()
   ↓
3. Stockage local (data/reported_ips.json)
   ↓
4. Si ac_enable_ip_reporting = 1 :
   ↓
5. Signature RSA de l'IP
   ↓
6. HTTP POST vers serveur central
   ↓
7. Journalisation résultat (succès/échec)
```

### 8.3 Méthode `report_ip()`

```php
public function report_ip($ip, $reason, $context = [])
{
    // 1. Valider IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    // 2. Hash IP pour stockage
    $ip_hash = md5($ip);
    
    // 3. Charger données existantes
    $reported_ips = $this->load_reported_ips();
    
    // 4. Ajouter/mettre à jour
    if (isset($reported_ips[$ip_hash])) {
        $reported_ips[$ip_hash]['count']++;
        $reported_ips[$ip_hash]['last_seen'] = time();
    } else {
        $reported_ips[$ip_hash] = [
            'ip' => $ip,
            'reason' => $reason,
            'context' => $context,
            'first_seen' => time(),
            'last_seen' => time(),
            'count' => 1,
            'submitted' => false,
        ];
    }
    
    // 5. Sauvegarder
    $this->save_reported_ips($reported_ips);
    
    // 6. Soumettre au serveur central
    if ($this->config['ac_enable_ip_reporting']) {
        return $this->submit_to_central_server($ip, $reason, $context);
    }
    
    return true;
}
```

### 8.4 Signature cryptographique

```php
private function submit_to_central_server($ip, $reason, $context)
{
    // 1. Charger clé privée
    $private_key_path = $this->phpbb_root_path . 'ext/linkguarder/activitycontrol/data/private_key.pem';
    
    if (!file_exists($private_key_path)) {
        $this->log->add('critical', 0, $this->user->ip, 'LOG_AC_PRIVATE_KEY_MISSING');
        return false;
    }
    
    $private_key = openssl_pkey_get_private(file_get_contents($private_key_path));
    
    // 2. Préparer payload
    $payload = [
        'ip' => $ip,
        'reason' => $reason,
        'timestamp' => time(),
        'context' => $context,
    ];
    
    $payload_json = json_encode($payload);
    
    // 3. Signer
    openssl_sign($payload_json, $signature, $private_key, OPENSSL_ALGO_SHA256);
    $signature_b64 = base64_encode($signature);
    
    // 4. Préparer requête
    $data = [
        'ip' => $ip,
        'reason' => $reason,
        'timestamp' => time(),
        'context' => $context,
        'signature' => $signature_b64,
    ];
    
    // 5. HTTP POST
    $url = $this->config['ac_central_server_url'] . '/api/report';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 6. Journaliser
    if ($http_code === 200) {
        $this->log->add('admin', 0, $this->user->ip, 'LOG_AC_IP_SUBMITTED', false, [$ip]);
        return true;
    } else {
        $this->log->add('critical', 0, $this->user->ip, 'LOG_AC_SUBMISSION_FAILED', false, [$ip, $http_code]);
        return false;
    }
}
```

### 8.5 Format JSON local

**Fichier:** `data/reported_ips.json`

```json
{
    "5f4dcc3b5aa765d61d8327deb882cf99": {
        "ip": "192.168.1.100",
        "reason": "Attempted to post links with insufficient post count",
        "context": {
            "user_id": 123,
            "username": "spammer",
            "user_posts": 2,
            "action": "post_with_links",
            "subject": "Check out my website!",
            "forum_id": 2
        },
        "first_seen": 1698155000,
        "last_seen": 1698155300,
        "count": 3,
        "submitted": true,
        "submitted_time": 1698155005
    }
}
```

### 8.6 Protection du répertoire `data/`

**Fichier:** `data/.htaccess`

```apache
# Deny access to all files in this directory
<FilesMatch ".*">
    Require all denied
</FilesMatch>

# Alternative pour Apache 2.2
<IfModule !mod_authz_core.c>
    Order Allow,Deny
    Deny from all
</IfModule>
```

---

## 9. Synchronisation IP bans

### 9.1 Architecture (prévu v1.3.0)

```
┌──────────────────────┐
│  Serveur Central     │
│  (API REST)          │
└──────────┬───────────┘
           │
           │ GET /api/ip-bans?since=...
           │ POST /api/ip-bans/report
           ▼
┌──────────────────────┐
│  IP Ban Sync Service │
│  (Tâche cron)        │
├──────────────────────┤
│ 1. Pull changements  │
│ 2. Mise à jour DB    │
│ 3. Applique bans     │
│ 4. Push locaux       │
└──────────┬───────────┘
           │
           ├──→ phpbb_ac_remote_ip_bans (métadonnées)
           └──→ phpbb_banlist (application)
```

### 9.2 Tâche cron

**Fichier:** `cron/task/sync_ip_bans.php` (à créer)

```php
namespace linkguarder\activitycontrol\cron\task;

class sync_ip_bans extends \phpbb\cron\task\base
{
    protected $config;
    protected $ip_ban_sync;
    
    public function run()
    {
        if (!$this->config['ac_ipban_sync_enabled']) {
            return;
        }
        
        $this->ip_ban_sync->sync();
    }
    
    public function is_runnable()
    {
        $interval = (int) $this->config['ac_ipban_sync_interval'] * 60;
        $last_sync = (int) $this->config['ac_ipban_last_sync'];
        
        return (time() - $last_sync) >= $interval;
    }
    
    public function should_run()
    {
        return $this->config['ac_ipban_sync_enabled'] && $this->is_runnable();
    }
}
```

### 9.3 Contrat d'API

#### 9.3.1 Pull : Récupérer les bans

**Requête:**

```http
GET /api/ip-bans?since=1698155000&limit=1000
Authorization: Bearer <token>
```

**Réponse:**

```json
{
    "cursor": 1698160000,
    "items": [
        {
            "ip": "203.0.113.45",
            "cidr": 32,
            "reason": "Spam abuse",
            "action": "add",
            "banned_by": "central",
            "banned_at": 1698155000,
            "expires_at": null,
            "hash": "b2b3c5a1e4..."
        },
        {
            "ip": "2001:db8::",
            "cidr": 64,
            "reason": "Malicious scanning",
            "action": "remove",
            "banned_by": "central",
            "banned_at": 1698155100,
            "expires_at": null,
            "hash": "c9d1a0f8b3..."
        }
    ]
}
```

#### 9.3.2 Push : Signaler un ban local

**Requête:**

```http
POST /api/ip-bans/report
Authorization: Bearer <token>
Content-Type: application/json

{
    "ip": "198.51.100.7",
    "cidr": 32,
    "reason": "Repeated brute-force attempts",
    "action": "add",
    "reported_by": "forum.example.com",
    "context": {
        "user_id": 123,
        "topic_id": 456,
        "evidence": "5 attempts in 1 minute"
    }
}
```

**Réponse:**

```json
{
    "status": "accepted",
    "hash": "d4e2f1c7a9...",
    "message": "IP ban reported successfully"
}
```

### 9.4 Algorithme de fusion

```php
public function merge_remote_bans($remote_bans)
{
    foreach ($remote_bans as $ban) {
        // 1. Vérifier si existe déjà
        $existing = $this->get_ban_by_hash($ban['hash']);
        
        if ($existing) {
            // Mise à jour si modifié
            if ($existing['action'] !== $ban['action']) {
                $this->update_ban($ban);
            }
        } else {
            // Nouvelle entrée
            $this->insert_ban($ban);
        }
        
        // 2. Appliquer dans phpbb_banlist
        if ($ban['action'] === 'add') {
            $this->apply_ban($ban['ip'], $ban['cidr'], $ban['reason']);
        } elseif ($ban['action'] === 'remove') {
            $this->remove_ban($ban['ip'], $ban['cidr']);
        }
        
        // 3. Journaliser
        $this->log_ban_action($ban);
    }
    
    // 4. Mettre à jour cursor
    $this->config->set('ac_ipban_last_sync', time());
}
```

### 9.5 Gestion des conflits

**Politique par défaut:** Priorité locale > distante

```php
private function apply_ban($ip, $cidr, $reason)
{
    // Vérifier conflit local
    $local_ban = $this->get_local_ban($ip, $cidr);
    
    if ($local_ban) {
        // Marquer comme conflit
        $this->mark_conflict($ip, $cidr, 'Local ban exists');
        return;
    }
    
    // Appliquer
    $sql = 'INSERT INTO ' . BANLIST_TABLE . ' ' . $this->db->sql_build_array('INSERT', [
        'ban_ip' => $ip . '/' . $cidr,
        'ban_reason' => 'Remote: ' . $reason,
        'ban_give_reason' => 'Synchronized from central server',
        'ban_start' => time(),
        'ban_end' => 0,
    ]);
    
    $this->db->sql_query($sql);
}
```

---

## 10. Sécurité

### 10.1 Protection des clés cryptographiques

#### Permissions fichiers

```bash
# Clé privée : lecture/écriture propriétaire uniquement
chmod 600 data/private_key.pem

# Répertoire data : exécution + lecture propriétaire uniquement
chmod 700 data/

# Propriétaire : utilisateur web (www-data, apache, nginx, etc.)
chown www-data:www-data data/ -R
```

#### .gitignore

```gitignore
# Ne JAMAIS commit les clés
data/private_key.pem
data/public_key.pem
data/reported_ips.json
```

### 10.2 Validation des entrées

#### IPs

```php
// Validation
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    throw new \InvalidArgumentException('Invalid IP address');
}

// IPv4 vs IPv6
$is_ipv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
```

#### URLs

```php
// Détection liens
$pattern = '#https?://[^\s<>"]+|www\.[^\s<>"]+#i';
preg_match_all($pattern, $text, $matches);
```

#### SQL Injection

```php
// Utiliser TOUJOURS les requêtes préparées ou sql_build_array
$sql = 'SELECT * FROM ' . $this->ac_logs_table . '
    WHERE user_id = ' . (int) $user_id;  // Cast int

// Ou
$sql = 'INSERT INTO ' . $this->ac_logs_table . ' ' .
    $this->db->sql_build_array('INSERT', $data);  // Échappement auto
```

#### XSS

```php
// Échapper toujours l'output
$this->template->assign_vars([
    'USERNAME' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
]);
```

### 10.3 RGPD et conformité

#### Données collectées

- **Adresses IP** : Données personnelles dans l'UE
- **Actions utilisateur** : Métadonnées (posts, sujets, etc.)
- **Timestamps** : Traçabilité

#### Base légale

Documenter dans votre politique de confidentialité :

> **Lutte contre le spam et sécurité**  
> Nous collectons et signalons les adresses IP des utilisateurs qui tentent  
> de publier du contenu spam sur notre plateforme. Ces données sont partagées  
> avec un serveur central sécurisé pour protéger l'ensemble de notre réseau.  
> Base légale : Intérêt légitime (Article 6(1)(f) RGPD)

#### Droits des utilisateurs

- **Droit d'accès** : Fournir copie des données
- **Droit de suppression** : Supprimer sur demande
- **Droit d'opposition** : Cesser le traitement

**Implémentation:**

```php
// Récupérer logs d'un utilisateur
public function get_user_logs($user_id)
{
    $sql = 'SELECT * FROM ' . $this->ac_logs_table . '
        WHERE user_id = ' . (int) $user_id;
    
    $result = $this->db->sql_query($sql);
    $logs = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    
    return $logs;
}

// Supprimer données d'un utilisateur
public function delete_user_data($user_id)
{
    // Logs
    $sql = 'DELETE FROM ' . $this->ac_logs_table . '
        WHERE user_id = ' . (int) $user_id;
    $this->db->sql_query($sql);
    
    // IPs signalées (anonymiser)
    $reported_ips = $this->load_reported_ips();
    
    foreach ($reported_ips as $hash => &$data) {
        if (isset($data['context']['user_id']) && $data['context']['user_id'] == $user_id) {
            $data['context']['user_id'] = 0;
            $data['context']['username'] = '[deleted]';
        }
    }
    
    $this->save_reported_ips($reported_ips);
}
```

### 10.4 Audit et logs

#### Types de logs

| Action | Niveau | Description |
|--------|--------|-------------|
| `links_removed_post` | INFO | Liens supprimés d'un post |
| `links_removed_signature` | INFO | Liens supprimés signature |
| `links_removed_profile` | INFO | Liens supprimés profil |
| `user_group_updated` | INFO | Groupe utilisateur changé |
| `ip_submitted` | ADMIN | IP signalée au serveur |
| `ip_submission_failed` | CRITICAL | Échec signalement IP |
| `ip_ban_applied` | ADMIN | Ban IP appliqué |
| `ip_ban_sync_failed` | CRITICAL | Échec synchronisation |
| `private_key_missing` | CRITICAL | Clé privée introuvable |

#### Rétention

**Recommandation:** Nettoyer les logs > 90 jours

```php
public function cleanup_old_logs($days = 90)
{
    $cutoff = time() - ($days * 86400);
    
    $sql = 'DELETE FROM ' . $this->ac_logs_table . '
        WHERE log_time < ' . (int) $cutoff;
    
    $this->db->sql_query($sql);
}
```

---

## 11. Tests et validation

### 11.1 Tests unitaires

**Structure recommandée:**

```
tests/
├── event/
│   └── listener_test.php
├── service/
│   ├── ip_reporter_test.php
│   └── ip_ban_sync_test.php
└── functional/
    ├── acp_settings_test.php
    └── post_filtering_test.php
```

### 11.2 Tests fonctionnels

#### Scénario 1 : Filtrage liens

```
1. Créer utilisateur avec 0 posts
2. Tenter de poster message avec lien
3. Vérifier : lien remplacé par [link removed]
4. Vérifier : log créé dans ac_logs
5. Vérifier : IP ajoutée à reported_ips.json
```

#### Scénario 2 : Quarantaine

```
1. Activer ac_quarantine_posts
2. Poster message avec lien (utilisateur < 10 posts)
3. Vérifier : post mis en file modération
4. Vérifier : notification modérateur
```

#### Scénario 3 : Signalement IP

```
1. Activer ac_enable_ip_reporting
2. Démarrer serveur central (mock)
3. Poster message avec lien
4. Vérifier : HTTP POST envoyé au serveur
5. Vérifier : signature valide
6. Vérifier : log succès
```

#### Scénario 4 : Gestion groupes

```
1. Créer nouvel utilisateur
2. Vérifier : ajouté au groupe "AC - Utilisateurs restreints"
3. Poster 10 messages
4. Vérifier : déplacé vers groupe "AC - Utilisateurs partiellement vérifiés"
5. Poster 50 messages
6. Vérifier : déplacé vers groupe "AC - Utilisateurs vérifiés"
```

### 11.3 Tests de sécurité

#### Injection SQL

```php
// Test avec user_id malicieux
$malicious_id = "1 OR 1=1--";
$logs = $this->get_user_logs($malicious_id);
// Doit retourner array vide, pas tous les logs
```

#### XSS

```php
// Test avec username malicieux
$malicious_name = "<script>alert('XSS')</script>";
$template_vars = ['USERNAME' => $malicious_name];
// Doit être échappé dans le template
```

#### Path Traversal

```php
// Test avec fichier malicieux
$malicious_path = "../../../etc/passwd";
$content = file_get_contents($this->phpbb_root_path . $malicious_path);
// Doit échouer ou être bloqué
```

### 11.4 Tests de performance

#### Charge DB

```sql
-- Mesurer temps requête logs
EXPLAIN SELECT l.*, u.username
FROM phpbb_ac_logs l
LEFT JOIN phpbb_users u ON l.user_id = u.user_id
ORDER BY l.log_time DESC
LIMIT 50;

-- Vérifier utilisation index
-- Key: log_time (index)
```

#### Charge HTTP

```bash
# Benchmarker signalement IP
ab -n 1000 -c 10 http://localhost:5000/api/report
```

---

## 12. Développement

### 12.1 Environnement de développement

#### Prérequis

- phpBB 3.3.x installé
- PHP 7.4+ avec extensions :
  - `openssl`
  - `curl`
  - `json`
  - `pdo_mysql` ou équivalent
- Serveur web (Apache/Nginx)
- MySQL/MariaDB ou PostgreSQL

#### Installation dev

```bash
# Clone l'extension
git clone https://github.com/linkguarder/activitycontrol.git
cd activitycontrol

# Lien symbolique vers phpBB
ln -s $(pwd) /path/to/phpbb/ext/linkguarder/activitycontrol

# Générer clés RSA (dev uniquement)
cd /path/to/phpbb/ext/linkguarder/activitycontrol
openssl genrsa -out data/private_key.pem 2048
openssl rsa -in data/private_key.pem -pubout -out data/public_key.pem
chmod 600 data/private_key.pem
chmod 644 data/public_key.pem

# Activer extension dans phpBB ACP
```

### 12.2 Structure du code

#### Conventions

**Noms de fichiers:**
- `snake_case.php`

**Classes:**
- `PascalCase`

**Méthodes:**
- `snake_case()`

**Variables:**
- `$snake_case`

**Constantes:**
- `UPPER_SNAKE_CASE`

**Clés de config:**
- `snake_case` (ex: `ac_enable_ip_reporting`)

#### Namespaces

```php
namespace linkguarder\activitycontrol\[sous-dossier]\;

// Exemples
namespace linkguarder\activitycontrol\event;
namespace linkguarder\activitycontrol\service;
namespace linkguarder\activitycontrol\acp;
namespace linkguarder\activitycontrol\migrations;
```

#### Docblocks

```php
/**
 * Signale une adresse IP au serveur central
 *
 * @param string $ip       Adresse IP à signaler
 * @param string $reason   Raison du signalement
 * @param array  $context  Contexte supplémentaire
 *
 * @return bool True si succès, false sinon
 *
 * @throws \InvalidArgumentException Si IP invalide
 */
public function report_ip($ip, $reason, $context = [])
{
    // ...
}
```

### 12.3 Outils de développement

#### Debug phpBB

```php
// Activer debug dans config.php
$phpbb_config['debug'] = true;

// Logs détaillés
$this->log->add('admin', 0, $this->user->ip, 'DEBUG: ' . var_export($data, true));
```

#### Purge cache

```bash
# CLI
php bin/phpbbcli.php cache:purge

# Ou via ACP
# ACP > Général > Purger le cache
```

#### Migrations

```bash
# Réinitialiser migration (DEV UNIQUEMENT)
# Supprimer entrée dans phpbb_migrations
DELETE FROM phpbb_migrations 
WHERE migration_name LIKE '%activitycontrol%';

# Puis réactiver extension
```

### 12.4 Git workflow

#### Branches

```
main           # Production stable
develop        # Développement actif
feature/*      # Nouvelles fonctionnalités
bugfix/*       # Corrections de bugs
release/*      # Préparation releases
```

#### Commits

```bash
# Format
<type>(<scope>): <subject>

# Types
feat     # Nouvelle fonctionnalité
fix      # Correction bug
docs     # Documentation
style    # Formatage
refactor # Refactoring
test     # Tests
chore    # Maintenance

# Exemples
feat(ip-reporter): add retry mechanism
fix(listener): prevent duplicate log entries
docs(readme): update installation steps
```

#### Pull Requests

```markdown
## Description
Brief description of changes

## Type of change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Functional tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Comments added where necessary
- [ ] Documentation updated
- [ ] No new warnings generated
```

### 12.5 Release process

#### 1. Préparation

```bash
# Créer branche release
git checkout -b release/1.1.0 develop

# Mettre à jour version
# - composer.json : "version": "1.1.0"
# - ext.php : return '1.1.0';
# - migrations/install_v1_1_0.php (si nouvelle migration)

# Mettre à jour CHANGELOG.md
```

#### 2. Tests

```bash
# Tests unitaires
phpunit tests/

# Tests fonctionnels
# - Installation fraîche
# - Mise à jour depuis version précédente
# - Scénarios utilisateur complets
```

#### 3. Documentation

```bash
# Mettre à jour
# - README.md
# - DOCS.md (si nouvelles fonctionnalités)
# - IP_REPORTING_INTEGRATION.md (si changements signalement)
# - TROUBLESHOOTING.md (si nouveaux problèmes connus)
```

#### 4. Merge et tag

```bash
# Merge dans main
git checkout main
git merge --no-ff release/1.1.0

# Tag
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin main --tags

# Merge dans develop
git checkout develop
git merge --no-ff release/1.1.0
git push origin develop

# Supprimer branche release
git branch -d release/1.1.0
```

#### 5. Publication

```bash
# Créer archive
git archive --format=zip --prefix=activitycontrol/ v1.1.0 -o activitycontrol-1.1.0.zip

# GitHub Release
# - Upload ZIP
# - Copier CHANGELOG
# - Marquer comme release
```

---

## 📚 Annexes

### A. Glossaire

| Terme | Définition |
|-------|------------|
| **ACP** | Admin Control Panel (Panneau d'administration) |
| **MCP** | Moderator Control Panel (Panneau de modération) |
| **UCP** | User Control Panel (Panneau utilisateur) |
| **DI** | Dependency Injection (Injection de dépendances) |
| **PSR** | PHP Standard Recommendation |
| **CIDR** | Classless Inter-Domain Routing (notation IP/masque) |
| **RSA** | Rivest–Shamir–Adleman (algorithme cryptographique) |
| **PKCS#1** | Public Key Cryptography Standards #1 |

### B. Références externes

- [phpBB Documentation](https://area51.phpbb.com/docs/dev/3.3.x/)
- [phpBB Extension Development](https://www.phpbb.com/support/docs/en/3.3/kb/article/how-to-start-extension-development/)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)
- [Semantic Versioning](https://semver.org/)
- [Keep a Changelog](https://keepachangelog.com/)

### C. Contact et support

- **GitHub:** https://github.com/linkguarder/activitycontrol
- **Issues:** https://github.com/linkguarder/activitycontrol/issues
- **Wiki:** https://github.com/linkguarder/activitycontrol/wiki
- **Email:** support@linkguarder.team

---

**Document généré le 26 octobre 2025**  
**© 2025 LinkGuarder Team - GPL-2.0-only**
