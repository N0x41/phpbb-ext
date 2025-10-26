<<<<<<< HEAD
# 🛡️ Activity Control - Extension phpBB
=======
# 🛡️ Activity Control - Extension (phpBB 3.3.x)



**Version:** 1.0.0  Extension phpBB 3.3.x pour contrôler l’activité des utilisateurs, limiter les liens selon le nombre de messages, journaliser les actions et (conception incluse) gérer une liste d’IP bannies synchronisée avec un serveur central. Toute la configuration se fait depuis l’ACP.
>>>>>>> 58b0971d42b0c967b523dba0798d8bfc3fcd7004

**Version:** 1.0.0  
**Auteur:** LinkGuarder Team  
**Licence:** GPL-2.0-only  
**Compatibilité:** phpBB 3.3.1+, PHP 7.4+

Extension phpBB complète pour le contrôle de l'activité des utilisateurs, la gestion des liens spam et le signalement automatique des IPs suspectes vers un serveur central sécurisé.

---

## 📋 Table des matières

- [Fonctionnalités](#-fonctionnalités-principales)
- [Installation](#-installation-rapide)
- [Configuration](#️-configuration)
- [Sécurité](#-sécurité)
- [Documentation](#-documentation)
- [Support](#-support)
- [Licence](#-licence)

---

## ✨ Fonctionnalités principales

✅ **Contrôle automatique des liens** dans posts, signatures et profils  
✅ **Gestion dynamique des groupes** selon l'activité utilisateur  
✅ **Signalement d'IP cryptographiquement sécurisé** (RSA 2048-bit)  
✅ **Stockage local** des IPs avec métadonnées complètes  
✅ **Module ACP complet** avec Settings, Logs et IP Bans  
✅ **Infrastructure de synchronisation** pour serveur central

---

## 📥 Installation rapide

### Prérequis

- phpBB 3.3.1+
- PHP 7.4+
- Extension OpenSSL (pour signalement d'IP)

### Étapes d'installation

```bash
# 1. Placez l'extension
cd /path/to/phpbb/ext/linkguarder/
git clone https://github.com/linkguarder/activitycontrol.git activitycontrol

# 2. Activez dans l'ACP
# Personnaliser → Gérer les extensions → Activity Control → Activer

# 3. Configurez (optionnel)
# ACP → Extensions → Activity Control → Settings
```

Les migrations s'exécutent automatiquement lors de l'activation :
- Ajout des clés de configuration
- Création de la table de logs `phpbb_ac_logs`
- Ajout des modules ACP

**Note :** Vider le cache phpBB si nécessaire (ACP > Général > Purger le cache).

---

## ⚙️ Configuration

### Paramètres par défaut

| Paramètre | Valeur par défaut | Description |
|-----------|-------------------|-------------|
| Posts min pour liens | 10 | Nombre de posts requis pour poster des liens |
| Posts min signature | 5 | Nombre de posts requis pour liens dans signature |
| Posts min profil | 5 | Nombre de posts requis pour liens dans profil |
| Quarantaine | Désactivé | Mettre en modération les posts avec liens supprimés |
| Signalement d'IP | Désactivé | Signaler automatiquement les IPs suspectes |

### Configuration ACP

**Paramètres** (ACP > Extensions > Activity Control > Settings):
- `min_posts_for_links` - Minimum de posts pour poster des liens
- `ac_quarantine_posts` - Activer la quarantaine des posts
- `ac_remove_sig_links_posts` - Minimum de posts pour liens dans signature
- `ac_remove_profile_links_posts` - Minimum de posts pour liens dans profil

**Journaux** (ACP > Extensions > Activity Control > Logs):
- Visualisation des 50 derniers événements
- Filtrage par type d'action
- Export disponible

Pour le signalement d'IP, consultez [IP_REPORTING_INTEGRATION.md](IP_REPORTING_INTEGRATION.md)

---

## 🔐 Sécurité

### Protection des données sensibles

⚠️ **Clé privée RSA**
- Stockée dans `data/private_key.pem` (permissions `600`)
- Répertoire `data/` protégé par `.htaccess`
- **Ne JAMAIS commit les fichiers `.pem` dans git**

### Signature cryptographique

- RSA 2048-bit
- PKCS#1 v1.5
- SHA-256
- Encodage base64

### Conformité RGPD

⚠️ **Données personnelles**
- Les IPs sont des données personnelles dans l'UE
- Documentez votre base légale
- Informez les utilisateurs dans votre politique de confidentialité
- Respectez les droits d'accès et de suppression

---

## 🏗️ Architecture technique

### Tables de base de données

- `phpbb_ac_logs` : Logs de toutes les actions
- `phpbb_ac_remote_ip_bans` : Gestion des IP bannies (prévu v1.3.0)

### Services principaux

- `linkguarder.activitycontrol.listener` : Event listener principal
- `linkguarder.activitycontrol.ip_reporter` : Signalement d'IP
- `linkguarder.activitycontrol.ip_ban_sync` : Synchronisation (squelette)

### Événements écoutés

- `core.submit_post_start` - Filtrage liens posts
- `core.ucp_profile_info_modify_sql_ary` - Filtrage signature/profil
- `core.member_register_after` - Attribution groupe initial
- `core.submit_post_end` - Mise à jour groupe
- Plus de 10 événements au total

Pour plus de détails, consultez [DOCS.md](DOCS.md)

---

## 📚 Documentation

- **[DOCS.md](DOCS.md)** - Documentation technique complète
- **[IP_REPORTING_INTEGRATION.md](IP_REPORTING_INTEGRATION.md)** - Signalement d'IP
- **[INTEGRATION_SUMMARY.md](INTEGRATION_SUMMARY.md)** - Résumé technique
- **[CHANGELOG.md](CHANGELOG.md)** - Historique des versions
- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Dépannage

---

## 🆘 Support

### Problèmes courants

**L'extension ne s'active pas**
```bash
# Vérifier PHP
php -v  # Doit être >= 7.4

# Vérifier OpenSSL
php -m | grep openssl
```

**Les liens ne sont pas filtrés**
- Les modérateurs/admins sont exemptés
- Vérifiez la configuration dans l'ACP
- Purgez le cache phpBB

**Erreur "Private key not found"**
```bash
# Générer les clés (voir IP_REPORTING_INTEGRATION.md)
chmod 600 data/private_key.pem
```

### Obtenir de l'aide

- 🐛 [Issues GitHub](https://github.com/linkguarder/activitycontrol/issues)
- 📖 [Wiki](https://github.com/linkguarder/activitycontrol/wiki)
- 📧 support@linkguarder.team

---

## 👨‍💻 Développement

```bash
# Clone et lien symbolique
git clone https://github.com/linkguarder/activitycontrol.git
ln -s /path/to/activitycontrol /path/to/phpbb/ext/linkguarder/activitycontrol

# Tests
phpunit tests/

# Conventions
# - PSR-12 pour le formatage
# - snake_case pour les configs
# - Docblocks obligatoires
```

**Contribuer:** Forkez → Branche → PR

---

## 📄 Licence

GPL-2.0-only © 2025 LinkGuarder Team

---

## 🙏 Crédits

- phpBB Team
- Communauté phpBB
- Tous les contributeurs

---

**🔐 Protégez votre communauté avec Activity Control**

*Développé avec ❤️ par LinkGuarder Team*
