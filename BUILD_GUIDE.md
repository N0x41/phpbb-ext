# Build Release Script - Guide d'utilisation

[← Retour au README principal](README.md)

## 📦 Script de préparation de release

Le script `build_release.sh` automatise la création d'un package ZIP prêt pour publication de l'extension Activity Control.

## 🚀 Utilisation

### Commande basique

```bash
./build_release.sh
```

Le script vous demandera l'URL du serveur central RogueBB à configurer.

### Processus interactif

```
╔════════════════════════════════════════════════════════╗
║   LinkGuarder Activity Control - Build Release        ║
║   Version: 1.0.0                                       ║
╚════════════════════════════════════════════════════════╝

════════════════════════════════════════════════════════
Configuration du serveur central RogueBB
════════════════════════════════════════════════════════

Entrez l'URL du serveur central RogueBB pour cette release
Exemples:
  - http://localhost:5000 (développement)
  - http://votre-serveur.com:5000 (production)
  - https://roguebb.example.com (production avec SSL)

URL du serveur central [http://localhost:5000]: _
```

## 📋 Ce que fait le script

### 1. Préparation
- ✅ Crée les dossiers `build/` et `release/`
- ✅ Copie l'extension complète
- ✅ Nettoie les fichiers temporaires

### 2. Configuration
- ✅ Demande l'URL du serveur central
- ✅ Configure automatiquement `ip_ban_sync.php`
- ✅ Configure automatiquement `ip_reporter.php`
- ✅ Vérifie que les modifications sont appliquées

### 3. Documentation
- ✅ Génère `README_INSTALLATION.txt`
- ✅ Génère `CHANGELOG.txt`
- ✅ Génère `ROGUEBB_CONFIG.txt`
- ✅ Génère `metadata.json`

### 4. Packaging
- ✅ Crée l'archive ZIP
- ✅ Calcule le hash SHA256
- ✅ Génère les métadonnées JSON

## 📦 Contenu du package

```
linkguarder_activitycontrol_1.0.0.zip
├── README_INSTALLATION.txt       # Guide d'installation
├── CHANGELOG.txt                 # Liste des changements
├── ROGUEBB_CONFIG.txt           # Configuration RogueBB
└── activitycontrol/             # Extension complète
    ├── acp/                     # Module admin
    ├── adm/                     # Templates ACP
    ├── config/                  # Configuration
    ├── controller/              # API REST
    ├── data/                    # Données
    ├── event/                   # Event listeners
    ├── language/                # Traductions
    ├── migrations/              # Migrations DB
    ├── service/                 # Services (configurés)
    ├── composer.json
    └── ext.php
```

## 📁 Fichiers générés

Dans le dossier `release/` :

```
release/
├── linkguarder_activitycontrol_1.0.0.zip           # Package principal
├── linkguarder_activitycontrol_1.0.0.zip.sha256   # Hash de vérification
└── linkguarder_activitycontrol_1.0.0.json         # Métadonnées
```

## 🔧 Configuration automatique

Le script modifie automatiquement ces fichiers avec l'URL fournie :

### Avant (dans le code source)
```php
const CENTRAL_SERVER_URL = 'http://localhost:5000';
```

### Après (dans le package)
```php
const CENTRAL_SERVER_URL = 'https://votre-serveur.com:5000';
```

**Fichiers modifiés :**
- `activitycontrol/service/ip_ban_sync.php`
- `activitycontrol/service/ip_reporter.php`

## 📝 Exemples d'utilisation

### Build pour développement local

```bash
./build_release.sh
# Entrer: http://localhost:5000
```

### Build pour production

```bash
./build_release.sh
# Entrer: https://roguebb.example.com
```

### Build avec serveur sur port personnalisé

```bash
./build_release.sh
# Entrer: http://mon-serveur.com:8080
```

## ✅ Vérification du package

Après la création, le script affiche :

```
╔════════════════════════════════════════════════════════╗
║   Build terminé avec succès !                          ║
╚════════════════════════════════════════════════════════╝

Package créé :
  📦 release/linkguarder_activitycontrol_1.0.0.zip

Fichiers inclus :
  📄 README_INSTALLATION.txt
  📄 CHANGELOG.txt
  📄 ROGUEBB_CONFIG.txt
  📁 activitycontrol/ (extension complète)

Vérification :
  🔒 SHA256 : abc123...
  💾 Taille : 45K

Configuration :
  🌐 Serveur central : https://votre-serveur.com

Prochaines étapes :
  1. Tester l'installation du package
  2. Publier sur GitHub Releases
  3. Mettre à jour la documentation
```

## 🧪 Test du package

### 1. Extraire le ZIP
```bash
cd /tmp
unzip linkguarder_activitycontrol_1.0.0.zip
```

### 2. Vérifier la configuration
```bash
grep "CENTRAL_SERVER_URL" activitycontrol/service/ip_ban_sync.php
grep "CENTRAL_SERVER_URL" activitycontrol/service/ip_reporter.php
```

### 3. Vérifier le hash
```bash
sha256sum linkguarder_activitycontrol_1.0.0.zip
cat linkguarder_activitycontrol_1.0.0.zip.sha256
```

## 📤 Publication

### Sur GitHub Releases

1. Créer une nouvelle release
2. Tag : `v1.0.0`
3. Titre : `LinkGuarder Activity Control v1.0.0`
4. Description : Copier le contenu de `CHANGELOG.txt`
5. Attacher les fichiers :
   - `linkguarder_activitycontrol_1.0.0.zip`
   - `linkguarder_activitycontrol_1.0.0.zip.sha256`
   - `linkguarder_activitycontrol_1.0.0.json`

### Informations à inclure

```markdown
## 📦 Installation

1. Télécharger `linkguarder_activitycontrol_1.0.0.zip`
2. Extraire dans `phpBB/ext/linkguarder/`
3. Activer depuis l'ACP

## 🔒 Vérification

SHA256: `abc123...`

## ⚙️ Configuration

Cette release est préconfigurée avec :
- Serveur central : `https://votre-serveur.com`

Pour changer l'URL plus tard, voir `README_INSTALLATION.txt`

## 📚 Documentation

- [Guide d'installation](README_INSTALLATION.txt)
- [Configuration RogueBB](ROGUEBB_CONFIG.txt)
- [Changelog](CHANGELOG.txt)
```

## 🛠️ Dépannage

### Le script ne démarre pas

```bash
chmod +x build_release.sh
```

### sed: command not found

Installer sed :
```bash
# Debian/Ubuntu
sudo apt install sed

# macOS
brew install gnu-sed
```

### Permission denied sur les dossiers

```bash
rm -rf build/ release/
./build_release.sh
```

## 🔄 Workflow complet

### Préparation d'une nouvelle release

```bash
# 1. Mettre à jour la version
# Éditer build_release.sh et changer VERSION="1.0.0"

# 2. Mettre à jour le code si nécessaire
git add -A
git commit -m "Prepare v1.0.0 release"

# 3. Build la release
./build_release.sh
# Entrer l'URL du serveur

# 4. Tester le package
cd /tmp
unzip ~/Documents/phpbb-ext/release/linkguarder_activitycontrol_1.0.0.zip

# 5. Si OK, créer le tag git
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0

# 6. Publier sur GitHub Releases
# Attacher les fichiers du dossier release/
```

## 📊 Structure de développement vs release

### Développement
```
phpbb-ext/
├── activitycontrol/          # Code source (localhost:5000)
├── roguebb/                  # Serveur central
└── build_release.sh          # Script de build
```

### Release
```
linkguarder_activitycontrol_1.0.0.zip
└── activitycontrol/          # Configuré avec URL production
```

## 💡 Conseils

### Releases multiples

Pour créer plusieurs releases avec différentes configurations :

```bash
# Release 1 : Développement
./build_release.sh
# URL: http://localhost:5000
mv release release-dev

# Release 2 : Production
./build_release.sh
# URL: https://roguebb.example.com
mv release release-prod
```

### Automatisation

Pour automatiser complètement :

```bash
#!/bin/bash
echo "https://roguebb.example.com" | ./build_release.sh
```

### CI/CD

Exemple pour GitHub Actions :

```yaml
- name: Build Release
  run: |
    echo "${{ secrets.CENTRAL_SERVER_URL }}" | ./build_release.sh
    
- name: Upload Release Asset
  uses: actions/upload-release-asset@v1
  with:
    upload_url: ${{ steps.create_release.outputs.upload_url }}
    asset_path: ./release/linkguarder_activitycontrol_1.0.0.zip
    asset_name: linkguarder_activitycontrol_1.0.0.zip
    asset_content_type: application/zip
```

## 📞 Support

- **Documentation** : [README.md](README.md)
- **Issues** : GitHub Issues
- **Discord** : Support communautaire

---

[← Retour au README principal](README.md) | [📚 Documentation complète](docs/INDEX.md)

**Dernière mise à jour** : 27 octobre 2025
