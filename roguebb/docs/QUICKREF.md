# 🚀 Guide Rapide - RogueBB

Ce guide vous montre les commandes les plus courantes pour utiliser le serveur RogueBB.

## ⚡ Utilisation Super Simple (Recommandé)

Un script bash tout-en-un est disponible : `roguebb.sh`

### Commandes Principales

```bash
# Voir toutes les commandes disponibles
./roguebb.sh help

# Démarrer le serveur
./roguebb.sh start

# Vérifier le statut
./roguebb.sh status

# Soumettre une IP
./roguebb.sh submit 192.168.1.100

# Soumettre plusieurs IPs depuis un fichier
./roguebb.sh submit-file mes_ips.txt

# Voir les statistiques
./roguebb.sh stats

# Sauvegarder la liste
./roguebb.sh backup

# Ouvrir le dashboard dans le navigateur
./roguebb.sh dashboard

# Arrêter le serveur
./roguebb.sh stop
```

## 📝 Exemples d'Utilisation

### Scénario 1 : Démarrer et soumettre des IPs

```bash
cd /home/nox/Documents/roguebb

# 1. Démarrer le serveur
./roguebb.sh start

# 2. Soumettre quelques IPs
./roguebb.sh submit 192.168.1.100
./roguebb.sh submit 10.0.0.50
./roguebb.sh submit 172.16.0.1

# 3. Voir les stats
./roguebb.sh stats
```

### Scénario 2 : Soumission en masse

```bash
# 1. Créer un fichier avec vos IPs
cat > ips_suspectes.txt << EOF
192.168.1.150
192.168.1.151
10.0.0.100
203.0.113.45
198.51.100.78
EOF

# 2. Soumettre tout le fichier
./roguebb.sh submit-file ips_suspectes.txt

# 3. Vérifier le résultat
./roguebb.sh stats
```

### Scénario 3 : Monitoring

```bash
# Voir le statut en temps réel
watch -n 5 './roguebb.sh status'

# Ou suivre les logs
./roguebb.sh follow
```

### Scénario 4 : Sauvegardes régulières

```bash
# Sauvegarder avec la date du jour
./roguebb.sh backup backup_$(date +%Y%m%d).txt

# Ou planifier avec cron (chaque jour à minuit)
crontab -e
# Ajouter : 0 0 * * * cd /home/nox/Documents/roguebb && ./roguebb.sh backup backup_$(date +\%Y\%m\%d).txt
```

## 🎯 Commandes Directes (Alternative)

Si vous préférez utiliser directement les scripts Python :

```bash
# Soumettre une IP
python3 client_example.py 192.168.1.100

# Soumettre plusieurs IPs
python3 batch_submit_ips.py 192.168.1.1 192.168.1.2 192.168.1.3
python3 batch_submit_ips.py --file ips.txt

# Récupérer la liste
python3 get_ip_list.py --count
python3 get_ip_list.py --stats
python3 get_ip_list.py --save backup.txt
```

## 🌐 Dashboard Web

Une fois le serveur démarré, ouvrez : **http://localhost:5000**

Le dashboard vous permet de :
- ✅ Voir le nombre total d'IPs
- ✅ Voir la version de la liste
- ✅ Voir les nœuds actifs
- ✅ Supprimer des IPs individuellement
- ✅ Réinitialiser complètement la liste

## 📊 Format des Fichiers d'IPs

Les fichiers `.txt` doivent contenir :
- **Une IP par ligne**
- Les lignes vides sont ignorées
- Les lignes commençant par `#` sont des commentaires

**Exemple :**
```
# IPs suspectes du 26/10/2025
192.168.1.100
192.168.1.101

# IPs de spam
203.0.113.45
198.51.100.78
```

## 🔐 Sécurité

- ✅ Toutes les soumissions nécessitent une **signature RSA**
- ✅ Les clés sont déjà générées (`private_key.pem`, `public_key.pem`)
- ⚠️ **Ne JAMAIS** partager `private_key.pem`
- ✅ Les requêtes non signées sont automatiquement **rejetées**

## 🆘 Problèmes Courants

### Le serveur ne démarre pas
```bash
# Vérifier si le port 5000 est libre
lsof -i :5000

# Voir les logs d'erreur
cat server.log
```

### Le serveur ne répond pas
```bash
# Vérifier qu'il tourne
./roguebb.sh status

# Redémarrer
./roguebb.sh restart
```

### Erreur de signature
```bash
# Régénérer les clés
python3 generate_keys.py
./roguebb.sh restart
```

## 📚 Documentation Complète

- **[GUIDE_UTILISATION.md](GUIDE_UTILISATION.md)** - Guide complet en français
- **[README.md](README.md)** - Documentation technique
- **[SECURITY.md](SECURITY.md)** - Détails sur la sécurité
- **[QUICK_START.md](QUICK_START.md)** - Démarrage rapide

## 💡 Astuces

### Alias pratique

Ajoutez dans votre `~/.bashrc` :
```bash
alias roguebb='cd /home/nox/Documents/roguebb && ./roguebb.sh'
```

Puis vous pourrez utiliser depuis n'importe où :
```bash
roguebb start
roguebb submit 192.168.1.100
roguebb stats
```

### Script de monitoring simple

```bash
#!/bin/bash
while true; do
    clear
    ./roguebb.sh status
    sleep 10
done
```

### Intégration avec phpBB

Le service `ip_reporter.php` dans `/home/nox/Documents/phpbb-ext/` peut automatiquement soumettre des IPs détectées sur votre forum phpBB.

## ✨ Workflow Recommandé

1. **Démarrage** : `./roguebb.sh start`
2. **Vérification** : `./roguebb.sh status`
3. **Soumission** : `./roguebb.sh submit-file ips.txt` ou `./roguebb.sh submit <ip>`
4. **Monitoring** : `./roguebb.sh dashboard` (ouvre le navigateur)
5. **Sauvegarde** : `./roguebb.sh backup backup_$(date +%Y%m%d).txt`

---

**🎉 Vous êtes prêt ! Le système est simple et efficace.**

Pour toute question, consultez le **[GUIDE_UTILISATION.md](GUIDE_UTILISATION.md)** complet.
