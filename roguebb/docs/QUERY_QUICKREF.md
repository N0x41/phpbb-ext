# 🔍 Référence Rapide - Requêtes aux Nœuds

Commandes essentielles pour interroger les nœuds phpBB depuis RogueBB.

---

## 📋 Commandes de base

### Interroger tous les nœuds

```bash
# Statut et configuration
./roguebb.sh query status

# Statistiques des forums
./roguebb.sh query stats

# Forcer une synchronisation
./roguebb.sh query sync-now

# IPs bannies localement
./roguebb.sh query local-ips

# IPs signalées par les nœuds
./roguebb.sh query reported-ips
```

### Interroger un nœud spécifique

```bash
# Syntaxe générale
./roguebb.sh query <type> --node <url>

# Exemples
./roguebb.sh query status --node http://forum.com/app.php/activitycontrol/api/query
./roguebb.sh query stats --node http://forum.com/app.php/activitycontrol/api/query
./roguebb.sh query sync-now --node http://forum.com/app.php/activitycontrol/api/query
```

---

## 🎯 Les 5 types de requêtes

### 1. Status - Statut et configuration

```bash
./roguebb.sh query status
```

**Informations :**
- Nom du forum
- Versions (phpBB, extension)
- Synchronisation activée ?
- Signalement activé ?
- Dernière sync
- Version de la liste d'IPs

**Utilité :** Vérifier que les nœuds sont configurés et à jour

---

### 2. Stats - Statistiques

```bash
./roguebb.sh query stats
```

**Informations :**
- IPs bannies
- Utilisateurs
- Messages
- Sujets
- Dernière sync
- Version liste

**Utilité :** Collecter des statistiques globales

---

### 3. Sync Now - Synchronisation immédiate

```bash
./roguebb.sh query sync-now
```

**Action :**
- Déclenche sync immédiate
- Ajoute/retire des IPs
- Journalise dans ACP

**Informations :**
- IPs ajoutées
- IPs retirées
- Total après sync

**Utilité :** Forcer une sync sans attendre le cron

---

### 4. Local IPs - IPs bannies localement

```bash
./roguebb.sh query local-ips
```

**Informations :**
- Nombre total d'IPs
- Liste des IPs (max 100)

**Utilité :** Audit des bans locaux

---

### 5. Reported IPs - IPs signalées

```bash
./roguebb.sh query reported-ips
```

**Informations :**
- Nombre d'IPs signalées
- Détails par IP :
  - IP
  - Raison
  - Nombre de signalements
  - Date dernier signalement
  - Soumise à RogueBB ?

**Utilité :** Voir les IPs remontées par les forums

---

## 🔧 Gestion des nœuds

### Lister les nœuds

```bash
python3 manage_webhooks.py list
```

Les nœuds sont les webhooks (automatiquement convertis).

### Ajouter un nœud

```bash
# Ajouter via webhook
python3 manage_webhooks.py add http://forum.com/app.php/activitycontrol/webhook/notify

# Le système convertit automatiquement :
# /webhook/notify → /api/query
```

### Tester la connectivité

```bash
# Tester un nœud spécifique
./roguebb.sh query status --node http://forum.com/app.php/activitycontrol/api/query
```

---

## 📊 Cas d'usage pratiques

### Monitoring quotidien

```bash
# Vérifier l'état de tous les nœuds
./roguebb.sh query status
```

### Mise à jour urgente

```bash
# 1. Soumettre nouvelle IP
./roguebb.sh submit 203.0.113.50

# 2. Forcer sync immédiate sur tous les forums
./roguebb.sh query sync-now
```

### Audit mensuel

```bash
# Collecter les stats
./roguebb.sh query stats > stats_$(date +%Y%m).txt

# Voir les IPs signalées
./roguebb.sh query reported-ips >> stats_$(date +%Y%m).txt
```

### Dépannage d'un nœud

```bash
# Tester la connexion
./roguebb.sh query status --node http://forum-problem.com/app.php/activitycontrol/api/query

# Si OK, forcer sync
./roguebb.sh query sync-now --node http://forum-problem.com/app.php/activitycontrol/api/query
```

---

## 🐍 Utilisation Python directe

### Syntaxe

```bash
python3 query_nodes.py <query_type> [--node <url>]
```

### Exemples

```bash
# Tous les nœuds
python3 query_nodes.py status
python3 query_nodes.py stats
python3 query_nodes.py sync_now

# Nœud spécifique
python3 query_nodes.py status --node http://forum.com/app.php/activitycontrol/api/query
```

**Note :** Avec Python, utiliser `_` (underscores) au lieu de `-` (tirets)

---

## 🎨 Interprétation de l'affichage

### Symboles

- ✅ : Succès
- ❌ : Échec
- 🔸 : Nœud
- 📊 : Statistiques
- 🛡️ : IPs
- 🕒 : Timestamp

### Codes couleur (terminal)

- **Vert** : Succès
- **Rouge** : Erreur
- **Jaune** : Information
- **Bleu** : En-têtes

### Exemple de sortie

```
================================================================================
📊 STATUT DES NŒUDS
================================================================================

🔸 Nœud 1: http://forum1.com/app.php/activitycontrol/api/query
--------------------------------------------------------------------------------
  ✅ Statut          : ok
  📱 Type            : phpbb_forum
  🏷️  Nom du forum    : Mon Forum
  📦 Version phpBB   : 3.3.11
  🔧 Version ext.    : 1.0.1
  🔄 Sync activée    : ✅ Oui
  📡 Report activé   : ✅ Oui
  🕒 Dernière sync   : 2024-01-01 14:30:00
  📋 Version liste   : 42
  ⏰ Timestamp       : 2024-01-01 15:00:00

🔸 Nœud 2: http://forum2.com/app.php/activitycontrol/api/query
--------------------------------------------------------------------------------
  ❌ Erreur          : Connection error
  💬 Message         : Connection refused

================================================================================
✅ Succès: 1 | ❌ Échecs: 1
```

---

## ⚠️ Gestion d'erreurs

### Aucun nœud trouvé

```
⚠️  Aucun nœud enregistré trouvé
💡 Ajoutez des webhooks avec: python3 manage_webhooks.py add <url>
```

**Solution :** Ajouter au moins un webhook

### Connection error

```
❌ Erreur          : Connection error
💬 Message         : Connection refused
```

**Solutions possibles :**
- Forum hors ligne
- Firewall bloque la connexion
- URL incorrecte

### HTTP 404

```
❌ Erreur          : HTTP 404
💬 Message         : Not Found
```

**Solutions possibles :**
- Extension non installée
- URL incorrecte (vérifier `/app.php/activitycontrol/api/query`)
- Routes non configurées

### Invalid query type

```json
{
  "status": "error",
  "message": "Invalid query type"
}
```

**Solution :** Utiliser un type valide : status, stats, sync_now, local_ips, reported_ips

---

## 🔗 URLs à connaître

### Format webhook (pour notifications)

```
http://forum.com/app.php/activitycontrol/webhook/notify
```

### Format query (pour requêtes)

```
http://forum.com/app.php/activitycontrol/api/query
```

**Important :** Le système convertit automatiquement webhook → query

---

## 🚀 Workflow typique

### Configuration initiale

```bash
# 1. Démarrer RogueBB
./roguebb.sh start

# 2. Ajouter un forum
python3 manage_webhooks.py add http://forum.com/app.php/activitycontrol/webhook/notify

# 3. Tester la connexion
./roguebb.sh query status
```

### Utilisation quotidienne

```bash
# Matin : vérifier l'état
./roguebb.sh query status

# Soumettre des IPs au besoin
./roguebb.sh submit 192.168.1.100

# Fin de journée : stats
./roguebb.sh query stats
```

### En cas d'urgence

```bash
# Nouvelle menace détectée
./roguebb.sh submit 203.0.113.50

# Forcer sync immédiate de tous les forums
./roguebb.sh query sync-now

# Vérifier que tous les forums ont bien synchronisé
./roguebb.sh query status
```

---

## 📚 Documentation complète

Pour plus de détails, consultez :

- **[NODE_QUERY_GUIDE.md](NODE_QUERY_GUIDE.md)** - Guide complet
- **[WEBHOOK_GUIDE.md](WEBHOOK_GUIDE.md)** - Système de webhooks
- **[INDEX.md](INDEX.md)** - Index de la documentation

---

## 💡 Astuces

### Sauvegarder les résultats

```bash
# Sauvegarder les stats
./roguebb.sh query stats > stats_$(date +%Y%m%d).txt

# Sauvegarder le statut
./roguebb.sh query status > status_$(date +%Y%m%d).txt
```

### Interroger régulièrement

```bash
# Script de monitoring (à mettre dans cron)
#!/bin/bash
while true; do
    ./roguebb.sh query status
    sleep 300  # Toutes les 5 minutes
done
```

### Combiné avec d'autres commandes

```bash
# Vérifier le serveur puis les nœuds
./roguebb.sh status && ./roguebb.sh query status

# Stats serveur ET forums
./roguebb.sh stats && ./roguebb.sh query stats
```

---

**🔍 Référence créée pour une consultation rapide des commandes de requêtes aux nœuds**

*Version 1.0 - 2024*
