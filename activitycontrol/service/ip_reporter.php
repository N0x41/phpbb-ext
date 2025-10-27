<?php
/**
 * @Date: 2025-10-15
 * @Team: LinkGuarder Team
 * @Extension: Activity Control
 * @File: ip_reporter.php
 */

namespace linkguarder\activitycontrol\service;

class ip_reporter
{
    const CENTRAL_SERVER_URL = 'http://localhost:5000';
    
    protected $ip_list_file;
    protected $private_key_path;
    protected $config;
    protected $log;
    protected $ext_path;
    
    public function __construct(
        \phpbb\config\config $config,
        \phpbb\log\log $log,
        $ext_path
    ) {
        $this->config = $config;
        $this->log = $log;
        $this->ext_path = $ext_path;
        
        $this->ip_list_file = $this->ext_path . '/data/reported_ips.json';
        $this->private_key_path = $this->ext_path . '/data/private_key.pem';
    }
    
    public function report_ip($ip_address, $reason = '', $context = [])
    {
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $this->add_to_local_list($ip_address, $reason, $context);
        
        if ($this->config['ac_enable_ip_reporting'] ?? false) {
            return $this->submit_to_central_server($ip_address);
        }
        
        return true;
    }
    
    protected function add_to_local_list($ip_address, $reason, $context)
    {
        $data_dir = dirname($this->ip_list_file);
        if (!is_dir($data_dir)) {
            @mkdir($data_dir, 0755, true);
        }
        
        $ip_list = [];
        if (file_exists($this->ip_list_file)) {
            $json_content = file_get_contents($this->ip_list_file);
            $ip_list = json_decode($json_content, true) ?: [];
        }
        
        $ip_hash = md5($ip_address);
        if (!isset($ip_list[$ip_hash])) {
            $ip_list[$ip_hash] = [
                'ip' => $ip_address,
                'reason' => $reason,
                'context' => $context,
                'first_seen' => time(),
                'last_seen' => time(),
                'count' => 1,
                'submitted' => false
            ];
        } else {
            $ip_list[$ip_hash]['last_seen'] = time();
            $ip_list[$ip_hash]['count']++;
        }
        
        file_put_contents($this->ip_list_file, json_encode($ip_list, JSON_PRETTY_PRINT));
    }
    
    protected function submit_to_central_server($ip_address)
    {
        if (!file_exists($this->private_key_path)) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_PRIVATE_KEY_MISSING', false, [
                'key_path' => $this->private_key_path
            ]);
            return false;
        }
        
        $private_key = openssl_pkey_get_private(file_get_contents($this->private_key_path));
        
        if ($private_key === false) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_PRIVATE_KEY_INVALID', false, [
                'error' => openssl_error_string()
            ]);
            return false;
        }
        
        $signature = '';
        $success = openssl_sign(
            $ip_address,
            $signature,
            $private_key,
            OPENSSL_ALGO_SHA256
        );
        
        openssl_pkey_free($private_key);
        
        if (!$success) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_SIGNATURE_FAILED');
            return false;
        }
        
        $payload = [
            'ip' => $ip_address,
            'signature' => base64_encode($signature)
        ];
        
        $json_payload = json_encode($payload);
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n" .
                           "Content-Length: " . strlen($json_payload) . "\r\n",
                'content' => $json_payload,
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        
        $response = @file_get_contents(self::CENTRAL_SERVER_URL . '/api/submit_ip', false, $context);
        
        if ($response === false) {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_SERVER_UNREACHABLE', false, [
                'server_url' => self::CENTRAL_SERVER_URL,
                'ip' => $ip_address
            ]);
            return false;
        }
        
        $http_code = 500;
        if (isset($http_response_header)) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $http_code = isset($matches[1]) ? (int)$matches[1] : 500;
        }
        
        if ($http_code === 200) {
            $this->mark_as_submitted($ip_address);
            
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_IP_SUBMITTED', false, [
                'ip' => $ip_address,
                'server' => self::CENTRAL_SERVER_URL
            ]);
            
            return true;
        } else {
            $this->log->add('admin', ANONYMOUS, '', 'LOG_AC_SUBMISSION_FAILED', false, [
                'ip' => $ip_address,
                'http_code' => $http_code,
                'response' => $response
            ]);
            
            return false;
        }
    }
    
    protected function mark_as_submitted($ip_address)
    {
        if (!file_exists($this->ip_list_file)) {
            return;
        }
        
        $ip_list = json_decode(file_get_contents($this->ip_list_file), true) ?: [];
        $ip_hash = md5($ip_address);
        
        if (isset($ip_list[$ip_hash])) {
            $ip_list[$ip_hash]['submitted'] = true;
            $ip_list[$ip_hash]['submitted_time'] = time();
            file_put_contents($this->ip_list_file, json_encode($ip_list, JSON_PRETTY_PRINT));
        }
    }
    
    public function get_reported_ips()
    {
        if (!file_exists($this->ip_list_file)) {
            return [];
        }
        
        $json_content = file_get_contents($this->ip_list_file);
        return json_decode($json_content, true) ?: [];
    }
    
    public function cleanup_old_entries($max_age = 2592000)
    {
        if (!file_exists($this->ip_list_file)) {
            return;
        }
        
        $ip_list = json_decode(file_get_contents($this->ip_list_file), true) ?: [];
        $current_time = time();
        $cleaned = false;
        
        foreach ($ip_list as $hash => $entry) {
            if (($current_time - $entry['last_seen']) > $max_age) {
                unset($ip_list[$hash]);
                $cleaned = true;
            }
        }
        
        if ($cleaned) {
            file_put_contents($this->ip_list_file, json_encode($ip_list, JSON_PRETTY_PRINT));
        }
    }
}
