<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: ip_ban_sync.php
 */
namespace linkguarder\activitycontrol\service;

class ip_ban_sync
{
    protected $config;
    protected $db;
    protected $user;
    protected $log;
    protected $phpbb_root_path;
    protected $php_ext;

    public function __construct($config, $db, $user, $log, $phpbb_root_path, $php_ext)
    {
        $this->config = $config;
        $this->db = $db;
        $this->user = $user;
        $this->log = $log;
        $this->phpbb_root_path = $phpbb_root_path;
        $this->php_ext = $php_ext;
    }
    
    protected function get_server_url()
    {
        return $this->config['ac_central_server_url'] ?: 'http://localhost:5000';
    }

    public function sync()
    {
        $remote_data = $this->fetch_remote_ip_list($this->get_server_url());
        
        if (!$remote_data['success'])
        {
            $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AC_IP_SYNC_FAILED', time(), [$remote_data['message']]);
            return $remote_data;
        }

        $result = $this->sync_ip_list($remote_data['ips'], $remote_data['version']);

        if ($result['success'])
        {
            $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_AC_IP_SYNC_SUCCESS', time(), [
                $result['added'],
                $result['removed'],
                $result['total']
            ]);
        }

        return $result;
    }

    protected function fetch_remote_ip_list($server_url)
    {
        $api_url = rtrim($server_url, '/') . '/api/get_ips';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "User-Agent: phpBB-ActivityControl/1.0\r\n"
            ]
        ]);

        $response = @file_get_contents($api_url, false, $context);

        if ($response === false)
        {
            return [
                'success' => false,
                'message' => 'Failed to connect to central server'
            ];
        }

        $data = json_decode($response, true);

        if (!isset($data['ips']) || !is_array($data['ips']))
        {
            return [
                'success' => false,
                'message' => 'Invalid response format from server'
            ];
        }

        return [
            'success' => true,
            'ips' => $data['ips'],
            'version' => isset($data['version']) ? $data['version'] : 0,
            'count' => count($data['ips'])
        ];
    }

    protected function sync_ip_list($remote_ips, $version)
    {
        $remote_ips_set = array_flip($remote_ips);

        $sql = 'SELECT ban_ip, ban_id FROM ' . BANLIST_TABLE . ' WHERE ban_ip != ""';
        $result = $this->db->sql_query($sql);
        
        $local_ips = [];
        $ban_ids = [];
        while ($row = $this->db->sql_fetchrow($result))
        {
            $local_ips[] = $row['ban_ip'];
            $ban_ids[$row['ban_ip']] = $row['ban_id'];
        }
        $this->db->sql_freeresult($result);

        $local_ips_set = array_flip($local_ips);

        $ips_to_add = array_diff_key($remote_ips_set, $local_ips_set);
        $ips_to_remove = array_diff_key($local_ips_set, $remote_ips_set);

        $added_count = 0;
        $removed_count = 0;

        if (!empty($ips_to_add))
        {
            $ips_to_add_array = array_keys($ips_to_add);
            $chunks = array_chunk($ips_to_add_array, 100);
            
            foreach ($chunks as $chunk)
            {
                if ($this->add_ip_bans_batch($chunk))
                {
                    $added_count += count($chunk);
                }
            }
        }

        if (!empty($ips_to_remove))
        {
            $ban_ids_to_remove = [];
            foreach (array_keys($ips_to_remove) as $ip)
            {
                if (isset($ban_ids[$ip]))
                {
                    $ban_ids_to_remove[] = $ban_ids[$ip];
                }
            }
            
            if (!empty($ban_ids_to_remove) && $this->remove_ip_bans_batch($ban_ids_to_remove))
            {
                $removed_count = count($ban_ids_to_remove);
            }
        }

        $this->config->set('ac_last_ip_sync', time());
        $this->config->set('ac_ip_list_version', $version);

        return [
            'success' => true,
            'added' => $added_count,
            'removed' => $removed_count,
            'total' => count($remote_ips),
            'message' => sprintf('Sync completed: %d added, %d removed, %d total', $added_count, $removed_count, count($remote_ips))
        ];
    }

    protected function add_ip_ban($ip)
    {
        $ban_reason = $this->config['ac_ban_reason'] ?: 'Activity Control - Central Ban List';
        $ban_give_reason = 'Automatically banned by Activity Control';

        if (!function_exists('user_ban'))
        {
            include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
        }

        $result = user_ban('ip', [$ip], 0, 0, false, $ban_reason, $ban_give_reason);

        return !empty($result);
    }

    protected function add_ip_bans_batch($ips)
    {
        if (empty($ips))
        {
            return true;
        }

        $ban_reason = $this->config['ac_ban_reason'] ?: 'Activity Control - Central Ban List';
        $ban_give_reason = 'Automatically banned by Activity Control';

        if (!function_exists('user_ban'))
        {
            include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
        }

        $result = user_ban('ip', $ips, 0, 0, false, $ban_reason, $ban_give_reason);

        return !empty($result);
    }

    protected function remove_ip_ban($ban_id)
    {
        if (!function_exists('user_unban'))
        {
            include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
        }

        $result = user_unban('ip', [$ban_id]);

        return $result;
    }

    protected function remove_ip_bans_batch($ban_ids)
    {
        if (empty($ban_ids))
        {
            return true;
        }

        if (!function_exists('user_unban'))
        {
            include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
        }

        $result = user_unban('ip', $ban_ids);

        return $result;
    }

    public function should_sync()
    {
        $last_sync = (int) $this->config['ac_last_ip_sync'];
        $sync_interval = (int) $this->config['ac_ip_sync_interval'];

        if ($sync_interval <= 0)
        {
            $sync_interval = 3600;
        }

        return (time() - $last_sync) >= $sync_interval;
    }
    
    public function test_connection()
    {
        $server_url = $this->get_server_url();
        $api_url = rtrim($server_url, '/') . '/api/health';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 3,
                'header' => "User-Agent: phpBB-ActivityControl/1.0\r\n"
            ]
        ]);

        $start_time = microtime(true);
        $response = @file_get_contents($api_url, false, $context);
        $latency = round((microtime(true) - $start_time) * 1000);

        if ($response === false)
        {
            return [
                'connected' => false,
                'message' => 'Cannot reach RogueBB server',
                'server_url' => $server_url
            ];
        }

        $data = json_decode($response, true);
        
        return [
            'connected' => true,
            'message' => 'Connected to RogueBB',
            'latency' => $latency,
            'server_url' => $server_url,
            'server_data' => $data
        ];
    }
}
