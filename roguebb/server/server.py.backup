import requests
import threading
import time
from flask import Flask, jsonify, request, render_template_string, redirect, url_for
from collections import defaultdict

# --- Configuration ---
IP_SOURCE_URL = "https://raw.githubusercontent.com/stamparm/ipsum/master/levels/3.txt"
UPDATE_INTERVAL_SECONDS = 3600  # 1 heure

# --- Configuration des webhooks ---
WEBHOOK_URLS = [
    # Ajoutez ici les URLs de vos clients à notifier
    # Exemple: "http://votre-forum.com/app.php/activitycontrol/webhook/notify"
]

# --- Base de données en mémoire ---
master_ip_set = set()
nodes = {}
node_added_ips = defaultdict(set)
list_version = 0  # NOUVEAU: Pour le suivi des mises à jour
data_lock = threading.Lock()

# --- Initialisation de l'application Flask ---
app = Flask(__name__)

# --- Fonction utilitaire de mise à jour de la version ---
def increment_list_version():
    """Incrémente la version de la liste (doit être appelée à l'intérieur d'un data_lock)"""
    global list_version
    list_version += 1
    print(f"[Version] La liste est maintenant à la version {list_version}")
    
    # Notifier les webhooks après l'incrémentation
    notify_webhooks()

def notify_webhooks():
    """Envoie des notifications aux URLs de webhook configurées"""
    if not WEBHOOK_URLS:
        return
    
    # Préparer les données de notification
    notification_data = {
        'event': 'ip_list_updated',
        'version': list_version,
        'total_ips': len(master_ip_set),
        'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
    }
    
    print(f"[Webhook] Envoi de notifications à {len(WEBHOOK_URLS)} client(s)...")
    
    # Envoyer les notifications dans un thread séparé pour ne pas bloquer
    for webhook_url in WEBHOOK_URLS:
        threading.Thread(
            target=send_webhook_notification,
            args=(webhook_url, notification_data),
            daemon=True
        ).start()

def send_webhook_notification(webhook_url, data):
    """Envoie une notification webhook à une URL spécifique"""
    try:
        response = requests.post(
            webhook_url,
            json=data,
            timeout=10,
            headers={'Content-Type': 'application/json'}
        )
        
        if response.status_code == 200:
            result = response.json()
            print(f"[Webhook] ✓ Notification envoyée à {webhook_url}")
            if result.get('synced'):
                stats = result.get('stats', {})
                print(f"[Webhook]   → Client synchronisé: {stats.get('added', 0)} ajoutées, "
                      f"{stats.get('removed', 0)} retirées, {stats.get('total', 0)} total")
        else:
            print(f"[Webhook] ✗ Erreur HTTP {response.status_code} pour {webhook_url}")
            
    except requests.RequestException as e:
        print(f"[Webhook] ✗ Échec d'envoi à {webhook_url}: {e}")
    except Exception as e:
        print(f"[Webhook] ✗ Erreur inattendue pour {webhook_url}: {e}")

# --- Tâches de fond ---
def fetch_ip_list_from_source():
    """
    Logique de récupération de la liste.
    Appelée au démarrage et par l'updater périodique.
    """
    print(f"[Updater] Récupération des IP depuis {IP_SOURCE_URL}...")
    try:
        response = requests.get(IP_SOURCE_URL, timeout=10)
        if response.status_code == 200:
            new_ips = set(response.text.split())
            with data_lock:
                added_count = len(new_ips - master_ip_set)
                master_ip_set.update(new_ips)
                if added_count > 0:
                    increment_list_version()
            print(f"[Updater] Succès. {added_count} nouvelles IP ajoutées. Total : {len(master_ip_set)}")
        else:
            print(f"[Updater] Erreur : Code de statut {response.status_code}")
    except requests.RequestException as e:
        print(f"[Updater] Erreur lors de la récupération de la liste : {e}")

def periodic_ip_list_updater():
    """Thread de fond pour mettre à jour la liste périodiquement."""
    while True:
        # Attend d'abord, car la première récupération est faite au démarrage
        time.sleep(UPDATE_INTERVAL_SECONDS)
        fetch_ip_list_from_source()

# --- Interface Web (Dashboard) ---
@app.route('/')
def index():
    """Sert le tableau de bord HTML principal."""
    
    html_template = """
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard IP Distribué</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f4; }
            h1, h2 { color: #333; }
            .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;}
            .stat { font-size: 24px; margin: 10px 0; }
            .stat-small { font-size: 18px; color: #555; }
            .nodes { margin-top: 20px; }
            ul { list-style-type: none; padding-left: 0; }
            li { background: #fafafa; border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;}
            .ip-item { flex-grow: 1; }
            .btn-delete { color: red; text-decoration: none; font-weight: bold; padding: 5px; }
            .btn-reset { background-color: #d9534f; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        </style>
        <meta http-equiv="refresh" content="30">
    </head>
    <body>
        <h1>Dashboard IP Distribué</h1>

        <div class="container">
            <h2>Statut Global</h2>
            <div class="stat">
                <strong>IP Uniques Totales :</strong> {{ total_ips }}
            </div>
            <div class="stat-small">
                <strong>Version de la liste :</strong> {{ current_version }}
            </div>
            <br>
            <form action="/api/reset_list" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser TOUTE la liste ?');">
                <button type="submit" class="btn-reset">Réinitialiser la Liste</button>
            </form>
        </div>
        
        <div class="container">
            <h2>Nœuds Actifs ({{ node_count }})</h2>
            <ul class="nodes">
            {% for node, info in active_nodes.items() %}
                <li>
                    <span><strong>Nœud :</strong> {{ node }} | <em>Vu le :</em> {{ info.last_seen_str }}</span>
                </li>
            {% else %}
                <li>Aucun nœud actif détecté.</li>
            {% endfor %}
            </ul>
        </div>

        <div class="container">
            <h2>IP Ajoutées par les Nœuds</h2>
            <ul class="contributions">
            {% for node, ips in node_ips.items() %}
                <p><strong>Nœud {{ node }} ({{ ips|length }} IP):</strong></p>
                {% for ip in ips %}
                <li>
                    <span class="ip-item">{{ ip }}</span>
                    <a href="/api/delete_ip?ip={{ ip }}" class="btn-delete" title="Supprimer cette IP">[X]</a>
                </li>
                {% endfor %}
            {% else %}
                <li>Aucune IP soumise par les nœuds.</li>
            {% endfor %}
            </ul>
        </div>
    </body>
    </html>
    """
    
    with data_lock:
        total_count = len(master_ip_set)
        node_list = dict(nodes)
        contribution_list = {k: sorted(list(v)) for k, v in node_added_ips.items()}
        version = list_version
        
    for node, info in node_list.items():
        info['last_seen_str'] = time.ctime(info['last_seen'])
        
    return render_template_string(
        html_template,
        total_ips=total_count,
        current_version=version,
        node_count=len(node_list),
        active_nodes=node_list,
        node_ips=contribution_list
    )

# --- API pour les Nœuds ---

def register_node(node_ip):
    """Met à jour le timestamp 'last_seen' d'un nœud."""
    with data_lock:
        nodes[node_ip] = {'last_seen': time.time()}

@app.route('/api/get_version', methods=['GET'])
def get_version():
    """
    NOUVEAU: Endpoint léger pour que les clients vérifient la version de la liste.
    """
    return jsonify({'version': list_version})

@app.route('/api/get_ips', methods=['GET'])
def get_ips():
    """
    Endpoint pour que les nœuds obtiennent la liste complète.
    Renvoie maintenant aussi la version.
    """
    node_ip = request.remote_addr
    register_node(node_ip)
    
    print(f"[API] Le nœud {node_ip} demande la liste des IP.")
    with data_lock:
        # Renvoie une copie de la liste ET la version actuelle
        return jsonify({
            'version': list_version,
            'ips': list(master_ip_set)
        })

@app.route('/api/submit_ip', methods=['POST'])
def submit_ip():
    """Endpoint pour que les nœuds soumettent une nouvelle IP."""
    node_ip = request.remote_addr
    register_node(node_ip)
    
    data = request.json
    if not data or 'ip' not in data:
        return jsonify({'status': 'error', 'message': 'IP "ip" manquante'}), 400
        
    new_ip = data['ip'].strip()
    
    with data_lock:
        if new_ip not in master_ip_set:
            master_ip_set.add(new_ip)
            node_added_ips[node_ip].add(new_ip)
            status = 'added'
            increment_list_version()  # La liste a changé, on incrémente la version
        else:
            status = 'already_exists'
            
    print(f"[API] Le nœud {node_ip} a soumis l'IP : {new_ip} (statut : {status})")
    
    return jsonify({
        'status': status,
        'submitted_ip': new_ip,
        'total_ips': len(master_ip_set),
        'new_version': list_version
    })

@app.route('/api/heartbeat', methods=['POST'])
def heartbeat():
    """Endpoint pour que les nœuds signalent qu'ils sont en ligne."""
    node_ip = request.remote_addr
    register_node(node_ip)
    return jsonify({'status': 'ok'})

# --- NOUVEAUX Endpoints pour l'UI Admin ---

@app.route('/api/delete_ip', methods=['GET'])
def delete_ip():
    """
    NOUVEAU: Endpoint pour supprimer une IP via l'interface web.
    """
    ip_to_delete = request.args.get('ip')
    if not ip_to_delete:
        return "Erreur : IP non spécifiée", 400
    
    with data_lock:
        if ip_to_delete in master_ip_set:
            master_ip_set.remove(ip_to_delete)
            # Retirer aussi des listes de contribution
            for node in node_added_ips:
                node_added_ips[node].discard(ip_to_delete)
            
            increment_list_version() # La liste a changé
            print(f"[Admin] IP {ip_to_delete} supprimée par l'administrateur.")
        else:
            print(f"[Admin] Tentative de suppression d'une IP inexistante : {ip_to_delete}")
            
    return redirect(url_for('index')) # Redirige vers la page d'accueil

@app.route('/api/reset_list', methods=['POST'])
def reset_list():
    """
    NOUVEAU: Endpoint pour réinitialiser la liste complète.
    """
    with data_lock:
        master_ip_set.clear()
        node_added_ips.clear()
        # On garde les nœuds pour voir qui est connecté
        
        print("[Admin] La liste a été réinitialisée par l'administrateur.")
        # On force une nouvelle version
        increment_list_version()
        
    # Déclenche une récupération immédiate dans un nouveau thread
    # pour ne pas bloquer la réponse à l'utilisateur
    threading.Thread(target=fetch_ip_list_from_source).start()
    
    return redirect(url_for('index'))

# --- Endpoints de gestion des webhooks ---

@app.route('/api/webhooks', methods=['GET'])
def get_webhooks():
    """
    Récupère la liste des webhooks configurés.
    """
    return jsonify({
        'webhooks': WEBHOOK_URLS,
        'count': len(WEBHOOK_URLS)
    })

@app.route('/api/webhooks/add', methods=['POST'])
def add_webhook():
    """
    Ajoute une URL de webhook à la liste.
    """
    data = request.json
    if not data or 'url' not in data:
        return jsonify({'status': 'error', 'message': 'URL manquante'}), 400
    
    webhook_url = data['url'].strip()
    
    if not webhook_url.startswith('http'):
        return jsonify({'status': 'error', 'message': 'URL invalide (doit commencer par http)'}), 400
    
    if webhook_url in WEBHOOK_URLS:
        return jsonify({
            'status': 'already_exists',
            'message': 'Cette URL existe déjà',
            'webhook': webhook_url
        })
    
    WEBHOOK_URLS.append(webhook_url)
    print(f"[Webhook] Nouveau webhook ajouté: {webhook_url}")
    
    return jsonify({
        'status': 'added',
        'message': 'Webhook ajouté avec succès',
        'webhook': webhook_url,
        'total_webhooks': len(WEBHOOK_URLS)
    })

@app.route('/api/webhooks/remove', methods=['POST'])
def remove_webhook():
    """
    Retire une URL de webhook de la liste.
    """
    data = request.json
    if not data or 'url' not in data:
        return jsonify({'status': 'error', 'message': 'URL manquante'}), 400
    
    webhook_url = data['url'].strip()
    
    if webhook_url not in WEBHOOK_URLS:
        return jsonify({
            'status': 'not_found',
            'message': 'Cette URL n\'existe pas',
            'webhook': webhook_url
        })
    
    WEBHOOK_URLS.remove(webhook_url)
    print(f"[Webhook] Webhook retiré: {webhook_url}")
    
    return jsonify({
        'status': 'removed',
        'message': 'Webhook retiré avec succès',
        'webhook': webhook_url,
        'total_webhooks': len(WEBHOOK_URLS)
    })

@app.route('/api/webhooks/test', methods=['POST'])
def test_webhook():
    """
    Teste l'envoi d'une notification à un webhook spécifique.
    """
    data = request.json
    if not data or 'url' not in data:
        return jsonify({'status': 'error', 'message': 'URL manquante'}), 400
    
    webhook_url = data['url'].strip()
    
    # Envoyer une notification de test
    test_data = {
        'event': 'test_notification',
        'version': list_version,
        'total_ips': len(master_ip_set),
        'timestamp': time.strftime('%Y-%m-%d %H:%M:%S'),
        'message': 'This is a test notification from RogueBB'
    }
    
    try:
        response = requests.post(
            webhook_url,
            json=test_data,
            timeout=10,
            headers={'Content-Type': 'application/json'}
        )
        
        return jsonify({
            'status': 'success',
            'message': 'Test envoyé avec succès',
            'webhook': webhook_url,
            'http_code': response.status_code,
            'response': response.text[:500]  # Limiter la taille de la réponse
        })
        
    except requests.RequestException as e:
        return jsonify({
            'status': 'error',
            'message': f'Échec du test: {str(e)}',
            'webhook': webhook_url
        }), 500


# --- Exécution Principale ---
if __name__ == '__main__':
    # 1. Faire une première récupération de la liste au démarrage
    fetch_ip_list_from_source()

    # 2. Démarrer le thread de mise à jour périodique
    updater_thread = threading.Thread(target=periodic_ip_list_updater, daemon=True)
    updater_thread.start()
    
    # 3. Démarrer le serveur Flask
    print("[Serveur] Démarrage du serveur Flask sur http://0.0.0.0:5000")
    app.run(host='0.0.0.0', port=5000)