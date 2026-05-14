<?php
class BaoTaAPI {
    private $panel_url;
    private $api_key;
    private $api_secret;
    private $cookies = [];
    private $last_error;

    public function __construct($panel_url, $api_key, $api_secret = '') {
        $this->panel_url = rtrim($panel_url, '/');
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    private function generateAuth() {
        $time = time();

        if (empty($this->api_secret)) {
            return [
                'time' => $time,
                'token' => md5($time . md5($this->api_key))
            ];
        }

        return [
            'time' => $time,
            'token' => md5($time . md5($this->api_secret))
        ];
    }

    private function makeRequest($action, $data = [], $method = 'POST') {
        $auth = $this->generateAuth();
        $url = $this->panel_url . $action;

        $post_data = array_merge($data, [
            'request_time' => $auth['time'],
            'request_token' => $auth['token']
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest'
            ]
        ]);

        if (!empty($post_data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        }

        if (!empty($this->cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $this->cookies));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        if ($curl_error) {
            curl_close($ch);
            $this->last_error = $curl_error;
            return ['code' => 500, 'msg' => $curl_error];
        }

        curl_close($ch);

        $result = json_decode($response, true);
        return $result ?: ['code' => $http_code, 'msg' => $response];
    }

    public function getLastError() {
        return $this->last_error;
    }

    public function getPanelInfo() {
        return $this->makeRequest('/panel?action=GetPanelInfo', [], 'POST');
    }

    public function getSystemTotal() {
        return $this->makeRequest('/system?action=GetSystemTotal', [], 'POST');
    }

    public function getDiskInfo() {
        return $this->makeRequest('/system?action=GetDiskInfo', [], 'POST');
    }

    public function getNetwork() {
        return $this->makeRequest('/system?action=GetNetWork', [], 'POST');
    }

    public function getSites($limit = 100) {
        return $this->makeRequest('/data?action=getData&table=sites&limit=' . $limit, [], 'GET');
    }

    public function addSite($webname, $domain, $path, $php_version = '74', $sql = '0', $rewrite = 'thinkphp', $limit = '0') {
        return $this->makeRequest('/site?action=AddSite', [
            'webname' => $webname,
            'domain' => $domain,
            'path' => $path,
            'php_version' => $php_version,
            'sql' => $sql,
            'rewrite' => $rewrite,
            'limit' => $limit,
            'access_log' => '0'
        ]);
    }

    public function deleteSite($id) {
        return $this->makeRequest('/site?action=DeleteSite', ['id' => $id]);
    }

    public function restartSite($siteName) {
        return $this->makeRequest('/site?action=RestartSite', ['siteName' => $siteName]);
    }

    public function stopSite($siteName) {
        return $this->makeRequest('/site?action=StopSite', ['siteName' => $siteName]);
    }

    public function startSite($siteName) {
        return $this->makeRequest('/site?action=StartSite', ['siteName' => $siteName]);
    }

    public function getDatabaseList($type = 'MySQL') {
        return $this->makeRequest('/database?action=get_database_list&type=' . $type, [], 'GET');
    }

    public function addDatabase($name, $code = 'utf8mb4') {
        return $this->makeRequest('/database?action=AddDatabase', [
            'name' => $name,
            'code' => $code,
            'user' => $name,
            'password' => $this->generatePassword(),
            'access' => '%'
        ]);
    }

    public function deleteDatabase($name) {
        return $this->makeRequest('/database?action=DeleteDatabase', ['name' => $name]);
    }

    public function getFilesList($path = '/www/wwwroot') {
        return $this->makeRequest('/files?action=GetFiles', ['path' => $path], 'POST');
    }

    public function createFile($path, $content = '') {
        return $this->makeRequest('/files?action=CreateFile', ['path' => $path, 'content' => $content]);
    }

    public function readFile($path) {
        return $this->makeRequest('/files?action=ReadFile', ['path' => $path]);
    }

    public function writeFile($path, $content) {
        return $this->makeRequest('/files?action=WriteFile', ['path' => $path, 'content' => $content]);
    }

    public function deleteFile($path) {
        return $this->makeRequest('/files?action=DeleteFile', ['path' => $path]);
    }

    public function executeCommand($cmd) {
        return $this->makeRequest('/shell?action=ExecShell', ['shell' => $cmd]);
    }

    public function getPhpVersion() {
        return $this->makeRequest('/php?action=get_php_version', [], 'GET');
    }

    public function setPhpVersion($siteName, $version) {
        return $this->makeRequest('/site?action=SetPhpVersion', [
            'siteName' => $siteName,
            'version' => $version
        ]);
    }

    public function getSSLList() {
        return $this->makeRequest('/ssl?action=get_ssl_list', [], 'GET');
    }

    public function applySSL($domain) {
        return $this->makeRequest('/acme?action=apply_cert_api', ['domains' => [$domain]]);
    }

    public function getCrontabList() {
        return $this->makeRequest('/crontab?action=get_crontab_list', [], 'GET');
    }

    public function addCrontab($name, $type, $where1, $sname) {
        return $this->makeRequest('/crontab?action=add_crontab', [
            'name' => $name,
            'type' => $type,
            'where1' => $where1,
            'sname' => $sname
        ]);
    }

    public function deleteCrontab($id) {
        return $this->makeRequest('/crontab?action=DelCrontab', ['id' => $id]);
    }

    public function getDockerContainers() {
        return $this->makeRequest('/docker?action=get_containers', [], 'GET');
    }

    public function getDockerImages() {
        return $this->makeRequest('/docker?action=get_images', [], 'GET');
    }

    public function testConnection() {
        $result = $this->getPanelInfo();
        if (isset($result['code']) && $result['code'] === 1) {
            return true;
        }

        $result = $this->getSystemTotal();
        return isset($result['cpuRealUsed']);
    }

    public function getAllInfo() {
        return [
            'system' => $this->getSystemTotal(),
            'disk' => $this->getDiskInfo(),
            'network' => $this->getNetwork(),
            'sites' => $this->getSites(),
            'databases' => $this->getDatabaseList(),
            'php_versions' => $this->getPhpVersion()
        ];
    }

    private function generatePassword($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public function makeCustomRequest($action, $data = []) {
        return $this->makeRequest($action, $data);
    }
}
