<?php
/**
 *
 * Activity Control Extension
 *
 * @copyright (c) 2024 LinkGuarder
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace linkguarder\activitycontrol\service;

/**
 * Service pour gérer l'enregistrement au serveur central RogueBB
 */
class server_registration
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $ext_path;

	/** @var string URL du serveur RogueBB */
	protected $server_url;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config $config
	 * @param \phpbb\user $user
	 * @param string $ext_path
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\user $user, $ext_path)
	{
		$this->config = $config;
		$this->user = $user;
		$this->ext_path = $ext_path;
		
		// Récupérer l'URL du serveur depuis la config
		$this->server_url = $this->config['ac_central_server_url'] ?? 'http://localhost:5000';
	}

	/**
	 * Enregistre ce forum au serveur central
	 *
	 * @return array Résultat de l'enregistrement ['success' => bool, 'message' => string]
	 */
	public function register_to_server()
	{
		// Préparer les données du forum
		$forum_url = $this->get_forum_url();
		$forum_name = $this->config['sitename'];

		$data = [
			'forum_url' => $forum_url,
			'forum_name' => $forum_name,
			'phpbb_version' => $this->config['version'],
			'registered_at' => time()
		];

		// Envoyer la requête d'enregistrement
		$endpoint = rtrim($this->server_url, '/') . '/api/register';

		try {
			$response = $this->send_post_request($endpoint, $data);

			if ($response['http_code'] == 200) {
				$result = json_decode($response['body'], true);

				if ($result && $result['status'] === 'ok') {
					// Enregistrement réussi
					$this->log_event('info', 'Registered to RogueBB server successfully', [
						'server_url' => $this->server_url,
						'message' => $result['message'] ?? 'Registered'
					]);

					return [
						'success' => true,
						'message' => $result['message'] ?? 'Successfully registered to RogueBB server',
						'data' => $result
					];
				}
			}

			// Erreur
			$this->log_event('error', 'Failed to register to RogueBB server', [
				'server_url' => $this->server_url,
				'http_code' => $response['http_code'],
				'response' => substr($response['body'], 0, 500)
			]);

			return [
				'success' => false,
				'message' => 'Failed to register: HTTP ' . $response['http_code']
			];

		} catch (\Exception $e) {
			$this->log_event('error', 'Exception during registration', [
				'message' => $e->getMessage()
			]);

			return [
				'success' => false,
				'message' => 'Exception: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Obtient l'URL publique du forum
	 *
	 * @return string
	 */
	protected function get_forum_url()
	{
		// Construire l'URL du forum à partir de la configuration
		$board_url = $this->config['server_protocol'] . $this->config['server_name'];

		if ($this->config['server_port'] && $this->config['server_port'] != 80 && $this->config['server_port'] != 443) {
			$board_url .= ':' . $this->config['server_port'];
		}

		$board_url .= $this->config['script_path'];

		return rtrim($board_url, '/');
	}

	/**
	 * Envoie une requête POST
	 *
	 * @param string $url
	 * @param array $data
	 * @return array ['http_code' => int, 'body' => string]
	 */
	protected function send_post_request($url, $data)
	{
		$json_data = json_encode($data);

		$options = [
			'http' => [
				'header' => "Content-Type: application/json\r\n",
				'method' => 'POST',
				'content' => $json_data,
				'timeout' => 10,
				'ignore_errors' => true
			]
		];

		$context = stream_context_create($options);
		$response = @file_get_contents($url, false, $context);

		// Extraire le code HTTP
		$http_code = 0;
		if (isset($http_response_header)) {
			foreach ($http_response_header as $header) {
				if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
					$http_code = (int) $matches[1];
					break;
				}
			}
		}

		return [
			'http_code' => $http_code,
			'body' => $response !== false ? $response : ''
		];
	}

	/**
	 * Log un événement (simplifié pour éviter les dépendances)
	 *
	 * @param string $level
	 * @param string $message
	 * @param array $context
	 */
	protected function log_event($level, $message, $context = [])
	{
		// Pour l'instant, on écrit dans un fichier de log simple
		$log_file = $this->ext_path . '/data/registration.log';
		$log_entry = sprintf(
			"[%s] %s: %s %s\n",
			date('Y-m-d H:i:s'),
			strtoupper($level),
			$message,
			$context ? json_encode($context) : ''
		);

		@file_put_contents($log_file, $log_entry, FILE_APPEND);
	}

	/**
	 * Définit l'URL du serveur RogueBB
	 *
	 * @param string $url
	 */
	public function set_server_url($url)
	{
		$this->server_url = $url;
	}

	/**
	 * Obtient l'URL du serveur RogueBB
	 *
	 * @return string
	 */
	public function get_server_url()
	{
		return $this->server_url;
	}
}
