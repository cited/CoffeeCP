<?php

class Coffeecp_Server {

    public $HOME_DIR;
    public $INSTALL_DIR = "appservers";
    public $SERVER_DIR = "apache-tomcat";
    public $SERVER_PATH;
    public $TOMCAT_SH = "/usr/coffeecp/scripts/tomcat.sh";
    public $GLASSFISH_SH = "/usr/coffeecp/scripts/glassfish.sh";
    public $WildFly_SH = "/usr/coffeecp/scripts/wildfly.sh";
    public $DOWNLOAD_PATH = "/usr/coffeecp/appfiles/";
    public $PORTS;
    public $CONFIG;
    public $JDK_PATH = "";
    public $SERVER_JSON = "/usr/coffeecp/json/coffeecp_server.json";
    public $JDK_INFO;
    public $cm;
    public $FILE_PREFIX = "java_";

    function __construct() {

        $s = $this->get_installed_json();

        if (isset($s->jdk)) {
            $this->JDK_PATH = $s->jdk->path;
            $this->JDK_INFO = $s->jdk;
        } else {
            $s = $this->getServerLists();
            $this->JDK_PATH = $s->JDK[0]->path;
            $this->JDK_INFO = $s->JDK[0];
        }




        $this->HOME_DIR = "/home/" . $_ENV['REMOTE_USER'];
        //don;t changeit
        $this->SERVER_PATH = $this->HOME_DIR . '/' . $this->INSTALL_DIR . '/';
    }

    function install($version, $serverType, $ports, $cpserver, $cp) {

        /* removed older instances */
        //stop the process if there is anything running.

        $this->cm = new Coffeecp_Manage($cp);
        $this->cm->stop();

        $this->cm->delete($this->HOME_DIR . '/' . $this->INSTALL_DIR);

        $this->PORTS = $ports->ports;
        $this->CONFIG = $ports;
        $type = "";

        if ($serverType == 1) {
            $type = "tomcat";
            $this->install_tomcat($version, $cpserver);

            tomcat_users($this->SERVER_PATH);

            echo '<p class="description">Tomcat installed successfully.</p><p>Go to Power module and start tomcat.</p>';
        } elseif ($serverType == 2) {
            $type = "wildfly";
            $this->install_wildfly($version, $cpserver);

            wildfly_user($this->SERVER_PATH, $this->cm, $this->JDK_PATH);
        } elseif ($serverType == 3) {
            $type = "glass_fish";
            $this->install_glassfish($version, $cpserver);

            setup_glassfish_admin($this->SERVER_PATH, 'glassfish', 4848, $this->cm);
            echo '<p class="description">GlassFish installed successfully.</p><p>Go to Power module and start GlassFish.</p>';
        }

        $username = $_ENV['REMOTE_USER'];

        // Retrieve the account's main domain.
        $domain = $cp->api2(
                'DomainLookup', 'getmaindomain'
        );

        //$domain = $_ENV['DOMAIN'];

        $domain = $domain['cpanelresult']['data'][0]['main_domain'];

        $command = "$username $domain $type";

        file_put_contents("/home/" . $username . '/mapping.txt', $command);
    }

    function creatFile($dir, $fileName) {
        $fp = touch($dir . '/' . $fileName);
        // chown($dir . '/' . $fileName, 0700);
//        $account = $_ENV['REMOTE_USER'];
//        $this->xmlapi->api1_query($account, "Fileman", "fmmkfile", array($dir, $fileName));
//        $this->xmlapi->api1_query($account, "Fileman", "changeperm", array($dir, $fileName, 4, 2, 1, 0, 0, 0, 0, 0, 0));
    }

    /* copy the files for appfiles to coffecp folder */

    public function download($file_source, $file_target) {

        $source = $file_source;

        if (!copy($source, $file_target)) {
            echo "failed to copy $source...\n";
            exit;
        }

        return true;
    }

    public function install_tomcat($version, $cpserver) {

        echo "Initialize Installation<br>";

        echo "Downloading Tomcat Package<br/>";

        $this->SERVER_DIR = $cpserver->folder_name;
        $tomcatPath = $this->SERVER_PATH . $this->SERVER_DIR;
        $this->SERVER_PATH .= $this->SERVER_DIR . '/';
        $filename = basename($cpserver->path);

        $this->extractFile($this->HOME_DIR, $cpserver->path, $filename);

        //update xml and catellina sh file.
        echo "Update server.xml file. <br/>";
        $this->updateXML();
        echo "Update catalina.sh file. <br/>";
        $this->updateSH();

        $fileName = $_ENV['REMOTE_USER'] . '.sh';
        $this->creatFile($this->HOME_DIR, $fileName);

        $servicePath = $this->HOME_DIR . '/' . $fileName;
        $fp = fopen($servicePath, "w+");
        $source = file_get_contents($this->TOMCAT_SH, "r+");

        $account = $_ENV['REMOTE_USER'];
        $newline = str_replace('jkacugis', $account, $source);
        $newline = str_replace('#TOMCAT_PATH#', $tomcatPath, $newline);
        fwrite($fp, $newline);
        fclose($fp);

        // echo $this->SERVER_PATH; exit;

        $this->cm->exec("chmod +x {$this->SERVER_PATH}bin/startup.sh ");
        $this->cm->exec("chmod +x {$this->SERVER_PATH}bin/catalina.sh ");
        $this->cm->exec("chmod +x {$this->SERVER_PATH}bin/shutdown.sh ");
        $this->cm->exec("chmod +x {$servicePath}");
        //echo $this->cm->getLog();
    }

    public function install_wildfly($version, $cpserver) {

        echo "Initialize Installation<br>";


        echo "Downloading WildFly Package<br/>";


        $sourceFile = basename($cpserver->path);

        $fileDir = basename($sourceFile, ".tar.gz");

        $this->SERVER_DIR = $cpserver->folder_name;
        $this->SERVER_PATH .= $this->SERVER_DIR . '/';

        $appPath = $this->HOME_DIR . '/' . $this->INSTALL_DIR . '/';

        $this->extractFile($this->HOME_DIR, $cpserver->path, $sourceFile);

        $fileName = $_ENV['REMOTE_USER'] . '.sh';
        $servicePath = $this->HOME_DIR . '/' . $fileName;
        $fp = fopen($servicePath, "w+");
        $source = file_get_contents($this->WildFly_SH, "r+");


        $newline = str_replace('#JBOSS_CONF#', $appPath . 'wildfly.conf', $source);
//        $newline = str_replace('#GLASSFISH_PATH#', $this->SERVER_PATH . 'glassfish', $newline);
        fwrite($fp, $newline);
        fclose($fp);
        $this->cm->exec("chmod +x {$servicePath}");


        $this->updateWildFlyXML();

        /* create config file */
        $appPath = $this->HOME_DIR . '/' . $this->INSTALL_DIR . '/';
        $username = $_ENV['REMOTE_USER'];


        $content = "JAVA_HOME=" . $this->JDK_PATH . "
JBOSS_LOCKFILE={$appPath}wildfly.lock
WILDFLY_HOME={$appPath}
JBOSS_CONSOLE_LOG={$appPath}console.log
MAN_HTTP_PORT={$this->PORTS->shutdown_or_orb_ssl_or_mgt_http}
JBOSS_PIDFILE={$appPath}wildfly.pid
WILDFLY_VER={$version}
JBOSS_MODE=standalone
JBOSS_HOME={$this->SERVER_PATH}
JBOSS_USER={$username}";

        $content = file_put_contents($appPath . 'wildfly.conf', $content);

        //        
        $content = 'JAVA_HOME="' . $this->JDK_PATH . '"' . "\n";

        $content .= PHP_EOL . $this->CONFIG->memory;

        $v = $this->CONFIG->value / 2;
        $content .= PHP_EOL . 'JAVA_OPTS="$JAVA_OPTS -Xms64000000 -XX:MetaspaceSize=96M -XX:MaxMetaspaceSize=' . $v . 'M -Djava.net.preferIPv4Stack=true"';

        $content .= PHP_EOL . 'JAVA_OPTS="$JAVA_OPTS -Djboss.modules.system.pkgs=$JBOSS_MODULES_SYSTEM_PKGS"';

        file_put_contents($this->SERVER_PATH . 'bin/standalone.conf', $content);
    }

    public function install_glassfish($version, $cpserver) {

        echo "Initialize Installation<br>";


        echo "Downloading GlassFish Package<br/>";


        //create instllation folder and extract file
        $fileName = uniqid() . '.zip';

        $sourceFile = basename($cpserver->path); //"glassfish-" . $version . '-web.zip';
        $fileDir = basename($sourceFile, ".zip");

        $this->SERVER_DIR = $cpserver->folder_name . '/'; //$fileDir;
        $appPath = $this->SERVER_PATH . $cpserver->folder_name;
        $this->SERVER_PATH .= $this->SERVER_DIR . '';

        $this->extractFile($this->HOME_DIR, $cpserver->path, $sourceFile);

        if (file_exists($this->SERVER_PATH . 'glassfish')) {
            $this->SERVER_PATH = $this->SERVER_PATH . 'glassfish/';
            $appPath .= '/glassfish';
        }

        $fileName = $_ENV['REMOTE_USER'] . '.sh';
        $servicePath = $this->HOME_DIR . '/' . $fileName;
        $fp = fopen($servicePath, "w+");
        $source = file_get_contents($this->GLASSFISH_SH, "r+");

        //$newline = str_replace('#JAVA_HOME#', $this->JDK_PATH, $source);
        $newline = str_replace('#GLASSFISH_PATH#', $appPath, $source);
        fwrite($fp, $newline);
        fclose($fp);

        $this->cm->exec("chmod +x {$servicePath}");

        // chmod($this->HOME_DIR . '/' . $fileName, 755);
//        //update xml and catellina sh file.
//        echo "Update server.xml file. <br/>";
        $this->updateGFXML();

        $content = file_get_contents($this->SERVER_PATH . 'config/asenv.conf');
        $content .= "\n" . 'AS_JAVA="' . $this->JDK_PATH . '"' . "\n";
        file_put_contents($this->SERVER_PATH . 'config/asenv.conf', $content);
    }

    public function is_install() {

        $serverxml = $this->SERVER_PATH . 'conf/server.xml';
        if (file_exists($serverxml)) {
            return true;
        }

        return false;
    }

    public function updateXML() {

        $serverxml = $this->SERVER_PATH . 'conf/server.xml';

        $server = simplexml_load_file($serverxml);
        //echo $server->asXML(); exit;
        $t = $server->xpath("/Server[@port='8005']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->shutdown_or_orb_ssl_or_mgt_http;
        }

        $t = $server->xpath("//Connector[@protocol='AJP/1.3']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->ajp_or_orb;
            $t[0]->attributes()->address = "127.0.0.1";
            $t[0]->attributes()->redirectPort = $this->PORTS->https;
        }

        $t = $server->xpath("//Connector[@protocol='HTTP/1.1']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->http;
            $t[0]->attributes()->address = "127.0.0.1";
            $t[0]->attributes()->redirectPort = $this->PORTS->https;
        }


        //   echo $server->asXML($serverxml);
        $server->saveXML($serverxml);
    }

    public function updateGFXML() {

        $serverxml = $this->SERVER_PATH . 'domains/domain1/config/domain.xml';

        $server = simplexml_load_file($serverxml);
        //print_r($server); exit;
        //echo $server->asXML(); exit;
        $t = $server->xpath("//iiop-listener[@id='orb-listener-1']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->ajp_or_orb;
        }

        $t = $server->xpath("//iiop-listener[@id='ssl']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->shutdown_or_orb_ssl_or_mgt_http;
        }



        $t = $server->xpath("//iiop-listener[@id='SSL_MUTUALAUTH']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->orb_ssl_mu_or_mgt_https;
        }

        $t = $server->xpath("//admin-service/jmx-connector[@port='8686']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->jmx_conn_or_txt_reco_env;
        }

        $t = $server->xpath("//jms-host[@port='7676']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->jmx_host_or_txt_status;
        }

        $t = $server->xpath("//network-listener[@protocol='http-listener-1']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->http;
        }

        $t = $server->xpath("//network-listener[@protocol='http-listener-2']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->https;
        }

        $t = $server->xpath("//network-listener[@protocol='admin-listener']");

        if (isset($t[0])) {
            $t[0]->attributes()->port = $this->PORTS->admin_http;
        }



        $t = $server->xpath("//java-config");
//         echo "<pre>";
//            print_r($t1->children());
//            echo "</pre>";
//            exit;
        foreach ($t as $item) {
            $item->{'jvm-options'}[11] = "-Xmx" . $this->CONFIG->value . "m";
            //foreach($item->{'jvm-options'} as $g); 
        }




        //   echo $server->asXML($serverxml);
        $server->saveXML($serverxml);
    }

    public function updateWildFlyXML() {

        $serverxml = $this->SERVER_PATH . 'standalone/configuration/standalone.xml';

        $server = simplexml_load_file($serverxml);
        $ns = $server->getNamespaces(true);

        $server->registerXPathNamespace('s', "urn:jboss:domain:4.2");
        // echo "<pre>"; print_r($ns); echo "</pre>"; exit;
        //echo $server->asXML(); exit;
        $t = $server->xpath("//s:socket-binding-group[@name='standard-sockets']/s:socket-binding[@name='management-http']");
        //echo "<pre>"; print_r($t); echo "</pre>"; exit;
        if (isset($t[0])) {
            $t[0]->attributes()->port = '${jboss.management.http.port:' . $this->PORTS->shutdown_or_orb_ssl_or_mgt_http . '}';
        }

        $t = $server->xpath("//s:socket-binding-group[@name='standard-sockets']/s:socket-binding[@name='management-https']");


        if (isset($t[0])) {
            $t[0]->attributes()->port = '${jboss.management.https.port:' . $this->PORTS->orb_ssl_mu_or_mgt_https . '}';
        }

        $t = $server->xpath("//s:socket-binding-group[@name='standard-sockets']/s:socket-binding[@name='ajp']");


        if (isset($t[0])) {
            $t[0]->attributes()->port = '${jboss.ajp.port:' . $this->PORTS->ajp_or_orb . '}';
        }

        $t = $server->xpath("//s:socket-binding-group[@name='standard-sockets']/s:socket-binding[@name='http']");


        if (isset($t[0])) {
            $t[0]->attributes()->port = '${jboss.http.port:' . $this->PORTS->http . '}';
        }

        $t = $server->xpath("//s:socket-binding-group[@name='standard-sockets']/s:socket-binding[@name='https']");


        if (isset($t[0])) {
            $t[0]->attributes()->port = '${jboss.https.port:' . $this->PORTS->https . '}';
        }

        $t = $server->xpath("//s:socket-binding-group[@name='standard-sockets']/s:socket-binding[@name='txn-recovery-environment']");


        if (isset($t[0])) {

            $t[0]->attributes()->port = $this->PORTS->jmx_conn_or_txt_reco_env;
        }

        $t = $server->xpath("//s:socket-binding-group[@name='standard-sockets']/s:socket-binding[@name='txn-status-manager']");


        if (isset($t[0])) {

            $t[0]->attributes()->port = $this->PORTS->jmx_host_or_txt_status;
        }

        $t = $server->xpath("//s:interfaces/s:interface[@name='management']/s:inet-address");


        if (isset($t[0])) {

            $t[0]->attributes()->value = '0.0.0.0';
        }

        $t = $server->xpath("//s:interfaces/s:interface[@name='public']/s:inet-address");


        if (isset($t[0])) {

            $t[0]->attributes()->value = '0.0.0.0';
        }

        //   echo $server->asXML($serverxml);
        $server->saveXML($serverxml);
    }

    public function updateSH() {

        //$basePath = $this->HOME_DIR . '/' . $this->INSTALL_DIR . '/' . $this->SERVER_DIR;
        $path = $this->SERVER_PATH . 'bin/catalina.sh';
        $fp = fopen($path, "rw+");

        $i = 1;
        $insertPos = 0;

        $newline = '';

        while (!feof($fp)) {
            $line = fgets($fp);

            if ($i == 2) {
                $insertPos = ftell($fp);
                $newline = PHP_EOL . 'JAVA_HOME=' . $this->JDK_PATH;
                $newline .= PHP_EOL . 'export JRE_HOME=$JAVA_HOME';
                $newline .= PHP_EOL . 'export CATALINA_HOME=' . $this->SERVER_PATH;
                $newline .= PHP_EOL . 'export ' . $this->CONFIG->memory . PHP_EOL;
            } else {
                $newline .= $line;
            }

            $i++;
        }

        fseek($fp, $insertPos);   // move pointer to the file position where we saved above 
        fwrite($fp, $newline);

        fclose($fp);
    }

    public function extractFile($extractDir, $sourcepath, $neat_filename) {

        $fileDir = basename($neat_filename, ".zip");

        $extractDir = $extractDir . '/' . $this->INSTALL_DIR;

        $this->createFolder($this->INSTALL_DIR, $this->HOME_DIR);

        $this->createFolder($this->SERVER_DIR, $extractDir);

        $this->download($sourcepath, $extractDir . '/' . $neat_filename);

        $infile = $extractDir . '/' . $neat_filename;

        $extractDir = $extractDir . '/';

        if (stripos($neat_filename, "tar.gz") !== false) {
            $o = exec("tar -C $extractDir -zxvf $infile", $output1, $output2);
        } else {

            //echo "/usr/bin/unzip  -q $infile -d $extractDir";
            $o = exec("/usr/bin/unzip  -q $infile -d $extractDir", $output1, $output2);
            // unlink($infile);
            //$inputDir = $extractDir . $fileDir;
            //$outputDir = $extractDir . $this->SERVER_DIR;
            // echo "cd $extractDir && mv $fileDir $this->SERVER_DIR";
            $o = exec("cd $extractDir && mv $fileDir/* $this->SERVER_DIR/ && rm -rf $fileDir", $output1, $output2);
        }

        unlink($extractDir . '/' . $neat_filename);

//        
//        $account = $_ENV['REMOTE_USER'];
//
//        $this->createFolder($this->INSTALL_DIR, $this->HOME_DIR);
//        // $neat_filename = uniqid() . '.tar.gz';
//        $extractDir = $this->HOME_DIR . '/' . $this->INSTALL_DIR;
//
//        $this->download($sourcepath, $extractDir . '/' . $neat_filename);
//
//        //  $this->xmlapi->api1_query($account, "Fileman", "fmmkfile", array($extractDir, $neat_filename));
//        $this->xmlapi->api1_query($account, "Fileman", "changeperm", array($extractDir, $neat_filename, 4, 2, 1, 0, 0, 0, 0, 0, 0));
//
//
//        $this->xmlapi->api1_query($account, "Fileman", "extractfile", array($extractDir, $neat_filename));
//        //$this->xmlapi->api1_query($account, "Fileman", "rename", array($extractDir . '/' . $fileDir, $extractDir . '/apache-tomcat'));
//        $this->xmlapi->api1_query($account, "Fileman", "delfile", array($extractDir, $neat_filename));
    }

    public function createFolder($dirName, $dir) {
        //echo $dir . $dirName; exit;
        $dir = $dir . '/' . $dirName;
        if (!file_exists($dir)) {
            mkdir($dir, 0700);
        }

//        $account = $_ENV['REMOTE_USER'];
//
//        $this->xmlapi->api1_query($account, "Fileman", "fmmkdir", array($dir, $dirName));
//        $this->xmlapi->api1_query($account, "Fileman", "changeperm", array($dir, $dirName, 4, 2, 1, 0, 0, 0, 0, 0, 0));
    }

    public function xmlconnect() {
        
    }

    public function delete_directories($dir) {
        
    }

    public function getServerLists() {

        $dbowner = trim($_ENV['REMOTE_USER']);

        $servers = file_get_contents($this->SERVER_JSON);
        $servers = json_decode($servers);

        return $servers;
    }

    public function getServer($index) {

        $dbowner = trim($_ENV['REMOTE_USER']);

        $servers = file_get_contents($this->SERVER_JSON);
        $servers = json_decode($servers);

        return $servers->servers[$index];
    }

    public function installed_json($server) {
        $dbowner = trim($_ENV['REMOTE_USER']);

        file_put_contents('/home/' . $dbowner . '/' . $this->FILE_PREFIX . 'installed.json', json_encode($server));
    }

    public function get_installed_json() {

        $dbowner = trim($_ENV['REMOTE_USER']);
        $server = @file_get_contents('/home/' . $dbowner . '/' . $this->FILE_PREFIX . 'installed.json');

        if (isset($server) && !empty($server)) {

            $server = json_decode($server);

            if (isset($server->jdk)) {

                return $server;
            }
        }

        return false;
    }

    public function getConfig() {

        $dbowner = trim($_ENV['REMOTE_USER']);

        $config = @file_get_contents('/home/' . $dbowner . '/' . $this->FILE_PREFIX . $dbowner . '.json');

        if (isset($config) && !empty($config)) {
            return $config = json_decode($config);
        }

        return false;
    }

    public function update_java_path($s, $newPath) {

        if ($s->server->type == 1) {

            $path = $this->SERVER_PATH . $s->server->folder_name . '/bin/catalina.sh';

            $content = file_get_contents($path);

            $content = str_replace($s->jdk->path, $newPath->path, $content);

            file_put_contents($path, $content);
        } else if ($s->server->type == 2) {

            $appPath = $this->HOME_DIR . '/' . $this->INSTALL_DIR . '/wildfly.conf';

            $content = file_get_contents($appPath);

            $content = str_replace($s->jdk->path, $newPath->path, $content);

            $content = file_put_contents($appPath, $content);

            $fileDir = $s->server->folder_name . '/';

            //update wildfly conf
            $appPath = $this->SERVER_PATH . $fileDir . 'bin/standalone.conf';
            $content = file_get_contents($appPath);
            $content = str_replace($s->jdk->path, $newPath->path, $content);

            $content = file_put_contents($appPath, $content);
        } else if ($s->server->type == 3) {


            $fileDir = $s->server->folder_name . '/';

            $this->SERVER_PATH = $this->SERVER_PATH . $fileDir;
            if (file_exists($this->SERVER_PATH . '/glassfish')) {
                $this->SERVER_PATH = $this->SERVER_PATH . '/glassfish';
            }
            $content = file_get_contents($this->SERVER_PATH . '/config/asenv.conf');

            $content = str_replace($s->jdk->path, $newPath->path, $content);
            file_put_contents($this->SERVER_PATH . '/config/asenv.conf', $content);
        }
    }

}

?>
