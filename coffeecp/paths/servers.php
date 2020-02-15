<?php

/**
 * This files read all users home directory and find out installation home path, version, port
 */
include("config.php");
$homeDir = glob('/home/*', GLOB_ONLYDIR);

$data = array();
foreach ($homeDir as $h) {

    $username = basename($h);

    $installed = file_get_contents($h . '/' . INSTALLED_JSON_NAME);
    if ($installed !== false) {
        $installed = json_decode($installed, true);
        $installed['server']['home'] = $h . '/' .  INSTALL_DIR . '/' . $installed['server']['folder_name'];
    }

    $config = file_get_contents($h . '/' . FILE_PREFIX . $username . '.json');

    if ($installed !== false) {
        $config = json_decode($config, true);
    }
    
    $mydata = array();
    
    if(is_array($config) && is_array($installed)){
        $mydata = array_merge($installed, $config);
    }
    $data[$username] = $mydata;
}

$data = json_encode($data);
file_put_contents('/usr/coffeecp/json/appservers.json', $data);

echo json_encode($data);
exit;