# 🎯 GUIDE VISUEL - Soumission d'IPs avec RogueBB

## 📍 Vous êtes ici

```
/home/nox/Documents/roguebb/
```

## 🚀 Commandes Ultra-Rapides

### ⚡ Commande Unique (Recommandée)

```bash
cd /home/nox/Documents/roguebb && ./roguebb.sh submit <ip>
```

**Exemple concret :**
```bash
cd /home/nox/Documents/roguebb && ./roguebb.sh submit 192.168.1.100
```

**Résultat :**
```
✅ 192.168.1.100: Ajoutée (version 16)
✓ IP soumise avec succès!
```

---

## 📋 Méthodes de Soumission

### Méthode 1️⃣ : Une seule IP

```bash
./roguebb.sh submit 192.168.1.100
```

```
┌─────────────────────────────────────────┐
│  Vous tapez : 192.168.1.100            │
├─────────────────────────────────────────┤
│  ✅ Signature automatique (RSA)         │
│  ✅ Envoi au serveur                    │
│  ✅ Vérification par le serveur         │
│  ✅ Ajout à la liste                    │
│  ✅ Version incrémentée                 │
└─────────────────────────────────────────┘
```

---

### Méthode 2️⃣ : Plusieurs IPs (ligne de commande)

```bash
python3 batch_submit_ips.py 192.168.1.1 192.168.1.2 192.168.1.3
```

```
┌─────────────────────────────────────────┐
│  [1/3] ✅ 192.168.1.1: Ajoutée         │
│  [2/3] ✅ 192.168.1.2: Ajoutée         │
│  [3/3] ✅ 192.168.1.3: Ajoutée         │
├─────────────────────────────────────────┤
│  Taux de succès : 100.0%               │
└─────────────────────────────────────────┘
```

---

### Méthode 3️⃣ : Depuis un fichier texte

**Créer le fichier :**
```bash
cat > mes_ips.txt << EOF
192.168.1.150
192.168.1.151
10.0.0.100
EOF
```

**Soumettre :**
```bash
./roguebb.sh submit-file mes_ips.txt
```

```
┌─────────────────────────────────────────┐
│  📂 Lecture : mes_ips.txt              │
│  📤 Soumission de 3 IP(s)              │
├─────────────────────────────────────────┤
│  [1/3] ✅ 192.168.1.150: Ajoutée       │
│  [2/3] ✅ 192.168.1.151: Ajoutée       │
│  [3/3] ✅ 10.0.0.100: Ajoutée          │
├─────────────────────────────────────────┤
│  ✅ Ajoutées : 3                        │
│  Taux de succès : 100.0%               │
└─────────────────────────────────────────┘
```

---

### Méthode 4️⃣ : Depuis stdin (pipe)

```bash
echo "192.168.1.200" | python3 batch_submit_ips.py --stdin
```

Ou :
```bash
cat mes_ips.txt | python3 batch_submit_ips.py --stdin
```

```
┌─────────────────────────────────────────┐
│  📥 Lecture depuis stdin...            │
│  ✅ 192.168.1.200: Ajoutée             │
└─────────────────────────────────────────┘
```

---

## 📊 Voir les Statistiques

### Option 1 : Nombre d'IPs seulement

```bash
python3 get_ip_list.py --count
```

```
┌─────────────────────────────────────────┐
│  Nombre total d'IPs: 17485             │
└─────────────────────────────────────────┘
```

---

### Option 2 : Statistiques complètes

```bash
./roguebb.sh stats
```

```
┌─────────────────────────────────────────┐
│  Version de la liste : 15              │
│  Nombre total d'IPs  : 17485           │
│  Date/Heure          : 2025-10-26      │
├─────────────────────────────────────────┤
│  Répartition :                         │
│    - IPv4 : 17485                      │
│    - IPv6 : 0                          │
├─────────────────────────────────────────┤
│  Top 10 des réseaux IPv4 (/16):       │
│  1. 147.185.x.x   : 458 IPs           │
│  2. 162.216.x.x   : 457 IPs           │
│  3. 35.203.x.x    : 457 IPs           │
│  ...                                   │
└─────────────────────────────────────────┘
```

---

### Option 3 : Dashboard web

```bash
./roguebb.sh dashboard
```

Ouvre automatiquement : **http://localhost:5000**

```
┌─────────────────────────────────────────┐
│  🌐 Dashboard IP Distribué             │
├─────────────────────────────────────────┤
│  IP Uniques Totales : 17485            │
│  Version : 15                          │
│  Nœuds Actifs : 1                      │
├─────────────────────────────────────────┤
│  [Réinitialiser la Liste]              │
│                                         │
│  Liste des IPs avec boutons [X]        │
│  pour supprimer individuellement        │
└─────────────────────────────────────────┘
```

---

## 💾 Sauvegarder la Liste

```bash
./roguebb.sh backup backup_$(date +%Y%m%d).txt
```

```
┌─────────────────────────────────────────┐
│  ✅ Liste sauvegardée dans:            │
│  backup_20251026.txt                   │
└─────────────────────────────────────────┘
```

Le fichier contient :
```
# Liste d'IPs récupérée le 2025-10-26 18:15:00
# Nombre total: 17485

10.0.0.100
...
203.0.113.202
```

---

## 🔄 Workflow Complet

### Scénario typique :

```bash
# 1. Aller dans le répertoire
cd /home/nox/Documents/roguebb

# 2. Vérifier que le serveur tourne
./roguebb.sh status

# 3. Soumettre des IPs
./roguebb.sh submit 192.168.1.100
./roguebb.sh submit 192.168.1.101

# 4. Ou soumettre depuis un fichier
./roguebb.sh submit-file mes_ips.txt

# 5. Vérifier les stats
./roguebb.sh stats

# 6. Sauvegarder une copie
./roguebb.sh backup backup_daily.txt
```

```
┌──────────────────────┐
│   1. Vérification    │
│   ✅ Serveur OK      │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   2. Soumission      │
│   ✅ IPs ajoutées    │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   3. Vérification    │
│   ✅ Stats OK        │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   4. Sauvegarde      │
│   ✅ Backup créé     │
└──────────────────────┘
```

---

## 🎨 Format des Fichiers d'IPs

### ✅ Format Correct

```
# Commentaire : IPs suspectes
192.168.1.100
192.168.1.101

# Autre commentaire
10.0.0.50
```

**Règles :**
- ✅ Une IP par ligne
- ✅ Lignes vides ignorées
- ✅ Commentaires avec `#`
- ✅ IPv4 supporté
- ✅ IPv6 supporté

---

### ❌ Formats Incorrects

```
192.168.1.100, 192.168.1.101      ❌ Pas de virgule
192.168.1.100 192.168.1.101       ❌ Pas d'espace
192.168.1.100;192.168.1.101       ❌ Pas de point-virgule
999.999.999.999                   ❌ IP invalide
```

---

## 🔐 Sécurité Automatique

Tout est géré automatiquement :

```
┌─────────────────────────────────────────┐
│  VOUS                                   │
│  └─ Tapez l'IP                          │
│                                         │
│  ▼                                      │
│                                         │
│  SCRIPT (batch_submit_ips.py)          │
│  ├─ Charge private_key.pem             │
│  ├─ Signe l'IP avec RSA                │
│  └─ Encode en base64                   │
│                                         │
│  ▼                                      │
│                                         │
│  SERVEUR (server.py)                   │
│  ├─ Charge public_key.pem              │
│  ├─ Vérifie la signature               │
│  ├─ Si valide ✅ → Accepte             │
│  └─ Si invalide ❌ → Rejette (403)     │
└─────────────────────────────────────────┘
```

**Vous n'avez rien à faire !** 🎉

---

## 🆘 Aide Rapide

### Le serveur n'est pas démarré ?

```bash
./roguebb.sh start
```

### Voir les logs ?

```bash
./roguebb.sh logs
```

### Suivre les logs en temps réel ?

```bash
./roguebb.sh follow
```

### Redémarrer le serveur ?

```bash
./roguebb.sh restart
```

### Arrêter le serveur ?

```bash
./roguebb.sh stop
```

---

## 📚 Tous les Guides

- **GUIDE_VISUEL.md** ← Vous êtes ici (guide illustré)
- **QUICKREF.md** - Référence ultra-rapide
- **GUIDE_UTILISATION.md** - Guide complet
- **RESUME.md** - Résumé des fonctionnalités
- **README.md** - Documentation technique

---

## ✅ Checklist Rapide

Avant de soumettre des IPs :

- [ ] Serveur démarré : `./roguebb.sh status`
- [ ] Fichier d'IPs créé (si nécessaire)
- [ ] Format correct (une IP par ligne)
- [ ] Commande de soumission prête

**C'est tout !** 🚀

---

## 🎯 Commandes Favorites

### Top 5 des commandes les plus utilisées :

1. **Soumettre une IP**
   ```bash
   ./roguebb.sh submit 192.168.1.100
   ```

2. **Soumettre depuis un fichier**
   ```bash
   ./roguebb.sh submit-file mes_ips.txt
   ```

3. **Voir les stats**
   ```bash
   ./roguebb.sh stats
   ```

4. **Vérifier le statut**
   ```bash
   ./roguebb.sh status
   ```

5. **Sauvegarder**
   ```bash
   ./roguebb.sh backup backup_$(date +%Y%m%d).txt
   ```

---

**🎉 Vous êtes maintenant un expert RogueBB ! 🎉**

Pour toute question, tapez :
```bash
./roguebb.sh help
```

---

*Guide Visuel - RogueBB*  
*Mise à jour : 26 octobre 2025*
