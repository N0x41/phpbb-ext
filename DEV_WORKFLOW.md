# Workflow de Développement - ActivityControl Extension

## 🎯 Structure du Projet

```
/home/nox/Documents/phpbb-ext/               # Dépôt Git principal
├── activitycontrol/                          # Code source de l'extension
│   ├── acp/
│   ├── service/
│   └── ...
├── roguebb/                                  # Serveur central
│   └── server/
│       └── server.py
└── sync_dev.sh                               # Script de synchronisation

/home/nox/Documents/NiMP/var/www/forum/      # Installation phpBB
└── ext/linkguarder/activitycontrol/          # Extension déployée
```

## 🔄 Synchronisation Automatique

### Mode Watch (Recommandé)

**Terminal 1 - Serveur RogueBB:**
```bash
cd ~/Documents/phpbb-ext/roguebb/server
source /home/nox/Documents/.venv/bin/activate
python3 server.py
```

**Terminal 2 - Auto-sync:**
```bash
cd ~/Documents/phpbb-ext
./sync_dev.sh watch
```

**Terminal 3 - Développement:**
```bash
cd ~/Documents/phpbb-ext/activitycontrol
# Éditer les fichiers avec votre éditeur favori
```

✨ **Magie:** Chaque modification est automatiquement:
1. Détectée par inotifywait
2. Synchronisée vers phpBB
3. Cache vidé
4. Prête à tester dans le navigateur

### Synchronisation Manuelle

**Du repo vers phpBB:**
```bash
./sync_dev.sh to-phpbb
```

**De phpBB vers le repo:**
```bash
./sync_dev.sh from-phpbb
```

## 📝 Workflow de Développement

### 1. Démarrer une session de dev

```bash
# Terminal 1: Serveur RogueBB
cd ~/Documents/phpbb-ext/roguebb/server
source /home/nox/Documents/.venv/bin/activate
python3 server.py

# Terminal 2: Auto-sync
cd ~/Documents/phpbb-ext
./sync_dev.sh watch
```

### 2. Développer

- Éditer dans: `/home/nox/Documents/phpbb-ext/activitycontrol/`
- Les changements sont auto-sync vers phpBB
- Tester dans le navigateur: http://localhost/forum/

### 3. Commiter

```bash
cd ~/Documents/phpbb-ext
git add activitycontrol/
git commit -m "Description des changements"
git push
```

## 🐛 Debugging

### Vider le cache manuellement
```bash
rm -rf ~/Documents/NiMP/var/www/forum/cache/*
```

### Logs phpBB
```bash
tail -f ~/Documents/NiMP/var/www/forum/phpbb.log
```

### Logs serveur RogueBB
Visible dans le terminal où `server.py` tourne

### Recréer l'extension proprement
```bash
# 1. Supprimer dans phpBB
rm -rf ~/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol

# 2. Resynchroniser
cd ~/Documents/phpbb-ext
./sync_dev.sh to-phpbb

# 3. Vider le cache
rm -rf ~/Documents/NiMP/var/www/forum/cache/*
```

## 🔧 Configuration

### Serveur RogueBB
- URL: `http://192.168.1.2:5000`
- Endpoints:
  - `/api/register` - Enregistrement d'un node
  - `/api/health` - Health check
  - `/api/get_ips` - Récupération manuelle des IPs
  - `/notify` - Réception de la liste d'IPs

### Extension phpBB
- Namespace: `linkguarder\activitycontrol`
- Emplacement: `ext/linkguarder/activitycontrol`
- ACP: Extensions → Activity Control

## 📦 Fichiers Importants

### Services (`config/services.yml`)
- `linkguarder.activitycontrol.ip_ban_sync` - Synchronisation IP
- `linkguarder.activitycontrol.server_registration` - Enregistrement RogueBB
- `linkguarder.activitycontrol.ip_reporter` - Signalement d'IPs

### Controllers
- `controller/main.php` - Endpoint `/notify`
- `acp/main_module.php` - Interface ACP

### Services
- `service/ip_ban_sync.php` - Gestion synchronisation
- `service/server_registration.php` - Auto-registration
- `service/server_authenticator.php` - Vérification RSA

## 🚀 Astuces

### Watch multiple dossiers
Le mode watch surveille automatiquement tous les sous-dossiers de `activitycontrol/`

### Ignorer des fichiers
Modifiez `sync_dev.sh` pour ajouter des exclusions:
```bash
--exclude='*.backup' \
--exclude='.DS_Store' \
--exclude='votre_fichier'
```

### Performance
Si la synchronisation est lente, ajoutez `--checksum` à rsync pour comparer uniquement les fichiers modifiés

## ⚠️ Important

- **Ne jamais éditer** directement dans `/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/`
- **Toujours éditer** dans `/home/nox/Documents/phpbb-ext/activitycontrol/`
- Le mode watch **vide automatiquement le cache** après chaque sync
- Utilisez `from-phpbb` uniquement si vous avez modifié des fichiers manuellement dans phpBB

## 📚 Documentation

- [phpBB Extension Development](https://area51.phpbb.com/docs/dev/3.3.x/extensions/)
- [RogueBB API](./roguebb/docs/README.md)
- [Troubleshooting](./docs/TROUBLESHOOTING.md)
