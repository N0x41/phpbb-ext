<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: main_controller.php
 */
namespace linkguarder\activitycontrol\controller;

class main
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var \phpbb\request\request */
	protected $request;

	/* @var \phpbb\log\log */
	protected $log;

	/* @var \linkguarder\activitycontrol\service\ip_ban_sync */
	protected $ip_ban_sync;

	/* @var \phpbb\db\driver\driver_interface */
	protected $db;

	/* @var string */
	protected $ext_path;

	public function __construct(
		\phpbb\config\config $config, 
		\phpbb\controller\helper $helper, 
		\phpbb\template\template $template, 
		\phpbb\user $user,
		\phpbb\request\request $request,
		\phpbb\log\log $log,
		\linkguarder\activitycontrol\service\ip_ban_sync $ip_ban_sync,
		\phpbb\db\driver\driver_interface $db,
		$ext_path
	)
    {
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->log = $log;
		$this->ip_ban_sync = $ip_ban_sync;
		$this->db = $db;
		$this->ext_path = $ext_path;
    }

	/**
	 * Endpoint pour recevoir les notifications du serveur RogueBB
	 * Appelé automatiquement quand la liste d'IPs est mise à jour
	 * 
	 * @return void
	 */
	public function webhook_notification()
	{
		// Vérifier que c'est une requête POST
		if ($this->request->server('REQUEST_METHOD') !== 'POST')
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'Only POST requests are allowed'
			]);
		}

		// Récupérer les données JSON
		$json_data = file_get_contents('php://input');
		$data = json_decode($json_data, true);

		if (!$data || !isset($data['event']))
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'Invalid JSON data'
			]);
		}

		// Vérifier le type d'événement
		if ($data['event'] !== 'ip_list_updated')
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'Unknown event type'
			]);
		}

		// Extraire les informations
		$version = isset($data['version']) ? (int)$data['version'] : 0;
		$total_ips = isset($data['total_ips']) ? (int)$data['total_ips'] : 0;
		$timestamp = isset($data['timestamp']) ? $data['timestamp'] : date('Y-m-d H:i:s');

		// Logger la notification reçue
		$this->log->add('admin', ANONYMOUS, '', 'LOG_AC_WEBHOOK_RECEIVED', false, [
			$version,
			$total_ips,
			$timestamp
		]);

		// Vérifier si la synchronisation automatique est activée
		if (!$this->config['ac_enable_ip_sync'])
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'ok',
				'message' => 'Notification received but auto-sync is disabled',
				'synced' => false
			]);
		}

		// Déclencher la synchronisation
		$sync_result = $this->ip_ban_sync->sync();

		if ($sync_result['success'])
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'ok',
				'message' => 'IP list synchronized successfully',
				'synced' => true,
				'stats' => [
					'added' => $sync_result['added'],
					'removed' => $sync_result['removed'],
					'total' => $sync_result['total']
				]
			]);
		}
		else
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'Synchronization failed: ' . $sync_result['message'],
				'synced' => false
			]);
		}
	}

	/**
	 * Endpoint pour recevoir des requêtes du serveur RogueBB
	 * Permet au serveur d'interroger le nœud pour obtenir des informations
	 * 
	 * @return void
	 */
	public function node_query()
	{
		// Vérifier que c'est une requête POST
		if ($this->request->server('REQUEST_METHOD') !== 'POST')
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'Only POST requests are allowed'
			]);
		}

		// Récupérer les données JSON
		$json_data = file_get_contents('php://input');
		$data = json_decode($json_data, true);

		if (!$data || !isset($data['query']))
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'Invalid JSON data or missing query parameter'
			]);
		}

		$query_type = $data['query'];

		// Traiter selon le type de requête
		switch ($query_type)
		{
			case 'status':
				$this->handle_status_query();
				break;

			case 'stats':
				$this->handle_stats_query();
				break;

			case 'sync_now':
				$this->handle_sync_now_query();
				break;

			case 'local_ips':
				$this->handle_local_ips_query();
				break;

			case 'reported_ips':
				$this->handle_reported_ips_query();
				break;

			default:
				$json_response = new \phpbb\json_response();
				$json_response->send([
					'status' => 'error',
					'message' => 'Unknown query type: ' . $query_type
				]);
		}
	}

	/**
	 * Gère la requête de statut
	 * 
	 * @return void
	 */
	protected function handle_status_query()
	{
		$json_response = new \phpbb\json_response();
		$json_response->send([
			'status' => 'ok',
			'node_type' => 'phpbb_forum',
			'extension_version' => '1.0.0',
			'phpbb_version' => $this->config['version'],
			'forum_name' => $this->config['sitename'],
			'sync_enabled' => (bool)$this->config['ac_enable_ip_sync'],
			'reporting_enabled' => (bool)$this->config['ac_enable_ip_reporting'],
			'last_sync' => (int)$this->config['ac_last_ip_sync'],
			'ip_list_version' => (int)$this->config['ac_ip_list_version'],
			'timestamp' => time()
		]);
	}

	/**
	 * Gère la requête de statistiques
	 * 
	 * @return void
	 */
	protected function handle_stats_query()
	{
		// Compter les IPs bannies
		$sql = 'SELECT COUNT(*) as total FROM ' . BANLIST_TABLE . ' WHERE ban_ip != ""';
		$result = $this->db->sql_query($sql);
		$total_banned_ips = (int)$this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		// Compter les utilisateurs actifs
		$sql = 'SELECT COUNT(*) as total FROM ' . USERS_TABLE . ' WHERE user_type = ' . USER_NORMAL;
		$result = $this->db->sql_query($sql);
		$total_users = (int)$this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		// Compter les messages
		$total_posts = (int)$this->config['num_posts'];
		$total_topics = (int)$this->config['num_topics'];

		$json_response = new \phpbb\json_response();
		$json_response->send([
			'status' => 'ok',
			'stats' => [
				'banned_ips' => $total_banned_ips,
				'total_users' => $total_users,
				'total_posts' => $total_posts,
				'total_topics' => $total_topics,
				'last_sync' => (int)$this->config['ac_last_ip_sync'],
				'ip_list_version' => (int)$this->config['ac_ip_list_version']
			],
			'timestamp' => time()
		]);
	}

	/**
	 * Gère la requête de synchronisation immédiate
	 * 
	 * @return void
	 */
	protected function handle_sync_now_query()
	{
		if (!$this->config['ac_enable_ip_sync'])
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'IP synchronization is disabled on this node'
			]);
		}

		$sync_result = $this->ip_ban_sync->sync();

		if ($sync_result['success'])
		{
			$this->log->add('admin', ANONYMOUS, '', 'LOG_AC_REMOTE_SYNC_TRIGGERED', false, [
				$sync_result['added'],
				$sync_result['removed'],
				$sync_result['total']
			]);

			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'ok',
				'message' => 'Synchronization completed',
				'stats' => [
					'added' => $sync_result['added'],
					'removed' => $sync_result['removed'],
					'total' => $sync_result['total']
				],
				'timestamp' => time()
			]);
		}
		else
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'error',
				'message' => 'Synchronization failed: ' . $sync_result['message']
			]);
		}
	}

	/**
	 * Gère la requête des IPs locales bannies
	 * 
	 * @return void
	 */
	protected function handle_local_ips_query()
	{
		$sql = 'SELECT ban_ip FROM ' . BANLIST_TABLE . ' WHERE ban_ip != "" ORDER BY ban_id DESC LIMIT 100';
		$result = $this->db->sql_query($sql);
		
		$ips = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ips[] = $row['ban_ip'];
		}
		$this->db->sql_freeresult($result);

		$json_response = new \phpbb\json_response();
		$json_response->send([
			'status' => 'ok',
			'ips' => $ips,
			'count' => count($ips),
			'note' => 'Limited to 100 most recent entries',
			'timestamp' => time()
		]);
	}

	/**
	 * Gère la requête des IPs signalées par ce nœud
	 * 
	 * @return void
	 */
	protected function handle_reported_ips_query()
	{
		// Charger le fichier des IPs signalées
		$reported_file = $this->ext_path . '/data/reported_ips.json';
		
		if (!file_exists($reported_file))
		{
			$json_response = new \phpbb\json_response();
			$json_response->send([
				'status' => 'ok',
				'ips' => [],
				'count' => 0,
				'timestamp' => time()
			]);
		}

		$json_content = file_get_contents($reported_file);
		$reported_ips = json_decode($json_content, true) ?: [];

		$ips_list = [];
		foreach ($reported_ips as $hash => $data)
		{
			$ips_list[] = [
				'ip' => $data['ip'],
				'reason' => $data['reason'],
				'first_seen' => $data['first_seen'],
				'last_seen' => $data['last_seen'],
				'count' => $data['count'],
				'submitted' => $data['submitted']
			];
		}

		$json_response = new \phpbb\json_response();
		$json_response->send([
			'status' => 'ok',
			'ips' => $ips_list,
			'count' => count($ips_list),
			'timestamp' => time()
		]);
	}
}