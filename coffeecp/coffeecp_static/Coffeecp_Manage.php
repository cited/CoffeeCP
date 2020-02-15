<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Coffeecp_Manage {

    public $HOME_DIR;
    public $SH_FILE;
    public $ssh;
    public $cpanel;
    public $port_file = "/usr/coffeecp/json/myserver.json";

    function __construct($cp) {

        $this->cpanel = $cp;

        $this->HOME_DIR = "/home/" . $_ENV['REMOTE_USER'];
        $this->SH_FILE = $this->HOME_DIR . '/' . $_ENV['REMOTE_USER'] . '.sh';

        set_include_path(dirname(__FILE__) . '/phpseclib1.0.4');

        include('Net/SSH2.php');
        include('Crypt/RSA.php');

        define('NET_SSH2_LOGGING', NET_SSH2_LOG_COMPLEX);

        $port = 22;
        if (file_exists($this->port_file)) {
            $file = file_get_contents($this->port_file);
            $file = json_decode($file);
            $port = $file->port;
        }
        
        if(isset($_ENV['SERVER_ADDR']) && !empty($_ENV['SERVER_ADDR'])){
            $ip = $_ENV['SERVER_ADDR'];
        }else{
            $ip = getHostByName(php_uname('n'));
        }
        
        //echo "<pre>"; print_r($ip); echo "</pre>"; exit;
        
        $this->ssh = new Net_SSH2("$ip", $port);
        $this->ssh->setTimeout(120);
        //$this->ssh->enablePTY(); 
        if (!$this->ssh->login($_ENV['REMOTE_USER'], $_ENV['REMOTE_PASSWORD'])) {
            //echo nl2br($this->ssh->getLog());
            $this->loginUsingSSH();
        }
    }

    public function start() {

        if (file_exists($this->SH_FILE)) {

            $res = $this->exec('/bin/sh ' . $this->SH_FILE . ' start');

            echo nl2br($res);
        }
    }

    public function restart() {
        if (file_exists($this->SH_FILE)) {
            $res = $this->exec('/bin/sh ' . $this->SH_FILE . ' restart');

            echo nl2br($res);
        }
    }

    public function stop() {
        if (file_exists($this->SH_FILE)) {
            $res = $this->exec('/bin/sh ' . $this->SH_FILE . ' stop');

            echo nl2br($res);
        }
    }

    public function running() {
        
    }

    public function delete($dir) {
        $res = $this->exec('rm -r ' . $dir);
    }

    public function getServer() {
        
    }

    public function exec($command) {

        $res = $this->ssh->exec($command);
        $res = str_replace('stdin: is not a tty', '', $res);
        return $res;
    }

    public function loginUsingSSH() {

        $sshFile = $this->HOME_DIR . "/.ssh/java";

        $list_keys = array();
        if (!file_exists($sshFile)) {

            $list_keys = $this->cpanel->api2(
                    'SSH', 'genkey', array(
                'bits' => '1024',
                'name' => "java",
                'pass' => 'coffeecp@123',
                'type' => 'rsa',
                    )
            );
            //print_r($list_keys); exit;
            if (isset($list_keys['cpanelresult']['error'])) {
                echo json_encode(array("status" => 500, "msg" => $list_keys['cpanelresult']['error']));
                exit;
            }
            //print_r($list_keys); exit;
            // Authorize the SSH key for "user"
            $list_key = $this->cpanel->api2(
                    'SSH', 'authkey', array(
                'key' => 'java',
                'action' => 'authorize',
                    )
            );
        }

        $key = new Crypt_RSA();
        $key->setPassword('coffeecp@123');
        $key->loadKey(file_get_contents($sshFile));

        if (!$this->ssh->login($_ENV['REMOTE_USER'], $key)) {
            echo json_encode(array("status" => 500, "msg" => "Something went wrong."));
            exit;
        }
    }

}
