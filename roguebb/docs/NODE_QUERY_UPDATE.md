# Système de Requêtes aux Nœuds - Mise à jour

## Résumé

Le système RogueBB ↔ phpBB dispose maintenant d'une **communication bidirectionnelle complète** :

1. **Push (Webhooks)** : RogueBB notifie les nœuds phpBB quand la liste change
2. **Pull (Requêtes)** : RogueBB interroge les nœuds phpBB pour obtenir des informations

## Nouveaux fichiers créés

### Côté RogueBB (`/home/nox/Documents/roguebb/`)

1. **query_nodes.py** (401 lignes)
   - Script Python pour interroger les nœuds phpBB
   - Support de 5 types de requêtes
   - Affichage formaté des résultats
   - Gestion des erreurs réseau

2. **NODE_QUERY_GUIDE.md** (500+ lignes)
   - Guide complet du système de requêtes
   - Documentation de chaque type de requête
   - Exemples d'utilisation
   - Troubleshooting

3. **roguebb.sh** (mis à jour)
   - Ajout de la commande `query` avec sous-commandes
   - Support des 5 types de requêtes
   - Conversion automatique des formats (tirets → underscores)

### Côté phpBB Extension (`/home/nox/Documents/phpbb-ext/`)

1. **controller/main.php** (mis à jour)
   - Nouvelle méthode `node_query()` : dispatcher de requêtes
   - 5 méthodes de traitement :
     * `handle_status_query()` : statut et configuration
     * `handle_stats_query()` : statistiques du forum
     * `handle_sync_now_query()` : synchronisation immédiate
     * `handle_local_ips_query()` : IPs bannies (max 100)
     * `handle_reported_ips_query()` : IPs signalées
   - Injection de dépendances : `$db`, `$ext_path`

2. **config/routing.yml** (mis à jour)
   - Nouvelle route : `/activitycontrol/api/query` (POST)
   - Mappage vers `linkguarder_activitycontrol_node_query`

3. **language/en/common.php** (mis à jour)
   - `LOG_AC_REMOTE_SYNC_TRIGGERED` : log de sync distante
   - `AC_NODE_QUERY_URL` : URL de requête
   - `AC_NODE_QUERY_URL_EXPLAIN` : explication

## Architecture du système de requêtes

```
┌─────────────────────────────────────────────────────────────┐
│                    SYSTÈME BIDIRECTIONNEL                    │
└─────────────────────────────────────────────────────────────┘

┌─────────────────┐                         ┌─────────────────┐
│  RogueBB        │                         │  phpBB Forum 1  │
│  (Port 5000)    │                         │                 │
│                 │                         │                 │
│  • server.py    │◄────── WEBHOOK ────────┤  webhook_       │
│  • query_nodes  │        (push)           │  notification() │
│                 │                         │                 │
│                 │──────► QUERY ──────────►│  node_query()   │
│                 │        (pull)           │                 │
└─────────────────┘                         └─────────────────┘
        │                                          
        │                                   ┌─────────────────┐
        └──────► QUERY ────────────────────►│  phpBB Forum 2  │
                 (pull)                     │                 │
                                            │  node_query()   │
                                            └─────────────────┘
```

## 5 types de requêtes

### 1. Status - Statut et configuration
```bash
./roguebb.sh query status
```
Retourne : nom du forum, versions, état de sync, dernière sync

### 2. Stats - Statistiques
```bash
./roguebb.sh query stats
```
Retourne : IPs bannies, utilisateurs, messages, sujets

### 3. Sync Now - Synchronisation immédiate
```bash
./roguebb.sh query sync-now
```
Déclenche : synchronisation immédiate avec RogueBB
Retourne : nombre d'IPs ajoutées/retirées

### 4. Local IPs - IPs bannies localement
```bash
./roguebb.sh query local-ips
```
Retourne : liste des IPs bannies sur le forum (max 100)

### 5. Reported IPs - IPs signalées
```bash
./roguebb.sh query reported-ips
```
Retourne : IPs que ce forum a signalées à RogueBB

## Utilisation

### Interroger tous les nœuds enregistrés

```bash
cd /home/nox/Documents/roguebb

# Via roguebb.sh (recommandé)
./roguebb.sh query status
./roguebb.sh query stats
./roguebb.sh query sync-now

# Via Python directement
python3 query_nodes.py status
python3 query_nodes.py stats
```

### Interroger un nœud spécifique

```bash
# Spécifier l'URL du nœud
./roguebb.sh query status --node http://forum.example.com/app.php/activitycontrol/api/query

# Avec Python
python3 query_nodes.py stats --node http://forum.example.com/app.php/activitycontrol/api/query
```

### Enregistrer des nœuds

Les nœuds sont découverts automatiquement depuis les webhooks :

```bash
# Ajouter un webhook (qui sera aussi un nœud interrogeable)
python3 manage_webhooks.py add http://forum.example.com/app.php/activitycontrol/webhook/notify

# Lister les nœuds (via les webhooks)
python3 manage_webhooks.py list
```

**Note :** L'URL webhook (`/webhook/notify`) est convertie automatiquement en URL de requête (`/api/query`).

## Format des réponses JSON

Toutes les réponses suivent le même format :

```json
{
  "status": "ok",
  "data": { ... },
  "timestamp": 1704111000
}
```

En cas d'erreur :

```json
{
  "status": "error",
  "message": "Description de l'erreur"
}
```

## Exemples de réponses

### Status
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

### Stats
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

### Sync Now
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

## Sécurité

### Pas d'authentification requise (par défaut)

Les requêtes aux nœuds ne nécessitent **pas** de signature cryptographique car :
- Lecture seule (sauf sync_now)
- Réseaux privés/de confiance
- Peut être restreint par IP dans phpBB

### Recommandations pour la production

1. **Utiliser HTTPS** pour chiffrer les communications
2. **Restreindre par IP** dans la configuration phpBB
3. **Surveiller les logs** pour détecter les abus
4. **Implémenter rate limiting** si nécessaire

## Tests

### Test sans nœud enregistré

```bash
$ python3 query_nodes.py status
🔍 Récupération de la liste des nœuds...
⚠️  Aucun nœud enregistré trouvé
💡 Ajoutez des webhooks avec: python3 manage_webhooks.py add <url>
```

### Test avec un nœud spécifique (simulation)

```bash
# Exemple d'URL (à adapter à votre forum)
$ ./roguebb.sh query status --node http://localhost/phpbb/app.php/activitycontrol/api/query
```

### Affichage formaté

Le script `query_nodes.py` affiche les résultats de manière formatée avec :
- 🔸 Icônes pour la lisibilité
- ✅/❌ Statuts visuels
- Séparateurs clairs
- Timestamps formatés en dates
- Résumé final (succès/échecs)

## Intégration avec le workflow existant

### Avant (Webhook uniquement)

```
1. Liste RogueBB mise à jour
2. → Webhook envoyé aux forums
3. → Forums se synchronisent automatiquement
```

### Maintenant (Webhook + Requêtes)

```
1. Liste RogueBB mise à jour
2. → Webhook envoyé aux forums
3. → Forums se synchronisent automatiquement

À tout moment :
4. RogueBB peut interroger les forums
5. → Obtenir leur statut
6. → Vérifier leur sync
7. → Forcer une sync
8. → Récupérer leurs IPs/stats
```

## Cas d'usage

### 1. Monitoring

```bash
# Vérifier que tous les nœuds sont à jour
./roguebb.sh query status | grep "ip_list_version"
```

### 2. Synchronisation forcée

```bash
# Forcer tous les forums à se resynchroniser
./roguebb.sh query sync-now
```

### 3. Audit des IPs

```bash
# Voir quelles IPs ont été signalées par les forums
./roguebb.sh query reported-ips
```

### 4. Collecte de statistiques

```bash
# Récupérer les stats de tous les forums
./roguebb.sh query stats > forum_stats_$(date +%Y%m%d).txt
```

## Logs et débogage

### Côté RogueBB

```bash
# Voir les logs du serveur
./roguebb.sh logs

# Suivre en temps réel
./roguebb.sh follow
```

### Côté phpBB

Les requêtes sont journalisées dans :
- **ACP → System → Administrator log** : sync déclenchées
- **error.log** : erreurs PHP éventuelles

## Performance

### Requêtes séquentielles

Actuellement, les requêtes sont envoyées **séquentiellement** :
- Avantage : simple et fiable
- Inconvénient : temps d'attente avec beaucoup de nœuds

### Optimisation future possible

Pour beaucoup de nœuds (>10), on pourrait implémenter :
- Requêtes asynchrones (aiohttp)
- Pool de threads
- Cache des résultats

## Dépendances

### Python (déjà installées)
- requests
- json (standard library)
- datetime (standard library)

### phpBB Extension
- Aucune nouvelle dépendance
- Utilise les services existants ($db, $config, etc.)

## Documentation complète

- **[NODE_QUERY_GUIDE.md](NODE_QUERY_GUIDE.md)** : Guide détaillé du système de requêtes
- **[WEBHOOK_GUIDE.md](WEBHOOK_GUIDE.md)** : Guide du système webhook
- **[SYSTEME_COMPLET.md](SYSTEME_COMPLET.md)** : Vue d'ensemble complète

## Statut du projet

✅ **Système de requêtes aux nœuds : COMPLET**

- [x] Endpoint phpBB (`node_query()` avec 5 handlers)
- [x] Script Python client (`query_nodes.py`)
- [x] Intégration roguebb.sh
- [x] Documentation complète
- [x] Gestion d'erreurs
- [x] Affichage formaté
- [x] Support nœud spécifique
- [x] Tests basiques

### Prochaines étapes (optionnelles)

- [ ] Tests en conditions réelles avec un forum phpBB
- [ ] Ajout d'authentification par token (si nécessaire)
- [ ] Cache des résultats côté RogueBB
- [ ] Interface web pour visualiser les nœuds
- [ ] Graphiques de monitoring

## Commandes rapides

```bash
# Voir l'aide
./roguebb.sh help

# Statut du serveur
./roguebb.sh status

# Interroger les nœuds
./roguebb.sh query status
./roguebb.sh query stats
./roguebb.sh query sync-now
./roguebb.sh query local-ips
./roguebb.sh query reported-ips

# Gérer les webhooks
python3 manage_webhooks.py list
python3 manage_webhooks.py add <url>
python3 manage_webhooks.py test
```

## Contact et support

Pour toute question ou problème :
1. Consulter [NODE_QUERY_GUIDE.md](NODE_QUERY_GUIDE.md)
2. Vérifier les logs avec `./roguebb.sh logs`
3. Tester avec un nœud spécifique : `--node <url>`

---

**Date de création :** 2024
**Version :** 1.0.0
**Auteur :** Système RogueBB/phpBB Integration
