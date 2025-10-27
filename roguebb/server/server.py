#!/usr/bin/env python3
"""
RogueBB Server - Serveur central de gestion d'IPs avec signature RSA
Version simplifiée avec endpoint /notify bidirectionnel
"""

import json
import time
import base64
import hashlib
import uuid
import requests
import threading
from pathlib import Path
from datetime import datetime
from flask import Flask, jsonify, request, render_template_string
from collections import defaultdict
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding

# --- Configuration ---
IP_SOURCE_URL = "https://raw.githubusercontent.com/stamparm/ipsum/master/levels/3.txt"
UPDATE_INTERVAL_SECONDS = 3600  # 1 heure
SERVER_URL = "http://localhost:5000"  # URL publique du serveur pour les clients

# Liste des nœuds enregistrés (sera mise à jour dynamiquement)
NODES = []

# --- Chemins de fichiers ---
PRIVATE_KEY_PATH = Path(__file__).parent / 'private_key.pem'
PUBLIC_KEY_PATH = Path(__file__).parent / 'public_key.pem'
NODES_DB_PATH = Path(__file__).parent / 'nodes.json'  # Base de données des nœuds

# --- Base de données en mémoire ---
master_ip_set = set()
nodes_status = {}  # Statut des nœuds
list_version_hash = None  # Hash au lieu de version incrémentale
server_start_time = None  # Timestamp de démarrage du serveur
data_lock = threading.Lock()

# --- Initialisation Flask ---
app = Flask(__name__)

# --- Chargement des clés RSA ---
def load_private_key():
    """Charge la clé privée RSA"""
    if not PRIVATE_KEY_PATH.exists():
        print(f"[Erreur] Clé privée introuvable : {PRIVATE_KEY_PATH}")
        print("Générez les clés avec: python3 generate_keys.py")
        return None
    
    with open(PRIVATE_KEY_PATH, 'rb') as f:
        private_key = serialization.load_pem_private_key(
            f.read(),
            password=None
        )
    return private_key

PRIVATE_KEY = load_private_key()

# --- Gestion de la persistence des nœuds ---
def save_nodes():
    """Sauvegarde la liste des nœuds dans nodes.json (doit être appelé AVEC data_lock déjà acquis)"""
    try:
        nodes_data = {
            'last_updated': time.time(),
            'nodes': NODES
        }
        
        with open(NODES_DB_PATH, 'w') as f:
            json.dump(nodes_data, f, indent=2)
        
        print(f"[Nodes DB] ✓ Sauvegarde de {len(NODES)} nœud(s)")
        return True
    except Exception as e:
        print(f"[Nodes DB] ✗ Erreur de sauvegarde: {e}")
        return False

def load_nodes():
    """Charge la liste des nœuds depuis nodes.json"""
    global NODES
    
    if not NODES_DB_PATH.exists():
        print(f"[Nodes DB] Aucune base de nœuds existante")
        return False
    
    try:
        with open(NODES_DB_PATH, 'r') as f:
            nodes_data = json.load(f)
        
        NODES = nodes_data.get('nodes', [])
        last_updated = nodes_data.get('last_updated', 0)
        
        print(f"[Nodes DB] ✓ Chargement de {len(NODES)} nœud(s)")
        if last_updated:
            last_update_str = datetime.fromtimestamp(last_updated).strftime('%Y-%m-%d %H:%M:%S')
            print(f"[Nodes DB]   Dernière mise à jour: {last_update_str}")
        
        return True
    except Exception as e:
        print(f"[Nodes DB] ✗ Erreur de chargement: {e}")
        NODES = []
        return False

# --- Fonctions de signature RSA ---
def create_signed_token(server_id='roguebb-main'):
    """Crée un token signé avec timestamp"""
    token_data = {
        'timestamp': int(time.time()),
        'server_id': server_id
    }
    token_json = json.dumps(token_data, separators=(',', ':'))
    
    if not PRIVATE_KEY:
        raise Exception("Clé privée non chargée")
    
    # Signer le token
    signature = PRIVATE_KEY.sign(
        token_json.encode('utf-8'),
        padding.PKCS1v15(),
        hashes.SHA256()
    )
    
    signature_b64 = base64.b64encode(signature).decode('utf-8')
    
    return token_json, signature_b64

def notify_node(node_url, filename, content):
    """
    Notifie un nœud phpBB en envoyant un fichier signé
    
    Args:
        node_url: URL du nœud (ex: http://localhost:8080/forum)
        filename: Nom du fichier à créer
        content: Contenu du fichier (string)
    """
    try:
        # Créer le token signé
        token, signature = create_signed_token()
        
        # Préparer la requête
        endpoint = f'{node_url.rstrip("/")}/app.php/notify'
        payload = {
            'filename': filename,
            'content': content,
            'token': token,
            'signature': signature
        }
        
        # Envoyer
        response = requests.post(
            endpoint,
            json=payload,
            headers={'Content-Type': 'application/json'},
            timeout=10
        )
        
        if response.status_code == 200:
            result = response.json()
            if result.get('status') == 'ok':
                print(f"[Notify] ✓ {node_url} - Fichier '{filename}' créé ({result.get('size')} octets)")
                return True
            else:
                print(f"[Notify] ✗ {node_url} - Erreur: {result.get('message')}")
                return False
        else:
            print(f"[Notify] ✗ {node_url} - HTTP {response.status_code}")
            return False
            
    except Exception as e:
        print(f"[Notify] ✗ {node_url} - Exception: {e}")
        return False

def broadcast_update(filename, content):
    """Diffuse une mise à jour vers tous les nœuds actifs"""
    print(f"\n[Broadcast] Diffusion de '{filename}' vers {len(NODES)} nœud(s)...")
    
    success_count = 0
    for node in NODES:
        if not node.get('enabled', True):
            print(f"[Broadcast] - {node['name']}: désactivé")
            continue
        
        success = notify_node(node['url'], filename, content)
        if success:
            success_count += 1
            
            # Mettre à jour le statut du nœud
            with data_lock:
                nodes_status[node['name']] = {
                    'url': node['url'],
                    'last_notified': time.time(),
                    'status': 'ok'
                }
        else:
            with data_lock:
                nodes_status[node['name']] = {
                    'url': node['url'],
                    'last_notified': time.time(),
                    'status': 'error'
                }
    
    print(f"[Broadcast] Terminé: {success_count}/{len(NODES)} nœuds mis à jour\n")
    return success_count

# --- Gestion de la liste d'IPs ---
def generate_version_hash():
    """Génère un hash aléatoire pour la version"""
    return hashlib.sha256(str(uuid.uuid4()).encode()).hexdigest()[:16]

def update_and_broadcast():
    """Met à jour la version et diffuse vers tous les nœuds"""
    global list_version_hash
    list_version_hash = generate_version_hash()
    print(f"[Version] Liste mise à jour → hash {list_version_hash}")
    
    # Créer le contenu reported_ips.json
    ips_list = list(master_ip_set)
    content = json.dumps(ips_list, separators=(',', ':'))
    
    # Diffuser vers tous les nœuds
    threading.Thread(
        target=broadcast_update,
        args=('reported_ips.json', content),
        daemon=True
    ).start()

def fetch_ip_list_from_source():
    """Récupère la liste d'IPs depuis la source externe"""
    print(f"[Updater] Récupération depuis {IP_SOURCE_URL}...")
    try:
        response = requests.get(IP_SOURCE_URL, timeout=10)
        if response.status_code == 200:
            new_ips = set(response.text.split())
            with data_lock:
                added_count = len(new_ips - master_ip_set)
                master_ip_set.update(new_ips)
                if added_count > 0:
                    update_and_broadcast()
            print(f"[Updater] ✓ {added_count} nouvelles IPs. Total: {len(master_ip_set)}")
        else:
            print(f"[Updater] ✗ HTTP {response.status_code}")
    except Exception as e:
        print(f"[Updater] ✗ Erreur: {e}")

def periodic_updater():
    """Thread de mise à jour périodique"""
    while True:
        time.sleep(UPDATE_INTERVAL_SECONDS)
        fetch_ip_list_from_source()

# --- API Endpoints ---

@app.route('/')
def index():
    """Dashboard HTML"""
    html = """
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>RogueBB Server Dashboard</title>
        <meta http-equiv="refresh" content="30">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            h1 { color: #333; }
            .stat { font-size: 20px; margin: 10px 0; }
            .node { background: #f9f9f9; padding: 10px; margin: 5px 0; border-radius: 4px; }
            .status-ok { color: green; font-weight: bold; }
            .status-error { color: red; font-weight: bold; }
            .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            .btn:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <h1>🛡️ RogueBB Server Dashboard</h1>
        
        <div class="container">
            <h2>📊 Statistiques Globales</h2>
            <div class="stat">Version de la liste: <strong>{{ version }}</strong></div>
            <div class="stat">IPs totales: <strong>{{ total_ips }}</strong></div>
            <div class="stat">Dernière mise à jour: {{ last_update }}</div>
        </div>
        
        <div class="container">
            <h2>🌐 Nœuds Connectés ({{ node_count }})</h2>
            {% for name, info in nodes.items() %}
            <div class="node">
                <strong>{{ name }}</strong> - {{ info.url }}
                <br>
                Statut: <span class="status-{{ info.status }}">{{ info.status|upper }}</span>
                {% if info.last_notified %}
                <br>Dernière notification: {{ info.last_notified_str }}
                {% endif %}
            </div>
            {% else %}
            <div class="node">Aucun nœud notifié pour le moment</div>
            {% endfor %}
        </div>
        
        <div class="container">
            <h2>🔧 Actions</h2>
            <form action="/api/force_update" method="POST">
                <button type="submit" class="btn">Forcer une mise à jour immédiate</button>
            </form>
        </div>
    </body>
    </html>
    """
    
    with data_lock:
        nodes_info = dict(nodes_status)
        for name, info in nodes_info.items():
            if info.get('last_notified'):
                info['last_notified_str'] = datetime.fromtimestamp(info['last_notified']).strftime('%Y-%m-%d %H:%M:%S')
        
        return render_template_string(
            html,
            version=list_version_hash or 'N/A',
            total_ips=len(master_ip_set),
            last_update=datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            nodes=nodes_info,
            node_count=len(nodes_info)
        )

@app.route('/api/health', methods=['GET'])
def health_check():
    """
    Endpoint de vérification de santé du serveur
    Utilisé par les nodes pour tester la connexion
    """
    uptime = int(time.time() - server_start_time) if server_start_time else 0
    
    return jsonify({
        'status': 'ok',
        'version': list_version_hash or 'N/A',
        'total_ips': len(master_ip_set),
        'total_nodes': len(NODES),
        'uptime': uptime
    })

@app.route('/api/get_ips', methods=['GET'])
def get_ips():
    """
    Retourne la liste complète des IPs pour synchronisation manuelle
    Utilisé par le bouton "Sync Now" dans l'ACP phpBB
    """
    with data_lock:
        return jsonify({
            'status': 'ok',
            'ips': list(master_ip_set),
            'version': list_version_hash or 'N/A',
            'count': len(master_ip_set)
        })

@app.route('/api/register', methods=['POST'])
def register_node():
    """
    Enregistre un nouveau nœud phpBB
    Appelé automatiquement lors de l'activation de l'extension
    """
    data = request.get_json()
    
    if not data or 'forum_url' not in data:
        return jsonify({'status': 'error', 'message': 'Missing forum_url'}), 400
    
    forum_url = data.get('forum_url')
    forum_name = data.get('forum_name', 'Unknown Forum')
    
    print(f"\n[Register] Nouveau nœud: {forum_name} ({forum_url})")
    
    # Vérifier si le nœud existe déjà
    with data_lock:
        node_exists = any(n['url'] == forum_url for n in NODES)
        
        if not node_exists:
            NODES.append({
                'name': forum_name,
                'url': forum_url,
                'enabled': True,
                'registered_at': time.time()
            })
            print(f"[Register] ✓ Nœud ajouté. Total: {len(NODES)}")
            
            # Sauvegarder la liste des nœuds
            save_nodes()
        else:
            print(f"[Register] ℹ Nœud déjà enregistré")
    
    # Envoyer immédiatement la liste d'IPs au nouveau nœud
    if master_ip_set:
        ips_list = list(master_ip_set)
        content = json.dumps(ips_list, separators=(',', ':'))
        
        # Envoyer en arrière-plan
        threading.Thread(
            target=notify_node,
            args=(forum_url, 'reported_ips.json', content),
            daemon=True
        ).start()
        
        print(f"[Register] 📤 Envoi de {len(ips_list)} IPs vers {forum_name}")
    
    return jsonify({
        'status': 'ok',
        'message': 'Node registered successfully'
    })

@app.route('/api/node_notification', methods=['POST'])
def node_notification():
    """
    Reçoit les notifications des nœuds phpBB
    Quand un nœud met à jour sa liste locale, il notifie le serveur ici
    """
    data = request.get_json()
    
    if not data or 'event' not in data:
        return jsonify({'status': 'error', 'message': 'Invalid data'}), 400
    
    event = data.get('event')
    node_name = data.get('node_name', 'Unknown')
    
    print(f"\n[Node Notification] Reçu de '{node_name}': {event}")
    
    # Traiter selon le type d'événement
    if event == 'ip_list_updated':
        # Un nœud a mis à jour sa liste locale
        # On va propager cette mise à jour vers tous les autres nœuds
        
        with data_lock:
            update_and_broadcast()
        
        return jsonify({
            'status': 'ok',
            'message': 'Update will be propagated to all nodes',
            'version_hash': list_version_hash
        })
    
    return jsonify({'status': 'ok', 'message': 'Notification received'})

@app.route('/api/force_update', methods=['POST'])
def force_update():
    """Force une mise à jour et diffusion immédiate"""
    fetch_ip_list_from_source()
    return jsonify({
        'status': 'ok',
        'message': 'Update forced',
        'version_hash': list_version_hash,
        'total_ips': len(master_ip_set)
    })

@app.route('/api/status')
def status():
    """Retourne le statut du serveur"""
    with data_lock:
        return jsonify({
            'status': 'ok',
            'version_hash': list_version_hash,
            'total_ips': len(master_ip_set),
            'nodes_count': len(nodes_status),
            'timestamp': int(time.time())
        })

# --- Démarrage ---
if __name__ == '__main__':
    server_start_time = time.time()
    
    print("=" * 60)
    print("🛡️  RogueBB Server - Système de gestion d'IPs centralisé")
    print("=" * 60)
    
    if not PRIVATE_KEY:
        print("\n❌ ERREUR: Clé privée RSA non trouvée!")
        print("Générez les clés avec: python3 generate_keys.py")
        exit(1)
    
    print(f"\n✓ Clé privée RSA chargée: {PRIVATE_KEY_PATH}")
    
    # Charger les nœuds sauvegardés
    load_nodes()
    print(f"✓ {len(NODES)} nœud(s) configuré(s)\n")
    
    # Récupération initiale
    fetch_ip_list_from_source()
    
    # Lancer le thread de mise à jour périodique
    updater_thread = threading.Thread(target=periodic_updater, daemon=True)
    updater_thread.start()
    print(f"✓ Mise à jour périodique activée (intervalle: {UPDATE_INTERVAL_SECONDS}s)\n")
    
    # Lancer le serveur Flask
    print("🚀 Serveur démarré sur http://0.0.0.0:5000")
    print("=" * 60 + "\n")
    
    app.run(host='0.0.0.0', port=5000, debug=False)
