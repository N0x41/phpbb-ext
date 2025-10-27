# Activity Control - Extension phpBB 3.3.x

Extension phpBB pour le filtrage de liens malicieux et le blocage d'IP dynamiques avec serveur central.

## Structure du projet

```
phpbb-ext/
├── activitycontrol/     # Code source de l'extension phpBB
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
├── docs/               # Documentation et fichiers de développement
│   ├── README.md              # Documentation principale
│   ├── DOCS.md                # Documentation détaillée
│   ├── API_ENDPOINT_DEBUG.md  # Debug des endpoints API
│   ├── NGINX_FIX.md           # Fix nginx pour phpBB 3.3.x
│   ├── TROUBLESHOOTING.md     # Guide de dépannage
│   └── *.sh, *.php            # Scripts de développement
│
└── sync_to_server.sh   # Script de synchronisation vers le serveur
```

## Installation sur serveur phpBB

L'extension doit être installée dans : `phpBB/ext/linkguarder/activitycontrol/`

### Méthode automatique

```bash
./sync_to_server.sh
```

### Méthode manuelle

```bash
rsync -av --delete activitycontrol/ /chemin/vers/phpbb/ext/linkguarder/activitycontrol/
```

## Configuration serveur central

L'URL du serveur central est codée en dur dans :
- `activitycontrol/service/ip_ban_sync.php` : `const CENTRAL_SERVER_URL = 'http://localhost:5000'`
- `activitycontrol/service/ip_reporter.php` : `const CENTRAL_SERVER_URL = 'http://localhost:5000'`

Pour changer l'URL, modifiez ces constantes et resynchronisez.

## API REST Endpoints

- **POST** `/app.php/ac_node_query` - Requêtes du serveur central
  - `{"query":"status"}` - Statut du nœud
  - `{"query":"stats"}` - Statistiques
  - `{"query":"sync_now"}` - Synchronisation forcée
  - `{"query":"local_ips"}` - Liste des IPs bannies localement
  - `{"query":"reported_ips"}` - Liste des IPs signalées

- **POST** `/app.php/notify` - Webhook de notification du serveur central

## Développement

Voir la documentation dans `docs/` pour :
- Configuration nginx (NGINX_FIX.md)
- Débogage API (API_ENDPOINT_DEBUG.md)
- Guide de dépannage (TROUBLESHOOTING.md)

## Git Workflow

```bash
# Faire des modifications dans activitycontrol/
cd /home/nox/Documents/phpbb-ext

# Commiter les changements
git add -A
git commit -m "Description des changements"

# Synchroniser vers le serveur
./sync_to_server.sh
```
