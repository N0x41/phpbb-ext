# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/lang/fr/).

## [1.0.0] - 2025-10-24

### Ajouté
- **Contrôle des liens** : Suppression automatique des liens dans les posts, signatures et profils pour les utilisateurs n'ayant pas atteint le nombre minimum de posts requis
- **Système de quarantaine** : Option pour mettre en file d'attente de modération les posts contenant des liens supprimés
- **Gestion des groupes** : Attribution automatique des utilisateurs à des groupes selon leur activité (restreints, partiellement vérifiés, vérifiés)
- **Logs détaillés** : Enregistrement de toutes les actions de modération dans une table dédiée
- **Module ACP complet** : Interface d'administration pour configurer tous les paramètres
- **Synchronisation IP bannies** : Infrastructure pour synchroniser les IP bannies avec un serveur central (squelette)
- **Signalement d'IP** : Système de signalement automatique des IP suspectes au serveur central avec authentification RSA
  - Stockage local en JSON des IPs signalées
  - Signature cryptographique RSA (PKCS#1 v1.5 + SHA-256)
  - Soumission automatique au serveur central via HTTP POST
  - Gestion des erreurs et logs détaillés
- **Protection des données** : Répertoire `data/` protégé avec `.htaccess`
- **Vérifications de sécurité** : Validation PHP 7.4+ et OpenSSL lors de l'activation

### Structure
- Table `ac_logs` : Logs des actions de l'extension
- Table `ac_remote_ip_bans` : Gestion des IP bannies distantes
- Service `ip_reporter` : Signalement d'IP au serveur central
- Service `ip_ban_sync` : Synchronisation des bans (squelette)
- Migration unique `install_v1_0_0.php` : Installation complète

### Configuration
- `ac_version` : Version de l'extension (1.0.0)
- `min_posts_for_links` : Posts minimum pour poster des liens (défaut: 10)
- `ac_quarantine_posts` : Activer la quarantaine (défaut: désactivé)
- `ac_remove_sig_links_posts` : Posts minimum pour liens dans signature (défaut: 5)
- `ac_remove_profile_links_posts` : Posts minimum pour liens dans profil (défaut: 5)
- `ac_ipban_sync_enabled` : Activer synchronisation IP (défaut: désactivé)
- `ac_ipban_server_url` : URL serveur de synchronisation
- `ac_ipban_server_token` : Token d'authentification
- `ac_ipban_sync_interval` : Intervalle de sync en minutes (défaut: 60)
- `ac_enable_ip_reporting` : Activer signalement d'IP (défaut: désactivé)
- `ac_central_server_url` : URL serveur central (défaut: http://localhost:5000)

### Documentation
- `IP_REPORTING_INTEGRATION.md` : Guide d'installation et configuration du signalement d'IP
- `INTEGRATION_SUMMARY.md` : Résumé de l'intégration phpBB
- `README.md` : Documentation générale
- `CHANGELOG.md` : Ce fichier

### Sécurité
- Validation des entrées utilisateur
- Échappement SQL via l'API phpBB
- Protection des clés privées RSA
- Vérification des signatures cryptographiques
- Protection du répertoire `data/` contre l'accès direct

[1.0.0]: https://github.com/linkguarder/activitycontrol/releases/tag/v1.0.0
