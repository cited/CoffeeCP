<?php

/**
 * This files read all users home directory and find out installation home path, version, port
 */
include("config.php");
$homeDir = glob("/home/*", GLOB_ONLYDIR);

$port = 5100;

$port1 = $argv[1];


if (!empty($port1)) {
    $port = $port1;
}

$data = array();
foreach ($homeDir as $h) {

    $username = basename($h);
    $data = false;
    $jsonPath = $h . '/' . MIGRATION_JSON_PATH;

    if (file_exists($jsonPath)) {
        $data = file_get_contents($jsonPath);
        echo $jsonPath . "\n";
    }

    if ($data != false) {

        echo $h . "\n";
        $data = json_decode($data);
        $port = port_assign($port, $username, $h, $data);

        $d = installed_update($username, $h, $data);

        update_script($username, $h, $d);
    }
}

function getServer($path) {

    $d = file_get_contents('/usr/coffeecp/json/coffeecp_server.json');
    $d = json_decode($d, true);

    $server = array();
    foreach ($d['servers'] as $s) {
        if ($s['path'] == $path) {
            $server = $s;
            break;
        }
    }
    return $server;
}

function getJDK($path) {
    $d = file_get_contents('/usr/coffeecp/json/coffeecp_server.json');
    $d = json_decode($d, true);

    $jdk = array();
    foreach ($d['JDK'] as $s) {
        if ($s['path'] == $path) {
            $jdk = $s;
            break;
        }
    }
    return $jdk;
}

function port_assign($port, $username, $h, $data) {

    $configPath = $h . '/' . FILE_PREFIX . $username . '.json';

    $config_json = false;
    if (file_exists($configPath)) {
        $config_json = file_get_contents($configPath);
    }


    $configData = array();
    $myData = array();
    if ($config_json !== false) {
        $configData = json_decode($config_json, true);
    } else {
        $temp = array();
        $temp['http'] = $port + 1;
        $temp['https'] = $port + 2;
        $temp['ajp_or_orb'] = $port + 3;
        $temp['shutdown_or_orb_ssl_or_mgt_http'] = $port + 4;
        $temp['http'] = $port + 5;
        $temp['orb_ssl_mu_or_mgt_https'] = $port + 5;
        $temp['jmx_conn_or_txt_reco_env'] = $port + 7;
        $temp['admin_http'] = $port + 8;
        $temp['jmx_host_or_txt_status'] = $port + 9;
        $temp['osgi_shell'] = $port + 10;
        //don't move it
        $configData['ports'] = $temp;

        $port = $temp['osgi_shell'];
    }

    $configData['value'] = $data->value;
    $configData['memory'] = $data->memory;
    $configData['ports']['http'] = $data->ports->http;
    $configData['ports']['https'] = $data->ports->https;
    $configData['ports']['ajp_or_orb'] = $data->ports->ajp;

    $type = $data->server->type;
    //1 for tomcat, 2 for wildfly and 3 for glassfish
    if ($type == 1) {
        $configData['ports']['shutdown_or_orb_ssl_or_mgt_http'] = $data->ports->shutdown;
    } else if ($type == 2) {
        $configData['ports']['admin_http'] = $data->ports->shutdown;
         $configData['ports']['shutdown_or_orb_ssl_or_mgt_http'] = $data->ports->mgt_http;
    } else if ($type == 3) {
        $configData['ports']['admin_http'] = $data->ports->shutdown;       
    }

    file_put_contents($configPath, json_encode($configData));
    //chown($configPath, $temp)
    exec("chown $username:$username $configPath");
    return $port;
}

function installed_update($username, $h, $data) {

    $installPath = $h . '/' . INSTALLED_JSON_NAME;
    $installed_json = false;
    if (file_exists($installPath)) {
        $installed_json = file_get_contents($installPath);
    }


    $installedData = array();
    if ($installed_json !== false) {
        $installedData = json_decode($installed_json, true);
    }

    $installedData['server'] = $data->server;
    $installedData['jdk'] = $data->jdk;

    file_put_contents($installPath, json_encode($installedData));

    exec("chown $username:$username $installPath");
    return $installedData;
}

function update_script($username, $h, $installedData) {

    $scriptPath = $h . '/' . $username . '.sh';

    $type = $installedData['server']->type;

    if ($type == 1) {

        $source = file_get_contents('/usr/coffeecp/scripts/tomcat.sh');

        $newline = str_replace('jkacugis', $username, $source);

        $tomcatPath = $h . '/' . INSTALL_DIR . '/' . $installedData['server']->folder_name;

        $newline = str_replace('#TOMCAT_PATH#', $tomcatPath, $newline);
    } elseif ($type == 2) {

        $source = file_get_contents('/usr/coffeecp/scripts/wildfly.sh');

        $wildflyPath = $h . '/' . INSTALL_DIR . '/wildfly.conf';
        $newline = str_replace('#JBOSS_CONF#', $wildflyPath, $source);

        $configPath = $h . '/' . FILE_PREFIX . $username . '.json';
        $ports = json_decode(file_get_contents($configPath));

        $appPath = $h . '/' . INSTALL_DIR . '/';
        $serverPath = $appPath . $installedData['server']->folder_name . '/';

        $content = "JAVA_HOME=" . $installedData['jdk']->path . "
JBOSS_LOCKFILE={$appPath}wildfly.lock
WILDFLY_HOME={$appPath}
JBOSS_CONSOLE_LOG={$appPath}console.log
MAN_HTTP_PORT={$ports->ports->shutdown_or_orb_ssl_or_mgt_http}
JBOSS_PIDFILE={$appPath}wildfly.pid
WILDFLY_VER={$installedData['server']->version}
JBOSS_MODE=standalone
JBOSS_HOME={$serverPath}
JBOSS_USER={$username}";
        $wildFlyConfPath = $appPath . 'wildfly.conf';
        $content = file_put_contents($wildFlyConfPath, $content);
        
        exec("chown $username:$username $wildFlyConfPath");
        
    } elseif ($type == 3) {
        $source = file_get_contents('/usr/coffeecp/scripts/glassfish.sh');
        $glassfishPath = $h . '/' . INSTALL_DIR . '/' . $installedData['server']->folder_name;
        $newline = str_replace('#GLASSFISH_PATH#', $glassfishPath, $source);
    }



    file_put_contents($scriptPath, $newline);

    exec("chmod 744 $scriptPath");
    exec("chown $username:$username $scriptPath");
}
