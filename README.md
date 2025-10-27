# LinkGuarder - Système complet de protection phpBB

Système complet de filtrage de liens malicieux et blocage d'IP dynamiques pour forums phpBB 3.3.x avec serveur central.

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Architecture](#architecture)
- [Composants](#composants)
- [Installation rapide](#installation-rapide)
- [Documentation](#documentation)

## Vue d'ensemble

LinkGuarder est un système complet composé de :

1. **RogueBB** - Serveur central Flask pour agréger et distribuer les listes d'IP
2. **Activity Control** - Extension phpBB pour filtrer les liens et bloquer les IP
3. **Communication bidirectionnelle** - API REST + Webhooks pour synchronisation temps réel

### Fonctionnalités

✅ Agrégation d'IP malveillantes depuis sources publiques (ipsum)  
✅ Filtrage automatique des liens pour utilisateurs à faible nombre de posts  
✅ Signalement d'IP suspectes au serveur central  
✅ Distribution automatique des listes d'IP aux forums  
✅ Notifications webhook en temps réel  
✅ Signatures RSA pour sécuriser les communications  
✅ Interface d'administration complète  

## Architecture

```
LinkGuarder/
├── roguebb/             # 🌐 Serveur central Flask
│   ├── server/         # Application serveur
│   ├── clients/        # Outils clients
│   └── docs/           # Documentation RogueBB
│
├── activitycontrol/     # 🔌 Extension phpBB
│   ├── acp/            # Module admin
│   ├── controller/     # API REST
│   ├── service/        # Services sync/report
│   └── ...             # Autres composants
│
└── docs/                # 📚 Documentation phpBB
```

## Composants

### 🌐 RogueBB - Serveur Central

Serveur Flask qui centralise la gestion des IP malveillantes.

**Fonctionnalités :**
- Agrégation d'IP depuis ipsum (mise à jour horaire)
- API REST pour distribution des listes
- Webhooks pour notifications automatiques
- Interface web de monitoring
- Gestion des nœuds connectés

**Documentation :** [roguebb/README.md](roguebb/README.md)

### 🔌 Activity Control - Extension phpBB

Extension phpBB pour filtrage de liens et blocage d'IP.

**Fonctionnalités :**
- Filtrage automatique des liens dans posts/signatures/profils
- Synchronisation automatique avec RogueBB
- Signalement d'IP suspectes
- Groupes utilisateurs (restreints/partiellement vérifiés/vérifiés)
- Module d'administration complet

**Documentation :** [activitycontrol/README.md](activitycontrol/README.md)

### 📚 Documentation

- **[docs/](docs/)** - Documentation de l'extension phpBB
- **[roguebb/docs/](roguebb/docs/)** - Documentation du serveur RogueBB

## Installation rapide

### 1. Installer le serveur RogueBB

```bash
cd roguebb/server/
pip install -r requirements.txt
python3 generate_keys.py
./roguebb.sh
```

Le serveur démarre sur `http://localhost:5000`

**Guide complet :** [roguebb/README.md](roguebb/README.md)

### 2. Installer l'extension phpBB

```bash
./sync_to_server.sh
```

Ou manuellement :
```bash
rsync -av --delete activitycontrol/ /chemin/vers/phpbb/ext/linkguarder/activitycontrol/
```

**Guide complet :** [activitycontrol/README.md](activitycontrol/README.md)

### 3. Configurer la connexion

Dans l'extension phpBB, l'URL du serveur est codée en dur :
- `activitycontrol/service/ip_ban_sync.php`
- `activitycontrol/service/ip_reporter.php`

```php
const CENTRAL_SERVER_URL = 'http://localhost:5000';
```

## Structure du projet

```
phpbb-ext/
├── roguebb/             # 🌐 Serveur central RogueBB
│   ├── server/         # Application Flask
│   │   ├── server.py          # Serveur principal
│   │   ├── requirements.txt   # Dépendances Python
│   │   ├── roguebb.sh        # Script démarrage
│   │   └── *.pem             # Clés RSA
│   ├── clients/        # Outils clients
│   │   ├── query_nodes.py     # Interroger nœuds
│   │   ├── manage_webhooks.py # Gérer webhooks
│   │   └── ...               # Autres outils
│   ├── docs/           # Documentation RogueBB
│   └── README.md       # README RogueBB
│
├── activitycontrol/     # 🔌 Extension phpBB
│   ├── acp/            # Module d'administration
│   ├── adm/            # Templates ACP
│   ├── config/         # Configuration (routing, services)
│   ├── controller/     # Contrôleurs API REST
│   ├── data/           # Données (reported_ips.json)
│   ├── event/          # Event listeners
│   ├── language/       # Fichiers de langue
│   ├── migrations/     # Migrations de base de données
│   ├── service/        # Services (ip_ban_sync, ip_reporter)
│   ├── styles/         # Templates et assets
│   ├── composer.json   # Métadonnées composer
│   └── ext.php         # Point d'entrée de l'extension
│
├── docs/               # 📚 Documentation phpBB
│   ├── README.md              # Index documentation phpBB
│   ├── DOCS.md                # Documentation détaillée
│   ├── API_ENDPOINT_DEBUG.md  # Debug des endpoints API
│   ├── NGINX_FIX.md           # Fix nginx pour phpBB 3.3.x
│   ├── TROUBLESHOOTING.md     # Guide de dépannage
│   └── *.sh, *.php            # Scripts de développement
│
├── sync_to_server.sh   # 🚀 Script de synchronisation
└── README.md           # ⬅️ Vous êtes ici
```

## Documentation

### 📚 Index complet de la documentation

- **[Documentation principale](docs/README.md)** - Index de toute la documentation phpBB
- **[Documentation RogueBB](roguebb/docs/INDEX.md)** - Index de la documentation serveur

### 🚀 Guides de démarrage rapide

| Composant | Guide | Description |
|-----------|-------|-------------|
| **RogueBB** | [roguebb/README.md](roguebb/README.md) | Installation et démarrage du serveur |
| **Extension phpBB** | [activitycontrol/README.md](activitycontrol/README.md) | Installation de l'extension |
| **API REST** | [docs/API_ENDPOINT_DEBUG.md](docs/API_ENDPOINT_DEBUG.md) | Debug des endpoints |

### 🔧 Documentation technique

#### Serveur RogueBB
- [QUICK_START.md](roguebb/docs/QUICK_START.md) - Démarrage rapide
- [SYSTEME_COMPLET.md](roguebb/docs/SYSTEME_COMPLET.md) - Vue d'ensemble système
- [NODE_QUERY_GUIDE.md](roguebb/docs/NODE_QUERY_GUIDE.md) - Guide requêtes nœuds
- [WEBHOOKS_GUIDE.md](roguebb/docs/WEBHOOKS_GUIDE.md) - Guide webhooks
- [SECURITY.md](roguebb/docs/SECURITY.md) - Sécurité RSA

#### Extension phpBB
- [NGINX_FIX.md](docs/NGINX_FIX.md) - Configuration nginx pour API REST
- [TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) - Guide de dépannage
- [IP_SYNC_GUIDE.md](docs/IP_SYNC_GUIDE.md) - Guide synchronisation IP

## API REST Endpoints

### Serveur RogueBB (port 5000)

- `GET /` - Interface web
- `GET /api/ip_list` - Liste des IP (JSON)
- `POST /api/submit_ip` - Soumettre une IP
- `GET /api/stats` - Statistiques
- `GET /api/nodes` - Liste des nœuds

### Extension phpBB

- `POST /app.php/ac_node_query` - Requêtes du serveur central
  - `{"query":"status"}` - Statut du nœud
  - `{"query":"stats"}` - Statistiques
  - `{"query":"sync_now"}` - Synchronisation forcée
  - `{"query":"local_ips"}` - IPs bannies localement
  - `{"query":"reported_ips"}` - IPs signalées

- `POST /app.php/notify` - Webhook de notification

## Outils clients

### query_nodes.py - Interroger les nœuds

```bash
cd roguebb/clients/
python3 query_nodes.py status --node http://forum.com/app.php/ac_node_query
python3 query_nodes.py stats --node http://forum.com/app.php/ac_node_query
```

### manage_webhooks.py - Gérer les webhooks

```bash
cd roguebb/clients/
python3 manage_webhooks.py list
python3 manage_webhooks.py add http://forum.com/app.php/notify
```

### get_ip_list.py - Récupérer la liste d'IP

```bash
cd roguebb/clients/
python3 get_ip_list.py
```

## Configuration serveur central

L'URL du serveur central est codée en dur dans :
- `activitycontrol/service/ip_ban_sync.php`
- `activitycontrol/service/ip_reporter.php`

```php
const CENTRAL_SERVER_URL = 'http://localhost:5000';
```

Pour changer l'URL, modifiez ces constantes et resynchronisez avec `./sync_to_server.sh`

## Workflow de développement

```bash
# 1. Faire des modifications dans activitycontrol/ ou roguebb/
cd /home/nox/Documents/phpbb-ext

# 2. Tester localement
cd roguebb/server && ./roguebb.sh  # Démarrer serveur
./sync_to_server.sh                # Synchroniser extension

# 3. Commiter les changements
git add -A
git commit -m "Description des changements"

# 4. Resynchroniser si nécessaire
./sync_to_server.sh
```

## Architecture technique

### Communication entre composants

```
┌─────────────────┐         ┌──────────────────┐
│  RogueBB Server │◄────────┤ ipsum (GitHub)   │
│  (Flask :5000)  │         └──────────────────┘
└────────┬────────┘
         │
         │ ① Synchronisation horaire
         │ ② Notification webhook
         │ ③ Requêtes node_query
         │
         ▼
┌─────────────────────────────────┐
│  phpBB Forums                   │
│  ┌───────────────────────────┐  │
│  │ Activity Control Extension│  │
│  │  • ip_ban_sync.php       │  │
│  │  • ip_reporter.php       │  │
│  │  • API REST controller    │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

### Flux de données

1. **Agrégation** : RogueBB récupère les IP depuis ipsum toutes les heures
2. **Signalement** : Forums signalent les IP suspectes à RogueBB
3. **Distribution** : RogueBB notifie les forums via webhooks
4. **Synchronisation** : Forums récupèrent et appliquent la liste d'IP
5. **Requêtes** : RogueBB peut interroger l'état des forums

## Sécurité

- **Signatures RSA** : Toutes les soumissions d'IP sont signées
- **Vérification** : Le serveur vérifie les signatures avant d'accepter
- **HTTPS recommandé** : En production, utilisez HTTPS
- **Clés séparées** : Chaque forum a sa propre paire de clés

Voir [roguebb/docs/SECURITY.md](roguebb/docs/SECURITY.md) pour plus de détails.

## Dépannage

### Le serveur RogueBB ne démarre pas

```bash
cd roguebb/server/
pip install -r requirements.txt
python3 server.py
```

### L'API REST phpBB retourne du HTML

Voir [docs/NGINX_FIX.md](docs/NGINX_FIX.md) - Vérifier que nginx utilise `app.php` et non `index.php`

### La synchronisation échoue

1. Vérifier que RogueBB est démarré : `curl http://localhost:5000/api/stats`
2. Vérifier les logs : `roguebb/server/server.log`
3. Vérifier la configuration ACP de l'extension

Documentation complète : [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)

## Support et contribution

### Documentation

- **Index général** : [docs/README.md](docs/README.md)
- **Index RogueBB** : [roguebb/docs/INDEX.md](roguebb/docs/INDEX.md)

### Liens utiles

| Ressource | Lien |
|-----------|------|
| Extension phpBB | [activitycontrol/README.md](activitycontrol/README.md) |
| Serveur RogueBB | [roguebb/README.md](roguebb/README.md) |
| API Debug | [docs/API_ENDPOINT_DEBUG.md](docs/API_ENDPOINT_DEBUG.md) |
| Guide nginx | [docs/NGINX_FIX.md](docs/NGINX_FIX.md) |
| Sécurité | [roguebb/docs/SECURITY.md](roguebb/docs/SECURITY.md) |

---

**Projet** : LinkGuarder Activity Control  
**Version** : 1.0.0  
**Dernière mise à jour** : 27 octobre 2025

