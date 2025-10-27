# Guide du système de requêtes aux nœuds

## Vue d'ensemble

Le système de requêtes aux nœuds permet au serveur RogueBB d'interroger les nœuds phpBB connectés pour obtenir des informations en temps réel ou déclencher des actions.

## Architecture

```
┌─────────────────┐                    ┌──────────────────┐
│  Serveur        │                    │  Nœud phpBB 1    │
│  RogueBB        │◄──────────────────►│  (Forum)         │
│                 │   Requêtes/        │                  │
│  query_nodes.py │   Réponses JSON    │  main.php        │
└─────────────────┘                    │  node_query()    │
                                       └──────────────────┘
        │                                     
        │                              ┌──────────────────┐
        └─────────────────────────────►│  Nœud phpBB 2    │
                                       │  (Forum)         │
                                       │                  │
                                       │  main.php        │
                                       │  node_query()    │
                                       └──────────────────┘
```

## Types de requêtes

### 1. Status - Statut et configuration

Obtient le statut et la configuration d'un nœud.

**Commande:**
```bash
./roguebb.sh query status
python3 query_nodes.py status
```

**Réponse:**
```json
{
  "status": "ok",
  "node_type": "phpbb_forum",
  "forum_name": "Mon Forum",
  "phpbb_version": "3.3.11",
  "extension_version": "1.0.1",
  "sync_enabled": true,
  "reporting_enabled": true,
  "last_sync": 1704110400,
  "ip_list_version": 42,
  "timestamp": 1704111000
}
```

**Informations retournées:**
- `status`: État du nœud (ok/error)
- `node_type`: Type de nœud (phpbb_forum)
- `forum_name`: Nom du forum
- `phpbb_version`: Version de phpBB
- `extension_version`: Version de l'extension Activity Control
- `sync_enabled`: Synchronisation activée (true/false)
- `reporting_enabled`: Signalement d'IPs activé (true/false)
- `last_sync`: Timestamp de la dernière synchronisation
- `ip_list_version`: Version de la liste d'IPs synchronisée
- `timestamp`: Timestamp de la réponse

### 2. Stats - Statistiques du forum

Obtient les statistiques du forum.

**Commande:**
```bash
./roguebb.sh query stats
python3 query_nodes.py stats
```

**Réponse:**
```json
{
  "status": "ok",
  "stats": {
    "banned_ips": 1247,
    "total_users": 5432,
    "total_posts": 98765,
    "total_topics": 12345,
    "last_sync": 1704110400,
    "ip_list_version": 42
  },
  "timestamp": 1704111000
}
```

**Informations retournées:**
- `banned_ips`: Nombre d'IPs bannies sur ce forum
- `total_users`: Nombre total d'utilisateurs
- `total_posts`: Nombre total de messages
- `total_topics`: Nombre total de sujets
- `last_sync`: Timestamp de la dernière synchronisation
- `ip_list_version`: Version de la liste d'IPs

### 3. Sync Now - Synchronisation immédiate

Déclenche une synchronisation immédiate des IPs.

**Commande:**
```bash
./roguebb.sh query sync-now
python3 query_nodes.py sync_now
```

**Réponse:**
```json
{
  "status": "ok",
  "message": "Synchronisation complétée avec succès",
  "stats": {
    "added": 15,
    "removed": 3,
    "total": 1259
  },
  "timestamp": 1704111000
}
```

**Informations retournées:**
- `message`: Message de résultat
- `stats.added`: Nombre d'IPs ajoutées
- `stats.removed`: Nombre d'IPs retirées
- `stats.total`: Total d'IPs après synchronisation

**Note:** Cette requête déclenche une synchronisation immédiate avec RogueBB et journalise l'action dans les logs d'administration du forum.

### 4. Local IPs - IPs bannies localement

Obtient la liste des IPs bannies localement sur le forum.

**Commande:**
```bash
./roguebb.sh query local-ips
python3 query_nodes.py local_ips
```

**Réponse:**
```json
{
  "status": "ok",
  "count": 1247,
  "ips": [
    "192.168.1.100",
    "10.0.0.50",
    "..."
  ],
  "note": "Limited to first 100 IPs",
  "timestamp": 1704111000
}
```

**Informations retournées:**
- `count`: Nombre total d'IPs bannies
- `ips`: Tableau des IPs (limité à 100)
- `note`: Note sur la limitation

### 5. Reported IPs - IPs signalées par le nœud

Obtient la liste des IPs signalées par ce nœud à RogueBB.

**Commande:**
```bash
./roguebb.sh query reported-ips
python3 query_nodes.py reported_ips
```

**Réponse:**
```json
{
  "status": "ok",
  "count": 47,
  "ips": [
    {
      "ip": "192.168.1.200",
      "reason": "Spam",
      "count": 5,
      "last_report": 1704110000,
      "submitted": true
    }
  ],
  "timestamp": 1704111000
}
```

**Informations retournées:**
- `count`: Nombre total d'IPs signalées
- `ips`: Tableau d'objets avec :
  - `ip`: Adresse IP
  - `reason`: Raison du signalement
  - `count`: Nombre de signalements
  - `last_report`: Timestamp du dernier signalement
  - `submitted`: IP soumise à RogueBB (true/false)

## Utilisation

### Interroger tous les nœuds

```bash
# Via le script roguebb.sh
./roguebb.sh query status
./roguebb.sh query stats
./roguebb.sh query sync-now
./roguebb.sh query local-ips
./roguebb.sh query reported-ips

# Directement avec Python
python3 query_nodes.py status
python3 query_nodes.py stats
```

### Interroger un nœud spécifique

```bash
# Via le script roguebb.sh
./roguebb.sh query status --node http://forum1.com/app.php/activitycontrol/api/query

# Directement avec Python
python3 query_nodes.py status --node http://forum1.com/app.php/activitycontrol/api/query
```

## Enregistrement des nœuds

Les nœuds sont automatiquement découverts à partir des webhooks enregistrés.

```bash
# Lister les webhooks (et donc les nœuds)
python3 manage_webhooks.py list

# Ajouter un nouveau nœud
python3 manage_webhooks.py add http://forum.com/app.php/activitycontrol/webhook/notify
```

**Note:** L'URL du webhook (`/webhook/notify`) est automatiquement convertie en URL de requête (`/api/query`).

## Format des URLs

### URL Webhook (pour notifications push)
```
http://forum.com/app.php/activitycontrol/webhook/notify
```

### URL Query (pour requêtes)
```
http://forum.com/app.php/activitycontrol/api/query
```

## Gestion des erreurs

### Nœud injoignable

```
❌ Erreur          : Connection error
💬 Message         : Connection refused
```

**Solutions:**
- Vérifier que le forum est en ligne
- Vérifier l'URL du nœud
- Vérifier les règles de pare-feu

### Erreur HTTP

```
❌ Erreur          : HTTP 404
💬 Message         : Not Found
```

**Solutions:**
- Vérifier que l'extension est installée
- Vérifier l'URL (doit être `/app.php/activitycontrol/api/query`)
- Vérifier que les routes sont configurées

### Type de requête invalide

```json
{
  "status": "error",
  "message": "Invalid query type"
}
```

**Solution:** Utiliser un type de requête valide (status, stats, sync_now, local_ips, reported_ips)

## Sécurité

### Authentification

Les requêtes aux nœuds **ne nécessitent pas** de signature cryptographique car :
1. Elles sont en **lecture seule** (sauf sync_now)
2. Les nœuds sont sur des réseaux privés/de confiance
3. L'extension peut être configurée pour restreindre l'accès par IP

### Bonnes pratiques

1. **Limiter l'accès par IP** dans la configuration phpBB
2. **Utiliser HTTPS** en production
3. **Surveiller les logs** pour détecter les abus
4. **Limiter le taux de requêtes** si nécessaire

## Configuration phpBB

### Activer les requêtes aux nœuds

Dans l'ACP du forum:
```
Extensions -> Activity Control -> Configuration

[✓] Activer les requêtes distantes
URL du serveur RogueBB: http://roguebb.local:5000
```

### URL de requête

L'URL de requête est automatiquement disponible à :
```
{forum_url}/app.php/activitycontrol/api/query
```

## Exemples d'utilisation

### Surveillance des nœuds

```bash
#!/bin/bash
# Vérifier le statut de tous les nœuds toutes les 5 minutes

while true; do
    echo "Vérification des nœuds..."
    ./roguebb.sh query status
    sleep 300
done
```

### Synchronisation forcée

```bash
# Forcer la synchronisation de tous les nœuds
./roguebb.sh query sync-now
```

### Collecte de statistiques

```bash
# Récupérer les statistiques de tous les forums
./roguebb.sh query stats > stats_$(date +%Y%m%d_%H%M).json
```

### Audit des IPs signalées

```bash
# Voir quelles IPs ont été signalées par les nœuds
./roguebb.sh query reported-ips
```

## Intégration avec le système webhook

Le système de requêtes complète le système webhook :

1. **Webhooks** : RogueBB → phpBB (push)
   - Notification automatique quand la liste change
   - Les nœuds reçoivent et se synchronisent

2. **Requêtes** : RogueBB → phpBB (pull)
   - RogueBB interroge les nœuds à la demande
   - Obtention d'informations en temps réel

```
┌─────────────────┐                    ┌──────────────────┐
│  RogueBB        │                    │  phpBB Forum     │
│                 │                    │                  │
│  [Liste mise    │ ───Webhook────►   │  [Reçoit]        │
│   à jour]       │    (push)          │  [Synchronise]   │
│                 │                    │                  │
│                 │ ◄──Requête────────  │  [Réponse]       │
│  [Demande info] │    (pull)          │                  │
└─────────────────┘                    └──────────────────┘
```

## Troubleshooting

### Aucun nœud trouvé

```
⚠️  Aucun nœud enregistré trouvé
💡 Ajoutez des webhooks avec: python3 manage_webhooks.py add <url>
```

**Solution:** Enregistrer au moins un webhook.

### Timeout de connexion

**Symptôme:** La requête prend trop de temps et échoue.

**Solutions:**
- Augmenter le timeout dans `query_nodes.py` (ligne `timeout=10`)
- Vérifier la latence réseau
- Optimiser les performances du forum

### Réponse JSON invalide

**Symptôme:** Erreur de parsing JSON.

**Solutions:**
- Vérifier les logs du forum
- S'assurer que l'extension est à jour
- Vérifier qu'aucune erreur PHP ne pollue la sortie

## Logs et débogage

### Activer le mode verbeux

Modifier `query_nodes.py` pour ajouter plus de détails :

```python
import logging
logging.basicConfig(level=logging.DEBUG)
```

### Logs côté phpBB

Les requêtes sont journalisées dans :
- **ACP Logs** : Synchronisations déclenchées à distance
- **error.log** : Erreurs PHP

### Logs côté RogueBB

```bash
# Voir les derniers logs
./roguebb.sh logs

# Suivre les logs en temps réel
./roguebb.sh follow
```

## Performances

### Requêtes parallèles

Le script interroge tous les nœuds **séquentiellement**. Pour des performances optimales avec beaucoup de nœuds, envisager :

1. Requêtes asynchrones (aiohttp)
2. Pool de threads
3. Timeout agressif

### Cache

Pour éviter de surcharger les nœuds, implémenter un cache :

```python
# Cache simple avec expiration de 60 secondes
cache = {}
cache_expiry = 60
```

## Voir aussi

- [WEBHOOK_GUIDE.md](WEBHOOK_GUIDE.md) - Système de webhooks
- [INTEGRATION_SUMMARY.md](../phpbb-ext/INTEGRATION_SUMMARY.md) - Intégration complète
- [API.md](API.md) - Documentation complète de l'API
