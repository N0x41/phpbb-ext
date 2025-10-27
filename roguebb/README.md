# RogueBB - Serveur Central de Gestion d'IP

[← Retour au README principal](../README.md)

Serveur central Flask pour la gestion et la distribution de listes d'IP malveillantes à travers un réseau de forums phpBB.

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Architecture](#architecture)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Documentation](#documentation)
- [Outils clients](#outils-clients)

## Vue d'ensemble

RogueBB est un serveur central qui :
- ✅ Agrège les IP malveillantes depuis des sources publiques (ipsum)
- ✅ Collecte les signalements d'IP des nœuds phpBB
- ✅ Distribue les listes d'IP aux forums connectés
- ✅ Notifie automatiquement les nœuds via webhooks
- ✅ Fournit une API REST pour interroger les nœuds

## Architecture

```
roguebb/
├── server/          # Serveur central Flask
│   ├── server.py           # Application Flask principale
│   ├── requirements.txt    # Dépendances Python
│   ├── roguebb.sh         # Script de démarrage
│   ├── generate_keys.py    # Génération clés RSA
│   ├── private_key.pem     # Clé privée RSA
│   └── public_key.pem      # Clé publique RSA
│
├── clients/         # Outils clients
│   ├── query_nodes.py      # Interroger les nœuds phpBB
│   ├── manage_webhooks.py  # Gérer les webhooks
│   ├── get_ip_list.py      # Récupérer la liste d'IP
│   ├── batch_submit_ips.py # Soumettre des IP en lot
│   ├── client_example.py   # Exemple client Python
│   └── client_example.php  # Exemple client PHP
│
├── docs/            # Documentation
│   └── *.md               # Guides et documentation
│
└── README.md        # Ce fichier
```

## Installation

### Prérequis

- Python 3.8+
- pip

### Installation des dépendances

```bash
cd server/
pip install -r requirements.txt
```

### Génération des clés RSA

```bash
cd server/
python3 generate_keys.py
```

Cela créera `private_key.pem` (serveur) et `public_key.pem` (à distribuer aux nœuds).

## Utilisation

### Démarrer le serveur

```bash
cd server/
./roguebb.sh
```

Ou manuellement :
```bash
python3 server.py
```

Le serveur démarre sur `http://localhost:5000`

### Configuration

Éditez `server.py` pour configurer :

```python
# URL de la source d'IP
IP_SOURCE_URL = "https://raw.githubusercontent.com/stamparm/ipsum/master/levels/3.txt"

# Intervalle de mise à jour (secondes)
UPDATE_INTERVAL_SECONDS = 3600  # 1 heure

# URLs des webhooks
WEBHOOK_URLS = [
    "http://votre-forum.com/app.php/notify"
]
```

## Documentation

### Guides principaux

- **[QUICK_START.md](docs/QUICK_START.md)** - Guide de démarrage rapide
- **[GUIDE_UTILISATION.md](docs/GUIDE_UTILISATION.md)** - Guide d'utilisation complet
- **[SECURITY.md](docs/SECURITY.md)** - Sécurité et signatures RSA

### API et intégration

- **[NODE_QUERY_GUIDE.md](docs/NODE_QUERY_GUIDE.md)** - Guide des requêtes de nœuds
- **[WEBHOOKS_GUIDE.md](docs/WEBHOOKS_GUIDE.md)** - Guide des webhooks
- **[CLIENT_PHP.md](docs/CLIENT_PHP.md)** - Client PHP exemple

### Références rapides

- **[QUICKREF.md](docs/QUICKREF.md)** - Référence rapide API
- **[QUERY_QUICKREF.md](docs/QUERY_QUICKREF.md)** - Référence requêtes nœuds

### Documentation complète

- **[INDEX.md](docs/INDEX.md)** - Index de toute la documentation

## Outils clients

### query_nodes.py - Interroger les nœuds

```bash
cd clients/
python3 query_nodes.py status --node http://forum.com/app.php/ac_node_query
python3 query_nodes.py stats --node http://forum.com/app.php/ac_node_query
```

### manage_webhooks.py - Gérer les webhooks

```bash
cd clients/
python3 manage_webhooks.py list
python3 manage_webhooks.py add http://forum.com/app.php/notify
python3 manage_webhooks.py remove http://forum.com/app.php/notify
```

### get_ip_list.py - Récupérer la liste d'IP

```bash
cd clients/
python3 get_ip_list.py
```

### batch_submit_ips.py - Soumettre des IP

```bash
cd clients/
python3 batch_submit_ips.py file.txt "raison du signalement"
```

## API Endpoints

### Endpoints du serveur central

- `GET /` - Interface web
- `GET /api/ip_list` - Liste des IP (JSON)
- `POST /api/submit_ip` - Soumettre une IP
- `GET /api/stats` - Statistiques
- `GET /api/nodes` - Liste des nœuds enregistrés

### Endpoints des nœuds phpBB

Voir [../activitycontrol/README.md](../activitycontrol/README.md) pour les endpoints phpBB.

## Intégration avec phpBB

L'extension phpBB Activity Control se connecte automatiquement à RogueBB :

1. **Synchronisation des IP** : Les nœuds récupèrent la liste toutes les heures
2. **Signalement d'IP** : Les nœuds signalent les IP suspectes au serveur
3. **Notifications webhook** : Le serveur notifie les nœuds des mises à jour

Voir la documentation de l'extension : [../activitycontrol/README.md](../activitycontrol/README.md)

## Développement

### Structure du code serveur

- `server.py` - Application Flask principale
  - Agrégation des IP depuis ipsum
  - API REST pour nœuds et clients
  - Système de webhooks
  - Interface web de monitoring

### Logs

Les logs du serveur sont dans `server/server.log`

## Support et contribution

Pour plus d'informations, consultez la [documentation complète](docs/INDEX.md).

---

[← Retour au README principal](../README.md) | [Documentation complète →](docs/INDEX.md)
