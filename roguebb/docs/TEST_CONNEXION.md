# 🔧 Test de Connexion RogueBB ↔ phpBB - Instructions

## Problème rencontré

Le système de requêtes aux nœuds a été implémenté mais ne fonctionne pas encore car **le cache de routing de phpBB ne se rafraîchit pas** simplement en vidant le dossier `cache/`.

## Solution : Réactiver l'extension

### Option 1 : Via l'ACP phpBB (Interface Web) ✅ RECOMMANDÉ

1. **Se connecter à l'administration du forum**
   - URL : http://localhost:8080/forum/adm/
   - Identifiants administrateur

2. **Désactiver l'extension**
   - Aller dans : `CUSTOMISE` → `Extension Management`
   - Trouver : `LinkGuarder Activity Control`
   - Cliquer sur : `Disable`

3. **Vider le cache**
   - Aller dans : `GENERAL` → `Purge the cache`
   - Cliquer sur : `Run now`

4. **Réactiver l'extension**
   - Retourner dans : `CUSTOMISE` → `Extension Management`
   - Trouver : `LinkGuarder Activity Control`
   - Cliquer sur : `Enable`

5. **Vider à nouveau le cache**
   - `GENERAL` → `Purge the cache` → `Run now`

### Option 2 : Via la Base de Données

```sql
-- Désactiver l'extension
UPDATE phpbb_ext 
SET ext_active = 0 
WHERE ext_name = 'linkguarder/activitycontrol';

-- Réactiver l'extension
UPDATE phpbb_ext 
SET ext_active = 1 
WHERE ext_name = 'linkguarder/activitycontrol';
```

Puis vider le cache :
```bash
rm -rf /home/nox/Documents/NiMP/var/www/forum/cache/*
```

## URLs à tester après réactivation

### Nouvelle URL simplifiée

L'endpoint a été simplifié pour éviter les conflits :

**Ancienne URL** (ne fonctionne pas) :
```
http://localhost:8080/forum/app.php/activitycontrol/api/query
```

**Nouvelle URL** (à tester) :
```
http://localhost:8080/forum/app.php/ac_node_query
```

### Test manuel avec curl

```bash
# Test de requête status
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"query":"status"}' \
  http://localhost:8080/forum/app.php/ac_node_query

# Devrait retourner du JSON, pas du HTML
```

### Test avec RogueBB

```bash
cd /home/nox/Documents/roguebb

# Tester avec la nouvelle URL
./roguebb.sh query status --node http://localhost:8080/forum/app.php/ac_node_query
```

## Modifications effectuées

### 1. Fichier `/phpbb-ext/config/routing.yml`

```yaml
linkguarder_activitycontrol_node_query:
    path: /ac_node_query
    defaults: { _controller: linkguarder.activitycontrol.controller:node_query }
    methods: [POST]

linkguarder_activitycontrol_webhook:
    path: /activitycontrol/webhook/notify
    defaults: { _controller: linkguarder.activitycontrol.controller:webhook_notification }
    methods: [POST]

linkguarder_activitycontrol_controller:
    path: /activitycontrol/{name}
    defaults: { _controller: linkguarder.activitycontrol.controller:handle }
```

**Changement clé** : Route `/ac_node_query` au lieu de `/activitycontrol/api/query` pour éviter conflit avec `/{name}`

### 2. Script `query_nodes.py`

Le script doit être mis à jour pour utiliser la nouvelle URL.

**AVANT :**
```python
# Convertir les URLs webhook en URLs de requête
node_url = webhook_url.replace('/webhook/notify', '/api/query')
```

**APRÈS :**
```python
# Convertir les URLs webhook en URLs de requête
node_url = webhook_url.replace('/activitycontrol/webhook/notify', '/ac_node_query')
```

### 3. Documentation

Tous les guides doivent être mis à jour :
- `NODE_QUERY_GUIDE.md`
- `NODE_QUERY_UPDATE.md`
- `QUERY_QUICKREF.md`

**Nouvelle URL de requête :**
```
http://forum.com/app.php/ac_node_query
```

## Vérification que ça fonctionne

### ✅ Succès

Si ça fonctionne, curl devrait retourner du JSON :

```json
{
  "status": "ok",
  "node_type": "phpbb_forum",
  "forum_name": "yourdomain.com",
  "phpbb_version": "3.3.11",
  ...
}
```

### ❌ Échec

Si ça ne fonctionne pas, curl retourne du HTML :

```html
<!DOCTYPE html>
<html>
...
```

## Debugging

Si le problème persiste après réactivation :

### 1. Vérifier que le fichier routing.yml est bien copié

```bash
cat /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/config/routing.yml
```

Doit afficher :
```yaml
linkguarder_activitycontrol_node_query:
    path: /ac_node_query
    ...
```

### 2. Vérifier que la méthode existe

```bash
grep -n "public function node_query" /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/controller/main.php
```

Doit afficher :
```
160:    public function node_query()
```

### 3. Vérifier les logs PHP

```bash
tail -f /var/log/nginx/error.log
# ou
tail -f /var/log/php7.4-fpm.log
```

### 4. Test avec GET au lieu de POST

```bash
curl http://localhost:8080/forum/app.php/ac_node_query
```

Devrait retourner :
```json
{
  "status": "error",
  "message": "Only POST requests are allowed"
}
```

Si ça retourne du HTML, la route n'est toujours pas reconnue.

## Alternative : Utiliser un contrôleur séparé

Si le problème persiste, on peut créer un contrôleur complètement séparé :

1. Créer `/controller/query.php` avec la classe `query`
2. Définir la méthode `handle()` dedans
3. Route : `/ac_query` → `linkguarder.activitycontrol.query:handle`

Cela éviterait complètement le conflit avec le contrôleur `main`.

## Commandes rapides

```bash
# Copier le routing mis à jour
cp /home/nox/Documents/phpbb-ext/config/routing.yml \
   /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/config/routing.yml

# Vider le cache
rm -rf /home/nox/Documents/NiMP/var/www/forum/cache/*

# Tester
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"query":"status"}' \
  http://localhost:8080/forum/app.php/ac_node_query
```

## Prochaines étapes après réussite du test

1. ✅ Mettre à jour `query_nodes.py` pour utiliser `/ac_node_query`
2. ✅ Mettre à jour toute la documentation
3. ✅ Tester les 5 types de requêtes
4. ✅ Ajouter l'URL comme webhook
5. ✅ Test end-to-end complet

---

**Note importante** : Le cache de routing de phpBB est très agressif. La désactivation/réactivation de l'extension est la méthode la plus fiable pour forcer le rechargement des routes.

**Date** : 26 octobre 2025  
**Statut** : En attente de réactivation de l'extension
