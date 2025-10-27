# 🔔 Système de Notifications Webhook - RogueBB ↔ phpBB

Ce guide explique comment configurer les notifications automatiques entre le serveur RogueBB et votre forum phpBB.

## 🎯 Fonctionnement

Quand la liste d'IPs est mise à jour sur RogueBB, le serveur **notifie automatiquement** votre forum phpBB, qui **synchronise immédiatement** sa liste de bannissements.

```
┌─────────────────────────────────────────────────────────────┐
│  SERVEUR ROGUEBB                                            │
├─────────────────────────────────────────────────────────────┤
│  1. Nouvelle IP ajoutée                                     │
│  2. Version incrémentée                                     │
│  3. 🔔 Notification envoyée aux webhooks                    │
└────────────────────────┬────────────────────────────────────┘
                         │
                         │ HTTP POST
                         │ {"event": "ip_list_updated",
                         │  "version": 16,
                         │  "total_ips": 17500,
                         │  "timestamp": "2025-10-26 18:30:00"}
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  FORUM PHPBB                                                │
├─────────────────────────────────────────────────────────────┤
│  4. 📥 Réception de la notification                         │
│  5. ✅ Synchronisation automatique                          │
│  6. 🛡️ Mise à jour des bannissements                        │
│  7. 📊 Réponse avec statistiques                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 📝 Configuration

### Étape 1 : Obtenir l'URL du webhook phpBB

L'URL du webhook de votre forum suit ce format :

```
http://VOTRE-DOMAINE/app.php/activitycontrol/webhook/notify
```

**Exemples :**
- Forum local : `http://localhost/phpbb/app.php/activitycontrol/webhook/notify`
- Forum en ligne : `http://forum.example.com/app.php/activitycontrol/webhook/notify`
- Sous-domaine : `http://forum.monsite.com/app.php/activitycontrol/webhook/notify`

### Étape 2 : Ajouter le webhook dans RogueBB

**Méthode 1 : Via le script Python (recommandé)**

```bash
cd /home/nox/Documents/roguebb
python3 manage_webhooks.py add http://VOTRE-DOMAINE/app.php/activitycontrol/webhook/notify
```

**Exemple concret :**
```bash
python3 manage_webhooks.py add http://localhost/phpbb/app.php/activitycontrol/webhook/notify
```

**Méthode 2 : Modification manuelle du fichier**

Éditez `server.py` et ajoutez votre URL :

```python
WEBHOOK_URLS = [
    "http://forum.example.com/app.php/activitycontrol/webhook/notify",
    # Ajoutez d'autres forums ici si nécessaire
]
```

Puis redémarrez le serveur :
```bash
./roguebb.sh restart
```

### Étape 3 : Activer la synchronisation dans phpBB

Dans l'ACP de votre forum phpBB :

1. Allez dans **Extensions > Activity Control > Settings**
2. Activez **"Enable IP synchronization"**
3. Vérifiez que l'URL du serveur central est correcte : `http://localhost:5000`
4. Sauvegardez

---

## ✅ Test de Configuration

### Test 1 : Vérifier les webhooks configurés

```bash
python3 manage_webhooks.py list
```

**Résultat attendu :**
```
======================================================================
📋 WEBHOOKS CONFIGURÉS (1)
======================================================================
1. http://localhost/phpbb/app.php/activitycontrol/webhook/notify
======================================================================
```

### Test 2 : Tester la connexion

```bash
python3 manage_webhooks.py test http://localhost/phpbb/app.php/activitycontrol/webhook/notify
```

**Résultat attendu :**
```
🧪 Test du webhook: http://localhost/phpbb/app.php/activitycontrol/webhook/notify
   Envoi d'une notification de test...
✅ Test réussi!
   HTTP Code: 200
   Réponse: {"status":"ok","message":"Notification received..."}
```

### Test 3 : Soumettre une IP et vérifier la notification

```bash
# 1. Soumettre une nouvelle IP
./roguebb.sh submit 203.0.113.250

# 2. Vérifier les logs du serveur
tail -20 server.log
```

**Dans les logs, vous devriez voir :**
```
[Version] La liste est maintenant à la version 17
[Webhook] Envoi de notifications à 1 client(s)...
[Webhook] ✓ Notification envoyée à http://localhost/phpbb/app.php/activitycontrol/webhook/notify
[Webhook]   → Client synchronisé: 1 ajoutées, 0 retirées, 17501 total
```

### Test 4 : Vérifier dans phpBB

Dans l'ACP de phpBB :
1. Allez dans **System > System logs**
2. Recherchez les entrées récentes avec "webhook" ou "sync"

Vous devriez voir :
```
Webhook notification received: version 17, 17501 IPs, at 2025-10-26 18:30:00
IP sync completed: 1 added, 0 removed, 17501 total
```

---

## 🔄 Workflow Complet

### Scénario : Ajout d'une IP malveillante

```bash
# 1. L'administrateur détecte une IP suspecte
IP_SUSPECTE="45.67.89.123"

# 2. Soumission au serveur RogueBB
cd /home/nox/Documents/roguebb
./roguebb.sh submit $IP_SUSPECTE
```

**Ce qui se passe automatiquement :**

```
┌─────────────────────────────────────┐
│  RogueBB                            │
├─────────────────────────────────────┤
│  ✅ IP ajoutée à la liste          │
│  ✅ Version incrémentée (17 → 18)  │
│  🔔 Notification envoyée            │
└──────────────┬──────────────────────┘
               │
               │ < 1 seconde
               │
               ▼
┌─────────────────────────────────────┐
│  phpBB Forum                        │
├─────────────────────────────────────┤
│  📥 Notification reçue              │
│  🔄 Synchronisation lancée          │
│  📥 Liste téléchargée               │
│  ✅ 45.67.89.123 bannie             │
│  📝 Log créé                        │
└─────────────────────────────────────┘
```

**Résultat :** L'IP est bannie sur tous vos forums en **moins d'une seconde** ! 🚀

---

## 🛠️ Commandes de Gestion

### Lister les webhooks

```bash
python3 manage_webhooks.py list
```

### Ajouter un webhook

```bash
python3 manage_webhooks.py add <url>
```

### Tester un webhook

```bash
python3 manage_webhooks.py test <url>
```

### Retirer un webhook

```bash
python3 manage_webhooks.py remove <url>
```

### Exemples pratiques

```bash
# Ajouter un forum
python3 manage_webhooks.py add http://forum1.com/app.php/activitycontrol/webhook/notify

# Ajouter un second forum
python3 manage_webhooks.py add http://forum2.com/app.php/activitycontrol/webhook/notify

# Tester les deux
python3 manage_webhooks.py test http://forum1.com/app.php/activitycontrol/webhook/notify
python3 manage_webhooks.py test http://forum2.com/app.php/activitycontrol/webhook/notify

# Lister tous
python3 manage_webhooks.py list
```

---

## 🔐 Sécurité

### Données envoyées dans les notifications

```json
{
  "event": "ip_list_updated",
  "version": 18,
  "total_ips": 17502,
  "timestamp": "2025-10-26 18:30:00"
}
```

**Note :** La liste complète des IPs n'est **pas** envoyée dans la notification. Le forum phpBB la télécharge lui-même via l'API `/api/get_ips`.

### Protection

- ✅ Les webhooks utilisent **HTTPS** si configuré
- ✅ Le forum phpBB peut vérifier l'origine de la requête
- ✅ Les notifications sont envoyées dans des threads séparés (non-bloquant)

---

## 📊 Format de Réponse du Webhook

Quand phpBB reçoit une notification, il répond avec :

**Succès avec synchronisation :**
```json
{
  "status": "ok",
  "message": "IP list synchronized successfully",
  "synced": true,
  "stats": {
    "added": 5,
    "removed": 2,
    "total": 17502
  }
}
```

**Notification reçue mais sync désactivée :**
```json
{
  "status": "ok",
  "message": "Notification received but auto-sync is disabled",
  "synced": false
}
```

**Erreur :**
```json
{
  "status": "error",
  "message": "Synchronization failed: ...",
  "synced": false
}
```

---

## 🆘 Dépannage

### Le webhook ne répond pas

**Vérifier que phpBB est accessible :**
```bash
curl -I http://VOTRE-DOMAINE/app.php/activitycontrol/webhook/notify
```

**Vérifier les logs du serveur RogueBB :**
```bash
tail -50 server.log | grep Webhook
```

### Erreur HTTP 404

L'extension phpBB n'est peut-être pas activée ou l'URL est incorrecte.

**Vérifier :**
1. L'extension Activity Control est activée dans phpBB
2. Le routing est correct : `/app.php/activitycontrol/webhook/notify`
3. Le mod_rewrite fonctionne (ou utilisez `/app.php/...`)

### Erreur HTTP 500

**Vérifier les logs d'erreur de phpBB :**
```bash
tail -50 /var/www/forum/phpbb_error.log
```

### Notifications ne sont pas envoyées

**Vérifier que des webhooks sont configurés :**
```bash
python3 manage_webhooks.py list
```

**Redémarrer le serveur :**
```bash
./roguebb.sh restart
```

---

## 💡 Astuces

### Plusieurs forums

Vous pouvez notifier plusieurs forums simultanément :

```bash
python3 manage_webhooks.py add http://forum1.com/app.php/activitycontrol/webhook/notify
python3 manage_webhooks.py add http://forum2.com/app.php/activitycontrol/webhook/notify
python3 manage_webhooks.py add http://forum3.com/app.php/activitycontrol/webhook/notify
```

Tous seront notifiés en parallèle à chaque mise à jour !

### Logs détaillés

Pour voir les notifications en temps réel :

```bash
./roguebb.sh follow
```

Puis dans un autre terminal :

```bash
./roguebb.sh submit 203.0.113.100
```

### Désactiver temporairement

Pour désactiver les notifications sans supprimer les webhooks, commentez-les dans `server.py` :

```python
WEBHOOK_URLS = [
    # "http://forum.example.com/app.php/activitycontrol/webhook/notify",  # Temporairement désactivé
]
```

---

## 📚 API Webhook Complète

### GET `/api/webhooks`
Liste tous les webhooks configurés.

```bash
curl http://localhost:5000/api/webhooks
```

### POST `/api/webhooks/add`
Ajoute un nouveau webhook.

```bash
curl -X POST http://localhost:5000/api/webhooks/add \
  -H "Content-Type: application/json" \
  -d '{"url": "http://forum.com/app.php/activitycontrol/webhook/notify"}'
```

### POST `/api/webhooks/remove`
Retire un webhook.

```bash
curl -X POST http://localhost:5000/api/webhooks/remove \
  -H "Content-Type: application/json" \
  -d '{"url": "http://forum.com/app.php/activitycontrol/webhook/notify"}'
```

### POST `/api/webhooks/test`
Teste un webhook.

```bash
curl -X POST http://localhost:5000/api/webhooks/test \
  -H "Content-Type: application/json" \
  -d '{"url": "http://forum.com/app.php/activitycontrol/webhook/notify"}'
```

---

## ✅ Checklist de Configuration

- [ ] Extension Activity Control installée et activée dans phpBB
- [ ] Synchronisation IP activée dans l'ACP phpBB
- [ ] URL du serveur central configurée : `http://localhost:5000`
- [ ] Serveur RogueBB démarré : `./roguebb.sh status`
- [ ] Webhook ajouté : `python3 manage_webhooks.py add <url>`
- [ ] Test réussi : `python3 manage_webhooks.py test <url>`
- [ ] Test de soumission d'IP effectué
- [ ] Logs vérifiés dans RogueBB et phpBB

---

**🎉 Votre système de notifications automatiques est opérationnel !**

---

*Guide des Webhooks - RogueBB ↔ phpBB*  
*Mise à jour : 26 octobre 2025*
