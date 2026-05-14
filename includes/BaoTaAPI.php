<?php
class BaoTaAPI {
    private $panel_url;
    private $BT_KEY;
    private $cookie_file;

    public function __construct($panel_url, $BT_KEY) {
        $this->panel_url = rtrim($panel_url, '/');
        $this->BT_KEY = $BT_KEY;
        $this->cookie_file = __DIR__ . '/../.cookie_' . md5($this->panel_url);
        if (!file_exists(dirname($this->cookie_file))) {
            mkdir(dirname($this->cookie_file), 0755, true);
        }
        if (!file_exists($this->cookie_file)) {
            touch($this->cookie_file);
        }
    }

    private function GetKeyData() {
        $now_time = time();
        $p_data = array(
            'request_time' => $now_time,
            'request_token' => md5($now_time . '' . md5($this->BT_KEY))
        );
        return $p_data;
    }

    private function HttpPostCookie($url, $data, $timeout = 60) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return json_encode(['code' => 500, 'msg' => $error]);
        }

        curl_close($ch);
        return $output;
    }

    private function makeRequest($action, $data = []) {
        $url = $this->panel_url . $action;
        $p_data = $this->GetKeyData();

        if (!empty($data)) {
            $p_data = array_merge($p_data, $data);
        }

        $result = $this->HttpPostCookie($url, $p_data);
        $data = json_decode($result, true);

        if ($data === null) {
            return ['code' => 0, 'msg' => $result];
        }

        return $data;
    }

    public function getSystemTotal() {
        return $this->makeRequest('/system?action=GetSystemTotal');
    }

    public function getDiskInfo() {
        return $this->makeRequest('/system?action=GetDiskInfo');
    }

    public function getNetwork() {
        return $this->makeRequest('/system?action=GetNetWork');
    }

    public function getSites($limit = 100) {
        return $this->makeRequest('/data?action=getData', ['table' => 'sites', 'limit' => $limit]);
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

    public function getDatabaseList() {
        return $this->makeRequest('/data?action=getData', ['table' => 'databases']);
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
        return $this->makeRequest('/files?action=GetFiles', ['path' => $path]);
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

    public function getLogs($limit = 10) {
        return $this->makeRequest('/data?action=getData', ['table' => 'logs', 'limit' => $limit]);
    }

    public function getPhpVersion() {
        return $this->makeRequest('/php?action=get_php_version');
    }

    public function setPhpVersion($siteName, $version) {
        return $this->makeRequest('/site?action=SetPhpVersion', [
            'siteName' => $siteName,
            'version' => $version
        ]);
    }

    public function getCrontabList() {
        return $this->makeRequest('/crontab?action=get_crontab_list');
    }

    public function testConnection() {
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

    public function makeCustomRequest($action, $data = []) {
        return $this->makeRequest($action, $data);
    }

    private function generatePassword($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
