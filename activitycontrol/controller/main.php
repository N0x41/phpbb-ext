<?php
/**
 * @Date: 2025-10-27
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: main_controller.php
 * @Description: Contrôleur simplifié avec endpoint /notify sécurisé par RSA
 */
namespace linkguarder\activitycontrol\controller;

class main
{
/* @var \phpbb\config\config */
protected $config;

/* @var \phpbb\controller\helper */
protected $helper;

/* @var \phpbb\user */
protected $user;

/* @var \phpbb\request\request */
protected $request;

/* @var \phpbb\log\log */
protected $log;

/* @var string */
protected $ext_path;

/* @var \linkguarder\activitycontrol\service\server_authenticator */
protected $server_authenticator;

public function __construct(
\phpbb\config\config $config, 
\phpbb\controller\helper $helper, 
\phpbb\user $user,
\phpbb\request\request $request,
\phpbb\log\log $log,
$ext_path,
\linkguarder\activitycontrol\service\server_authenticator $server_authenticator = null
)
{
$this->config = $config;
$this->helper = $helper;
$this->user = $user;
$this->request = $request;
$this->log = $log;
$this->ext_path = $ext_path;
$this->server_authenticator = $server_authenticator;
}

/**
 * Endpoint /notify - Communication bidirectionnelle sécurisée avec le serveur RogueBB
 * 
 * Flux:
 * 1. Le nœud notifie le serveur RogueBB d'une mise à jour (via POST externe)
 * 2. Le serveur RogueBB appelle cet endpoint avec un fichier signé (via RSA)
 * 3. Le nœud vérifie la signature et écrit le fichier
 * 4. Les autres nœuds reçoivent aussi cette mise à jour du serveur
 * 
 * POST /notify
 * Body (depuis le serveur RogueBB):
 * {
 *   "filename": "update_data.json",
 *   "content": "...",
 *   "token": "{\"timestamp\":1234567890,\"server_id\":\"roguebb-main\"}",
 *   "signature": "base64_signature"
 * }
 * 
 * @return \Symfony\Component\HttpFoundation\JsonResponse
 */
public function notify()
{
$json_response = new \phpbb\json_response();

// Vérifier que c'est une requête POST
if ($this->request->server('REQUEST_METHOD') !== 'POST')
{
return $json_response->send([
'status' => 'error',
'message' => 'Only POST requests are allowed'
], 405);
}

// Récupérer les données JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data)
{
return $json_response->send([
'status' => 'error',
'message' => 'Invalid JSON data'
], 400);
}

// Vérifier les champs requis pour l'authentification RSA
$required_fields = ['filename', 'content', 'token', 'signature'];
foreach ($required_fields as $field)
{
if (!isset($data[$field]))
{
return $json_response->send([
'status' => 'error',
'message' => "Missing required field: {$field}"
], 400);
}
}

// Vérifier que le service d'authentification est disponible
if (!$this->server_authenticator)
{
$this->log->add('critical', $this->user->data['user_id'], $this->user->ip, 'AC_NOTIFY_AUTH_SERVICE_UNAVAILABLE');

return $json_response->send([
'status' => 'error',
'message' => 'Authentication service not available'
], 500);
}

// Extraire les données
$filename = $data['filename'];
$content = $data['content'];
$token = $data['token'];
$signature = $data['signature'];

// Tenter de créer le fichier avec authentification RSA
$success = $this->server_authenticator->create_authenticated_file(
$filename,
$content,
$token,
$signature
);

if ($success)
{
	// Calculer le hash du fichier créé
	$file_hash = $this->server_authenticator->get_file_hash($filename);
	
	// Vérifier si le contenu est une liste vide (réinitialisation)
	$content_decoded = json_decode($content, true);
	$is_empty_list = (is_array($content_decoded) && empty($content_decoded));
	
	if ($is_empty_list)
	{
		// Réinitialisation : remettre les valeurs à zéro pour permettre le ré-enregistrement
		$this->config->set('ac_last_ip_sync', 0);
		$this->config->set('ac_ip_list_version', 0);
		
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'AC_NOTIFY_RESET', time(), [
			$filename
		]);
		
		return $json_response->send([
			'status' => 'ok',
			'message' => 'Node reset successfully',
			'filename' => $filename,
			'reset' => true,
			'timestamp' => time()
		]);
	}
	
	// Extraire le version_hash du token si présent
	$token_data = json_decode($token, true);
	$version_hash = 'unknown';
	if (isset($token_data['version_hash']))
	{
		$version_hash = $token_data['version_hash'];
		$this->config->set('ac_ip_list_version', $version_hash);
	}
	
	// Mettre à jour la date de dernière synchronisation
	$this->config->set('ac_last_ip_sync', time());

	// Logger uniquement les mises à jour de la liste d'IPs principale avec la version
	if ($filename === 'reported_ips.json')
	{
		// Utiliser un reportee_id spécial pour identifier Activity Control comme source
		// On utilise 0 (système) et on ajoutera le nom dans le message
		$this->log->add('admin', 0, '', 'LOG_AC_IP_LIST_UPDATED', time(), [
			'Activity Control',  // Nom affiché comme utilisateur
			$version_hash         // Version de la liste
		]);
	}

	return $json_response->send([
		'status' => 'ok',
		'message' => 'Update received and validated',
		'filename' => $filename,
		'size' => strlen($content),
		'hash' => $file_hash,
		'timestamp' => time()
	]);
}
else
{
	// Échec de l'authentification
	$this->log->add('critical', $this->user->data['user_id'], $this->user->ip, 'AC_NOTIFY_AUTH_FAILED', time(), [
		$filename,
		$this->user->ip
	]);

	return $json_response->send([
		'status' => 'error',
		'message' => 'Authentication failed: invalid signature or expired token',
		'filename' => $filename
	], 403);
}
	}
}
