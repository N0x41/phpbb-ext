# Index de la Documentation Activity Control (phpBB)

[← Retour au README principal](../README.md) | [→ Documentation RogueBB](../roguebb/docs/INDEX.md)

## 📚 Documentation de l'extension phpBB Activity Control

Cette page centralise toute la documentation de l'extension phpBB.

---

## 🚀 Démarrage rapide

### Pour les nouveaux utilisateurs
1. **[README.md](README.md)** - Vue d'ensemble et installation
2. **[NGINX_FIX.md](NGINX_FIX.md)** - ⚠️ Configuration nginx (IMPORTANT pour API REST)
3. **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Guide de dépannage

---

## 🔧 Configuration et installation

### Installation
- **[README.md](README.md)** - Installation de base
- **[NGINX_FIX.md](NGINX_FIX.md)** - Configuration nginx pour phpBB 3.3.x
  - Fix pour routing API REST
  - Utilisation de `app.php` au lieu de `index.php`

### Intégration
- **[IP_SYNC_GUIDE.md](IP_SYNC_GUIDE.md)** - Guide de synchronisation IP
- **[IP_REPORTING_INTEGRATION.md](IP_REPORTING_INTEGRATION.md)** - Intégration du signalement d'IP
- **[INTEGRATION_SUMMARY.md](INTEGRATION_SUMMARY.md)** - Résumé de l'intégration

---

## 🔌 API REST

### Debug et développement
- **[API_ENDPOINT_DEBUG.md](API_ENDPOINT_DEBUG.md)** - Debug des endpoints API
  - Diagnostic des problèmes
  - Tests avec curl
  - Vérification routing
  
- **[API_FIX_SOLUTION.md](API_FIX_SOLUTION.md)** - Solutions aux problèmes API

### Documentation technique
- **[DOCS.md](DOCS.md)** - Documentation technique détaillée

---

## 🔄 Maintenance et mises à jour

### Historique
- **[CHANGELOG.md](CHANGELOG.md)** - Journal des modifications
- **[CORRECTIONS_SUMMARY.md](CORRECTIONS_SUMMARY.md)** - Résumé des corrections
- **[RESET_SUMMARY.md](RESET_SUMMARY.md)** - Résumé des réinitialisations

---

## 🛠️ Scripts et outils

### Scripts de développement
- **[check_extension_status.php](check_extension_status.php)** - Vérifier le statut de l'extension
- **[reload_extension.php](reload_extension.php)** - Recharger l'extension
- **[quick_reload.php](quick_reload.php)** - Rechargement rapide

### Scripts de test
- **[test_api.sh](test_api.sh)** - Tester les endpoints API

### Scripts de maintenance
- **[cleanup_migrations.sql](cleanup_migrations.sql)** - Nettoyer les migrations

---

## 🐛 Dépannage

### Problèmes courants

| Problème | Solution |
|----------|----------|
| API retourne du HTML | [NGINX_FIX.md](NGINX_FIX.md) |
| Synchronisation échoue | [TROUBLESHOOTING.md](TROUBLESHOOTING.md) |
| Extension non visible | [check_extension_status.php](check_extension_status.php) |
| Erreurs de cache | [TROUBLESHOOTING.md](TROUBLESHOOTING.md) |

### Documentation de dépannage
- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Guide complet de dépannage
  - Problèmes d'API
  - Problèmes de cache
  - Problèmes de permissions
  - Problèmes de synchronisation

---

## 📂 Organisation de la documentation

```
docs/
├── INDEX.md                      ← Vous êtes ici
│
├── 🚀 Installation
│   ├── README.md                (Vue d'ensemble)
│   └── NGINX_FIX.md             (⚠️ Important)
│
├── 🔌 API
│   ├── API_ENDPOINT_DEBUG.md    (Debug)
│   ├── API_FIX_SOLUTION.md      (Solutions)
│   └── DOCS.md                  (Technique)
│
├── 🔄 Intégration
│   ├── IP_SYNC_GUIDE.md         (Sync IP)
│   ├── IP_REPORTING_INTEGRATION.md
│   └── INTEGRATION_SUMMARY.md   (Résumé)
│
├── 🔧 Maintenance
│   ├── CHANGELOG.md             (Historique)
│   ├── CORRECTIONS_SUMMARY.md   (Corrections)
│   └── RESET_SUMMARY.md         (Réinitialisations)
│
├── 🛠️ Scripts
│   ├── check_extension_status.php
│   ├── reload_extension.php
│   ├── quick_reload.php
│   ├── test_api.sh
│   └── cleanup_migrations.sql
│
└── 🐛 Dépannage
    └── TROUBLESHOOTING.md       (Guide complet)
```

---

## 🔗 Liens rapides

### Projet complet
- [README principal](../README.md)
- [Extension Activity Control](../activitycontrol/README.md)
- [Serveur RogueBB](../roguebb/README.md)
- [Documentation RogueBB](../roguebb/docs/INDEX.md)

### Par cas d'usage

| Je veux... | Consultez |
|------------|-----------|
| Installer l'extension | [README.md](README.md) |
| Configurer nginx | [NGINX_FIX.md](NGINX_FIX.md) |
| Débugger l'API | [API_ENDPOINT_DEBUG.md](API_ENDPOINT_DEBUG.md) |
| Résoudre un problème | [TROUBLESHOOTING.md](TROUBLESHOOTING.md) |
| Voir l'historique | [CHANGELOG.md](CHANGELOG.md) |
| Tester l'API | [test_api.sh](test_api.sh) |

---

## 📝 Flux de travail recommandé

### 1. Installation initiale
```bash
# Lire la documentation
cat README.md

# Configurer nginx
cat NGINX_FIX.md

# Synchroniser l'extension
../sync_to_server.sh

# Vérifier le statut
php check_extension_status.php
```

### 2. Configuration
```bash
# Activer dans l'ACP phpBB
# Extensions > Activity Control

# Configurer les paramètres
# ACP > Extensions > Activity Control > Settings
```

### 3. Test
```bash
# Tester l'API
bash test_api.sh

# Vérifier les logs
tail -f /path/to/phpbb/cache/logs/*.log
```

### 4. Dépannage si nécessaire
```bash
# Consulter le guide
cat TROUBLESHOOTING.md

# Recharger l'extension
php reload_extension.php
```

---

## 📊 Diagramme de navigation

```
                    README principal
                          │
          ┌───────────────┼───────────────┐
          │               │               │
    Extension phpBB   Serveur RogueBB   Documentation
          │               │               │
          │               │               ├─ docs/INDEX.md (ici)
          │               │               └─ roguebb/docs/INDEX.md
          │               │
          ├─ Installation ├─ Installation
          ├─ API          ├─ API
          ├─ Config       ├─ Webhooks
          └─ Dépannage    └─ Clients
```

---

## 📝 Navigation

- **← Précédent** : [README principal](../README.md)
- **→ Suivant** : [Documentation RogueBB](../roguebb/docs/INDEX.md)
- **↑ Niveau supérieur** : [README principal](../README.md)

---

**Dernière mise à jour** : 27 octobre 2025  
**Version** : 1.0.0  
**Extension** : Activity Control pour phpBB 3.3.x
