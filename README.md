# 🛡️ Activity Control - Extension phpBB# LinkGuarder Activity Control (phpBB 3.3.x)



**Version:** 1.0.0  Extension phpBB 3.3.x pour contrôler l’activité des utilisateurs, limiter les liens selon le nombre de messages, journaliser les actions et (conception incluse) gérer une liste d’IP bannies synchronisée avec un serveur central. Toute la configuration se fait depuis l’ACP.

**Auteur:** LinkGuarder Team  

**Licence:** GPL-2.0-only  > État actuel du code: fonctionnalités de contrôle de liens et de journaux opérationnelles (v1.1.0). Un squelette v1.2.0 est présent. La synchronisation de bannissements IP avec un serveur central est spécifiée ci‑dessous pour guider le développement v1.3.0.

**Compatibilité:** phpBB 3.3.1+, PHP 7.4+



Extension phpBB complète pour le contrôle de l'activité des utilisateurs, la gestion des liens spam et le signalement automatique des IPs suspectes vers un serveur central sécurisé.## Aperçu



---- Nom d’extension: linkguarder/activitycontrol

- Namespace: `linkguarder\\activitycontrol`

## ✨ Fonctionnalités principales- Version: 1.2.0 (voir `composer.json`)

- Compatibilité: phpBB >= 3.3.1, PHP >= 7.4

✅ **Contrôle automatique des liens** dans posts, signatures et profils  - Points d’entrée:

✅ **Gestion dynamique des groupes** selon l'activité utilisateur    - Fichier d’extension: `ext.php`

✅ **Signalement d'IP cryptographiquement sécurisé** (RSA 2048-bit)    - Services: `config/services.yml`

✅ **Stockage local** des IPs avec métadonnées complètes    - Routes (démo): `config/routing.yml`

✅ **Module ACP complet** avec Settings, Logs et IP Bans    - Contrôleur: `controller/main.php`

✅ **Infrastructure de synchronisation** pour serveur central    - Écouteur d’événements: `event/listener.php`

  - ACP: `acp/main_info.php`, `acp/main_module.php`, templates ACP dans `adm/style/`

---  - Migrations: `migrations/v1_1_0/initial_migration.php`, `migrations/v1_2_0/next_step.php`

  - Langues: `language/en/common.php`

## 📥 Installation rapide



```bash## Fonctionnalités existantes

# 1. Placez l'extension

cd /path/to/phpbb/ext/linkguarder/- Filtrage de liens selon le nombre de messages

git clone https://github.com/linkguarder/activitycontrol.git activitycontrol  - Supprime/neutralise les liens dans les messages des utilisateurs n’ayant pas atteint un seuil minimal (`min_posts_for_links`).

  - Peut mettre en quarantaine (modération) les messages concernés (`ac_quarantine_posts`).

# 2. Activez dans l'ACP  - Nettoie les liens dans la signature et les champs de profil selon des seuils distincts (`ac_remove_sig_links_posts`, `ac_remove_profile_links_posts`).

# Personnaliser → Gérer les extensions → Activity Control → Activer- Groupes dynamiques

  - Ajoute les nouveaux utilisateurs au groupe « AC - Utilisateurs restreints ». Transition automatique vers des groupes moins restreints en fonction de l’activité.

# 3. Configurez (optionnel)- Journaux d’action

# ACP → Extensions → Activity Control → Settings  - Table `phpbb_ac_logs` (créée par migration) pour tracer les actions de l’extension.

```  - Page ACP « Logs » listant les derniers événements.

- Intégration UI ACP/MCP

Pour le signalement d'IP, consultez [IP_REPORTING_INTEGRATION.md](IP_REPORTING_INTEGRATION.md)  - Pages ACP: Paramètres + Logs (voir `adm/style/acp_*.html`).

  - Injections CSS/JS minimales et logo dans les menus ACP/MCP.

---



## 📚 Documentation## Prérequis



- **[INSTALLATION.md](docs/INSTALLATION.md)** - Guide d'installation détaillé- phpBB 3.3.x (≥ 3.3.1 recommandé)

- **[CONFIGURATION.md](docs/CONFIGURATION.md)** - Configuration complète- PHP 7.4+

- **[IP_REPORTING_INTEGRATION.md](IP_REPORTING_INTEGRATION.md)** - Signalement d'IP- Accès administrateur à l’ACP

- **[INTEGRATION_SUMMARY.md](INTEGRATION_SUMMARY.md)** - Résumé technique

- **[CHANGELOG.md](CHANGELOG.md)** - Historique des versions

## Installation

---

1) Placer l’extension dans le dossier phpBB suivant:

## ⚙️ Configuration minimale

- `ext/linkguarder/activitycontrol`

### Prérequis

- phpBB 3.3.1+2) Activer l’extension depuis:

- PHP 7.4+

- Extension OpenSSL (pour signalement d'IP)- ACP > Personnaliser > Gérer les extensions > « Activity Control » > Activer



### Paramètres par défaut3) Les migrations s’exécutent automatiquement:



| Paramètre | Valeur par défaut |- Ajout des clés de configuration

|-----------|-------------------|- Création de la table de logs `phpbb_ac_logs`

| Posts min pour liens | 10 |- Ajout des modules ACP

| Posts min signature | 5 |

| Posts min profil | 5 |4) Vider le cache phpBB si besoin (ACP > Général > Purger le cache).

| Quarantaine | Désactivé |

| Signalement d'IP | Désactivé |

## Configuration (ACP)

---

- Paramètres (ACP > Extensions > Activity Control > Settings):

## 🔐 Sécurité  - Minimum posts to post links (`min_posts_for_links`)

  - Quarantine posts (`ac_quarantine_posts`) [Oui/Non]

⚠️ **Données sensibles protégées**  - Minimum posts for links in signature (`ac_remove_sig_links_posts`)

- Clé privée RSA dans `data/private_key.pem` (permissions `600`)  - Minimum posts for links in profile (`ac_remove_profile_links_posts`)

- Répertoire `data/` protégé par `.htaccess`- Journaux (ACP > Extensions > Activity Control > Logs):

- **Ne JAMAIS commit les fichiers `.pem` dans git**  - Visualisation des 50 derniers événements.



⚠️ **RGPD/Conformité**

- Les IPs sont des données personnelles## Architecture technique (résumé)

- Documentez votre base légale

- Informez les utilisateurs- Services (`config/services.yml`)

  - `linkguarder.activitycontrol.listener`: écoute des événements core (filtrage liens, groupes, UI ACP/MCP, logs).

---  - `linkguarder.activitycontrol.controller`: page démo et liaison template.

- Routes (`config/routing.yml`)

## 🏗️ Architecture technique  - `linkguarder_activitycontrol_controller`: `/activitycontrol/{name}` vers `controller\\main::handle`.

- Événements écoutés (extraits):

### Tables de base de données  - `core.submit_post_start`, `core.message_parser_check_message`: nettoyer liens avant envoi.

- `phpbb_ac_logs` : Logs de toutes les actions  - `core.member_register_after`, `core.submit_post_end`: placement/mise à jour des groupes utilisateurs.

- `phpbb_ac_remote_ip_bans` : Gestion des IP bannies  - `core.acp_page_header`, `core.mcp_page_header`: inclusions CSS.

- Base de données

### Services principaux  - `phpbb_ac_logs`: journaux d’actions (user_id, log_time, log_action, log_data JSON).

- `linkguarder.activitycontrol.listener` : Event listener principal  - Utilisation des tables natives pour groupes (`phpbb_groups`, `phpbb_user_group`).

- `linkguarder.activitycontrol.ip_reporter` : Signalement d'IP

- `linkguarder.activitycontrol.ip_ban_sync` : Synchronisation (squelette)Fichiers clés:



### Événements écoutés- `event/listener.php`: logique principale de filtrage/nettoyage/assignation de groupes/journalisation.

- `core.submit_post_start` - Filtrage liens posts- `acp/main_*`: déclaration et rendu des pages ACP (Settings, Logs).

- `core.ucp_profile_info_modify_sql_ary` - Filtrage signature/profil- `migrations/v1_1_0/initial_migration.php`: config initiale, création des logs, modules ACP.

- `core.member_register_after` - Attribution groupe initial- `language/en/common.php`: chaînes utilisées (ACP, logs, messages).

- `core.submit_post_end` - Mise à jour groupe

- Plus de 10 événements au total

## Conception: Gestion des IP bannies et synchronisation serveur central (v1.3.0)

---

Objectif: Permettre aux admins de gérer localement une liste d’adresses IP bannies (avec motifs, expirations), et de synchroniser automatiquement avec une liste provenant d’un serveur central. Le serveur central peut publier les IP bannies et les raisons; l’extension peut également lui signaler des bannissements locaux (optionnel).

## 🆘 Support

### Périmètre fonctionnel

### Problèmes courants

- ACP > Activity Control > IP bans

**L'extension ne s'active pas**  - Lister les IP bannies (locales et « gérées à distance »).

```bash  - Ajouter/éditer/supprimer une IP bannie localement (IP/CIDR, raison, date d’expiration optionnelle).

# Vérifier PHP  - Forcer une synchronisation manuelle et afficher l’état de la dernière synchronisation (succès/erreur, timestamp).

php -v  # Doit être >= 7.4  - Filtrer par source (locale/serveur central) et statut (actif/expiré).

- Application bans

# Vérifier OpenSSL  - S’appuyer sur la table native `phpbb_banlist` pour faire respecter les bannissements au niveau de phpBB (cohérence avec le core: login, visite, etc.).

php -m | grep openssl  - Maintenir une table d’appoint pour la synchronisation distante.

```

### Stockage et schéma proposé

**Les liens ne sont pas filtrés**

- Les modérateurs/admins sont exemptés- Table native: `phpbb_banlist` (existante; utilisée pour appliquer les bans IP).

- Vérifiez la configuration dans l'ACP- Nouvelle table (extension): `phpbb_ac_remote_ip_bans` (gérée par migration v1.3.0):

- Purgez le cache phpBB  - `id` (PK, auto)

  - `ip` (VARBINARY pour IPv4/IPv6, ou VCHAR si on stocke en texte normalisé)

**Erreur "Private key not found"**  - `cidr` (TINYINT, 0–128)

```bash  - `reason` (VCHAR/TEXT)

# Générer les clés (voir IP_REPORTING_INTEGRATION.md)  - `source` (VCHAR: ex. « central », « local »)

chmod 600 data/private_key.pem  - `action` (ENUM: add/remove) — pour refléter l’intention distante

```  - `hash` (VCHAR) — identifiant immuable ou checksum fourni par le serveur

  - `banned_at` (INT/TIMESTAMP)

### Obtenir de l'aide  - `expires_at` (INT/TIMESTAMP, nullable)

- 🐛 [Issues GitHub](https://github.com/linkguarder/activitycontrol/issues)  - `last_sync_at` (INT)

- 📖 [Wiki](https://github.com/linkguarder/activitycontrol/wiki)  - `status` (VCHAR: active, removed, expired, conflict)

- 📧 support@linkguarder.team

Notes:

---- On stocke l’entrée « distante » telle que fournie pour audit/traçabilité; l’application dans `phpbb_banlist` reste la source d’exécution.

- Pour IPv6/CIDR: normaliser en texte (ex: `2001:db8::/64`) et convertir/résoudre lors de l’application.

## 👨‍💻 Développement

### Configs ACP (nouvelles clés)

```bash

# Clone et lien symbolique- `ac_ipban_sync_enabled` (bool)

git clone https://github.com/linkguarder/activitycontrol.git- `ac_ipban_server_url` (string, ex: `https://central.example.com/api/ip-bans`)

ln -s /path/to/activitycontrol /path/to/phpbb/ext/linkguarder/activitycontrol- `ac_ipban_server_token` (string secret ou clé d’API)

- `ac_ipban_sync_interval` (int, minutes)

# Tests- `ac_ipban_last_sync` (timestamp)

phpunit tests/- `ac_ipban_post_local` (bool) — reporter les bans locaux vers le serveur central (optionnel)



# ConventionsCes clés seront ajoutées via la migration v1.3.0 et exposées dans une nouvelle page ACP « IP bans ».

# - PSR-12 pour le formatage

# - snake_case pour les configs### Tâche cron de synchronisation

# - Docblocks obligatoires

```- Implémenter une tâche `cron.task` dédiée (service tag `cron.task`) qui:

  - S’exécute selon `ac_ipban_sync_interval` et un backoff en cas d’erreur.

**Contribuer:** Forkez → Branche → PR  - Appelle l’API du serveur central et récupère les changements depuis `ac_ipban_last_sync`.

  - Met à jour `phpbb_ac_remote_ip_bans`, puis applique les entrées dans `phpbb_banlist`:

---    - action=add: créer/mettre à jour ban (IP/CIDR), raison « Remote: <source> — <reason> ».

    - action=remove: lever le ban correspondant si géré par « central ».

## 📄 Licence  - Journalise les actions dans `phpbb_ac_logs` (ex: `ip_ban_sync_started`, `ip_ban_applied`, `ip_ban_removed`, `ip_ban_sync_failed`).



GPL-2.0-only © 2025 LinkGuarder Team### Contrat d’API (serveur central) — proposition



---- Authentification: via en‑tête HTTP `Authorization: Bearer <token>` ou HMAC.

- Endpoints

## 🙏 Crédits  1) Pull (obligatoire):

     - `GET /api/ip-bans?since=<unix_ts>&limit=1000`

- phpBB Team     - Réponse `200 application/json`:

- Communauté phpBB       {

- Tous les contributeurs         "cursor": 1697380000,

         "items": [

---           {

             "ip": "203.0.113.45",

**🔐 Protégez votre communauté avec Activity Control**             "cidr": 32,

             "reason": "Abus de spam",

*Développé avec ❤️ par LinkGuarder Team*             "action": "add",

             "banned_by": "central",
             "banned_at": 1697375000,
             "expires_at": null,
             "hash": "b2b3c5..."
           },
           {
             "ip": "2001:db8::",
             "cidr": 64,
             "reason": "Sonde malveillante",
             "action": "remove",
             "banned_by": "central",
             "banned_at": 1697375100,
             "expires_at": null,
             "hash": "c9d1a0..."
           }
         ]
       }
  2) Push (optionnel):
     - `POST /api/ip-bans/report`
     - Corps JSON (exemple):
       {
         "ip": "198.51.100.7",
         "cidr": 32,
         "reason": "Tentatives répétées de bruteforce",
         "action": "add",
         "reported_by": "forum.example.com",
         "context": {
           "user_id": 123,
           "topic_id": 456,
           "evidence": "5 tentatives en 1 minute"
         }
       }

- Tolérance aux pannes:
  - Backoff exponentiel, limites de débit, timeouts.
  - Journaliser erreurs et continuer l’application locale.

### Algorithme de fusion et politique de conflit

- Validation
  - IP IPv4/IPv6 valides, gestion de plages CIDR, raisons limitées en longueur.
- Déduplication
  - Clé `hash` ou `(ip,cidr,source)` sert d’identifiant.
- Application
  - Les bans « distants » créent/Met à jour des entrées `phpbb_banlist`. Les bans locaux créés manuellement restent intacts par défaut.
- Conflits
  - Priorité locale > distante (configurable). Marquer `status=conflict` si une entrée distante tente de lever un ban local.

### UI ACP « IP bans » (proposition)

- Nouveau mode ACP `ip_bans` dans `acp/main_info.php`.
- Nouveau template `adm/style/acp_activitycontrol_ip_bans.html`:
  - Tableau des bans (IP/CIDR, raison, source, statut, créé/expire, actions).
  - Formulaire d’ajout/édition.
  - Bouton « Synchroniser maintenant » + indicateurs d’état.

### Migrations v1.3.0 (proposition)

- Ajout des configs listées plus haut.
- Création de `phpbb_ac_remote_ip_bans`.
- Ajout du module ACP `ip_bans`.
- Enregistrement de la tâche cron (service + tag `cron.task`).


## Développement

- Structure du code
  - Éviter de mettre la logique métier dans les templates; privilégier l’écouteur d’événements et des classes dédiées (services séparés si la logique grossit).
  - Conserver les noms de clés de config en snake_case.
- Migrations
  - Nommer les répertoires de version `vX_Y_Z` et incrémenter semver.
  - Fournir `effectively_installed()` pour idempotence.
- Langues
  - Ajouter les nouvelles clés dans `language/en/common.php` et prévoir i18n (ex: `language/fr/common.php`) si besoin.
- Performances
  - Limiter la pagination des logs (actuellement 50).
  - Index SQL sur colonnes de recherche (déjà fait pour `ac_logs`).
- Qualité
  - PHP 7.4+: utiliser types docblocks, valider entrées, éviter les E_NOTICE.
  - Respecter la licence GPL‑2.0‑only (voir `composer.json`).

Environnement de dev

- Purge du cache après modifications d’extension.
- Activer/désactiver l’extension depuis l’ACP pour forcer les migrations.
- Astuces: surveiller `store/logs` et la table `phpbb_migrations` pour l’état d’installation.


## Tests et validation (recommandations)

- Scénarios existants
  - Poster un message avec un lien en dessous du seuil: lien remplacé, log créé, (optionnel) mise en quarantaine.
  - Modifier signature/profil avec liens en dessous des seuils: liens supprimés, log créé.
  - Passage de seuils: vérification du groupe utilisateur mis à jour.
- Scénarios IP bans (à implémenter)
  - Création locale d’un ban IP avec expiration.
  - Synchronisation pull depuis serveur central (ajout/suppression), contrôle de `phpbb_banlist`.
  - Conflits locale vs distante et journalisation.


## Dépannage

- L’extension ne s’affiche pas dans l’ACP
  - Vérifier le chemin `ext/linkguarder/activitycontrol` et les permissions de fichiers.
- Erreur de migration / table manquante
  - Purger le cache et réactiver l’extension.
  - Vérifier `phpbb_migrations` et le préfixe de tables.
- Les liens ne sont pas filtrés pour certains comptes
  - Les modérateurs/administrateurs (ACL `m_`, `a_`) sont exclus par design.


## Feuille de route

- v1.1.0
  - Migration initiale, paramètres et logs.
- v1.2.0
  - Préparation de nouvelles étapes (fichier placeholder `migrations/v1_2_0/next_step.php`).
- v1.3.0 (prévu)
  - Gestion ACP des bannissements IP et synchronisation avec serveur central (tâche cron, UI ACP, migrations, logs).


## Changelog

Consigner les changements ici à chaque version (suivre SemVer):

- 1.2.0 — métadonnées mises à jour (voir `composer.json`); code actuel fonctionnel pour le contrôle de liens et journaux.
- 1.1.0 — version initiale (migrations, paramètres, logs, ACP de base).


## Sécurité et confidentialité

- Les adresses IP sont des données personnelles dans de nombreuses juridictions. Documenter votre base légale et minimiser la conservation (utiliser `expires_at`).
- Conserver uniquement les informations nécessaires, chiffrer le transport (HTTPS), tourner les jetons d’API du serveur central.
- Ajouter des contrôles d’accès stricts (ACL administrateur) aux écrans d’IP bannies.


## Licence et crédits

- Licence: GPL‑2.0‑only (voir `composer.json`).
- Équipe: LinkGuarder Team.


## Références rapides (fichiers)

- `ext.php` — base de l’extension
- `config/services.yml` — DI + écouteurs + contrôleur
- `config/routing.yml` — route de démonstration
- `controller/main.php` — rendu de `styles/prosilver/template/body.html`
- `event/listener.php` — logique principale (filtres, groupes, logs)
- `acp/main_info.php`, `acp/main_module.php` — modules ACP
- `adm/style/acp_activitycontrol_body.html` — paramètres ACP
- `adm/style/acp_activitycontrol_logs.html` — logs ACP
- `migrations/v1_1_0/initial_migration.php` — création `phpbb_ac_logs`, configs, modules ACP
- `language/en/common.php` — chaînes UI/ACP
