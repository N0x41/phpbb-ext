# 🇫🇷 Guide d'utilisation - Serveur RogueBB

Ce guide explique comment utiliser le serveur Python RogueBB pour gérer une liste centralisée d'adresses IP suspectes.

## 📋 Table des matières

1. [Démarrage du serveur](#démarrage-du-serveur)
2. [Soumission d'IPs](#soumission-dips)
3. [Récupération de la liste](#récupération-de-la-liste)
4. [Dashboard web](#dashboard-web)
5. [Scripts disponibles](#scripts-disponibles)

---

## 🚀 Démarrage du serveur

### Installation des dépendances (première fois seulement)

```bash
cd /home/nox/Documents/roguebb
pip install -r requirements.txt
```

### Démarrer le serveur

```bash
python3 server.py
```

Le serveur démarre sur `http://localhost:5000`

Pour l'arrêter : **Ctrl+C**

---

## 📤 Soumission d'IPs

### Méthode 1 : Soumettre une seule IP

```bash
python3 client_example.py 192.168.1.100
```

**Résultat :**
```
✓ IP soumise avec succès!
```

### Méthode 2 : Soumettre plusieurs IPs en ligne de commande

```bash
python3 batch_submit_ips.py 192.168.1.100 10.0.0.50 172.16.0.1
```

**Résultat :**
```
[1/3]   ✅ 192.168.1.100: Ajoutée (version 10)
[2/3]   ✅ 10.0.0.50: Ajoutée (version 11)
[3/3]   ℹ️  172.16.0.1: Existe déjà
```

### Méthode 3 : Soumettre depuis un fichier

Créez un fichier `mes_ips.txt` :
```
# IPs suspectes
192.168.1.150
192.168.1.151
10.0.0.100
```

Puis :
```bash
python3 batch_submit_ips.py --file mes_ips.txt
```

**Résultat :**
```
📂 Lecture depuis le fichier: mes_ips.txt
📤 Soumission de 3 IP(s)...

[1/3]   ✅ 192.168.1.150: Ajoutée (version 12)
[2/3]   ✅ 192.168.1.151: Ajoutée (version 13)
[3/3]   ✅ 10.0.0.100: Ajoutée (version 14)

============================================================
📊 STATISTIQUES
============================================================
Total traité      : 3
✅ Ajoutées       : 3
ℹ️  Déjà existantes : 0
❌ Échecs         : 0
⚠️  Invalides      : 0

Taux de succès    : 100.0%
============================================================
```

### Méthode 4 : Soumettre depuis stdin (pipe)

```bash
cat mes_ips.txt | python3 batch_submit_ips.py --stdin
```

ou

```bash
echo -e "192.168.1.1\n192.168.1.2" | python3 batch_submit_ips.py --stdin
```

---

## 📥 Récupération de la liste

### Afficher le nombre d'IPs

```bash
python3 get_ip_list.py --count
```

**Résultat :**
```
Nombre total d'IPs: 17481
```

### Afficher les statistiques

```bash
python3 get_ip_list.py --stats
```

**Résultat :**
```
============================================================
📊 STATISTIQUES DU SERVEUR
============================================================
Version de la liste : 8
Nombre total d'IPs  : 17481
Date/Heure          : 2025-10-26 18:08:09

Répartition :
  - IPv4            : 17481
  - IPv6            : 0

Top 10 des réseaux IPv4 (/16):
  1. 147.185.x.x          :   458 IPs
  2. 162.216.x.x          :   457 IPs
  ...
============================================================
```

### Sauvegarder la liste dans un fichier

```bash
python3 get_ip_list.py --save backup_ips.txt
```

**Résultat :**
```
✅ Liste sauvegardée dans: backup_ips.txt
```

### Afficher toute la liste

```bash
python3 get_ip_list.py
```

---

## 🖥️ Dashboard web

Ouvrez votre navigateur : **http://localhost:5000**

Le dashboard affiche :
- **Nombre total d'IPs** dans la liste
- **Version de la liste** (incrémentée à chaque modification)
- **Nœuds actifs** (clients connectés)
- **IPs ajoutées par chaque nœud**
- Boutons pour **supprimer des IPs** individuellement
- Bouton pour **réinitialiser la liste complète**

Le dashboard se rafraîchit automatiquement toutes les 30 secondes.

---

## 🛠️ Scripts disponibles

### `server.py`
Serveur Flask principal avec authentification cryptographique RSA.

**Usage :**
```bash
python3 server.py
```

**Endpoints API :**
- `GET /` - Dashboard web
- `GET /api/get_ips` - Récupérer la liste complète
- `GET /api/get_version` - Obtenir la version de la liste
- `POST /api/submit_ip` - Soumettre une IP (signature requise)
- `POST /api/heartbeat` - Signal de présence
- `GET /api/delete_ip?ip=x.x.x.x` - Supprimer une IP
- `POST /api/reset_list` - Réinitialiser la liste

---

### `client_example.py`
Client exemple pour soumettre une seule IP.

**Usage :**
```bash
python3 client_example.py <ip>
```

**Exemple :**
```bash
python3 client_example.py 192.168.1.100
```

---

### `batch_submit_ips.py`
Script pour soumettre plusieurs IPs en masse.

**Usage :**
```bash
# Depuis la ligne de commande
python3 batch_submit_ips.py <ip1> <ip2> <ip3> ...

# Depuis un fichier
python3 batch_submit_ips.py --file <fichier.txt>

# Depuis stdin
cat fichier.txt | python3 batch_submit_ips.py --stdin
```

**Exemples :**
```bash
python3 batch_submit_ips.py 192.168.1.1 192.168.1.2
python3 batch_submit_ips.py --file suspicious_ips.txt
echo "10.0.0.1" | python3 batch_submit_ips.py --stdin
```

**Configuration :**
- `BATCH_SIZE = 10` - Nombre d'IPs avant pause
- `DELAY_BETWEEN_BATCHES = 1` - Délai entre les batches (secondes)

---

### `get_ip_list.py`
Script pour récupérer et afficher la liste d'IPs.

**Usage :**
```bash
# Afficher toute la liste
python3 get_ip_list.py

# Afficher seulement le nombre
python3 get_ip_list.py --count

# Afficher les statistiques
python3 get_ip_list.py --stats

# Sauvegarder dans un fichier
python3 get_ip_list.py --save <fichier.txt>
```

**Exemples :**
```bash
python3 get_ip_list.py --stats
python3 get_ip_list.py --save backup_$(date +%Y%m%d).txt
```

---

### `generate_keys.py`
Générateur de paires de clés RSA pour l'authentification.

**Usage :**
```bash
python3 generate_keys.py
```

Génère :
- `private_key.pem` - Clé privée (SECRÈTE, ne pas partager)
- `public_key.pem` - Clé publique (pour les clients)

⚠️ **Important :** La clé privée ne doit JAMAIS être partagée ou committée dans git.

---

### `test_security.py`
Tests de sécurité pour vérifier l'authentification.

**Usage :**
```bash
python3 test_security.py
```

Teste que le serveur rejette :
- Les requêtes sans signature
- Les requêtes avec signature invalide
- Les requêtes avec signature vide

---

## 🔐 Sécurité

### Authentification par signature RSA

Toutes les soumissions d'IPs nécessitent une **signature cryptographique** :

1. Le client signe l'IP avec sa **clé privée**
2. Le serveur vérifie avec la **clé publique**
3. Les requêtes non signées ou invalides sont **rejetées** (HTTP 403)

### Codes de réponse HTTP

| Code | Signification |
|------|---------------|
| 200 | ✅ IP acceptée et ajoutée |
| 400 | ❌ Requête mal formée |
| 401 | ❌ Signature manquante |
| 403 | ❌ Signature invalide |

---

## 📊 Système de versioning

Chaque modification de la liste incrémente automatiquement le **numéro de version**.

Les clients peuvent :
1. Vérifier la version avec `GET /api/get_version`
2. Ne télécharger la liste que si elle a changé
3. Optimiser la bande passante

**Exemple :**
```python
import requests

# Récupérer la version actuelle
version = requests.get('http://localhost:5000/api/get_version').json()['version']
print(f"Version actuelle: {version}")
```

---

## 🔄 Intégration avec phpBB

Le service `ip_reporter.php` de l'extension phpBB peut :
1. Détecter des IPs suspectes (tentatives de connexion, spam, etc.)
2. Les soumettre automatiquement au serveur RogueBB
3. Synchroniser la liste pour bannir les IPs

Voir le fichier `/home/nox/Documents/phpbb-ext/service/ip_reporter.php`

---

## ⚙️ Configuration

### Changer l'URL du serveur

Dans les scripts clients, modifiez :
```python
SERVER_URL = "http://localhost:5000"  # Changer ici
```

### Modifier l'intervalle de mise à jour automatique

Dans `server.py` :
```python
UPDATE_INTERVAL_SECONDS = 3600  # 1 heure (3600 secondes)
```

### Changer la source d'IPs externe

Dans `server.py` :
```python
IP_SOURCE_URL = "https://raw.githubusercontent.com/stamparm/ipsum/master/levels/3.txt"
```

---

## 🆘 Dépannage

### Le serveur ne démarre pas

**Problème :** Port 5000 déjà utilisé

**Solution :**
```bash
# Trouver le processus
lsof -i :5000

# Tuer le processus
kill <PID>

# Ou changer le port dans server.py
app.run(host='0.0.0.0', port=5001)
```

### Erreur "Fichier private_key.pem introuvable"

**Solution :**
```bash
python3 generate_keys.py
```

### Erreur "Invalid signature"

**Cause :** Mauvaise clé privée utilisée

**Solution :** Vérifier que vous utilisez la bonne paire de clés (private_key.pem et public_key.pem doivent correspondre).

### Le serveur est inaccessible

**Vérifier que le serveur est démarré :**
```bash
ps aux | grep server.py
```

**Vérifier la connexion :**
```bash
curl http://localhost:5000/api/get_version
```

---

## 📚 Ressources supplémentaires

- **[README.md](README.md)** - Vue d'ensemble du projet
- **[SECURITY.md](SECURITY.md)** - Documentation de sécurité détaillée
- **[QUICK_START.md](QUICK_START.md)** - Guide de démarrage rapide
- **[CLIENT_PHP.md](CLIENT_PHP.md)** - Guide du client PHP

---

## 🎯 Exemples d'utilisation courants

### Workflow complet de soumission

```bash
# 1. Démarrer le serveur (terminal 1)
python3 server.py

# 2. Dans un autre terminal
cd /home/nox/Documents/roguebb

# 3. Créer une liste d'IPs suspectes
cat > mes_ips.txt << EOF
192.168.1.150
192.168.1.151
10.0.0.100
EOF

# 4. Soumettre les IPs
python3 batch_submit_ips.py --file mes_ips.txt

# 5. Vérifier les statistiques
python3 get_ip_list.py --stats

# 6. Sauvegarder une copie
python3 get_ip_list.py --save backup_$(date +%Y%m%d).txt
```

### Automatisation avec cron

Pour soumettre automatiquement des IPs toutes les heures :

```bash
# Éditer crontab
crontab -e

# Ajouter cette ligne
0 * * * * cd /home/nox/Documents/roguebb && python3 batch_submit_ips.py --file /path/to/daily_ips.txt
```

### Monitoring de la liste

```bash
# Créer un script de monitoring
cat > monitor.sh << 'EOF'
#!/bin/bash
while true; do
    clear
    echo "=== Monitoring RogueBB ==="
    date
    python3 get_ip_list.py --count
    echo ""
    sleep 60
done
EOF

chmod +x monitor.sh
./monitor.sh
```

---

**📧 Support :** Pour toute question, consultez la documentation ou ouvrez une issue.

**🔐 Sécurité :** Ne partagez JAMAIS votre clé privée (`private_key.pem`).

**✨ Bon bannissement d'IPs ! 🛡️**
