<?php

/* * *
 * Used for glassfish and wildcard admin port.
 */

$username = $argv[1];


if (strlen($username) > 30) {
    return "Invalid Username";
}

$domain = $argv[2];


$type = $argv[3];


if (!filter_var(gethostbyname($domain), FILTER_VALIDATE_IP)) {
    echo "invalid domain name";
    exit;
}

if ($type == "glass_fish" || $type == "wildfly" || $type == "tomcat") {
    
} else {
    echo "Invalid type field value";
    exit;
}


$cpPath = "/usr/local/apache/conf/coffeecppaths/";
$mapFile = $cpPath . "apadmin.{$domain}.gfaconf";

if ($type == "glass_fish") {
    $adminport = 4848;
    $port = file_get_contents("/home/" . $username . "/java_" . $username . '.json');
    $port = json_decode($port);
    $port = $port->ports->admin_http;

    if (!is_numeric($port)) {
        echo false;
        exit;
    }
} elseif ($type == "wildfly") {
    $adminport = 9990;
    $port = file_get_contents("/home/" . $username . "/java_" . $username . '.json');
    $port = json_decode($port);
    $port = $port->ports->shutdown_or_orb_ssl_or_mgt_http;

    if (!is_numeric($port)) {
        echo false;
        exit;
    }
} elseif ($type == "tomcat") {
    // echo $mapFile; 
    if (file_exists($mapFile)) {
        unlink($mapFile);
        remove_line($cpPath, $mapFile);
    }
    exit();
}

remove_line($cpPath, $mapFile);

$content = "<VirtualHost *:$adminport>
ServerAlias apadmin.{$domain} 
ServerName apadmin.{$domain} 
ProxyPass / http://localhost:{$port}/
ProxyPassReverse / http://localhost:{$port}/
ProxyPreserveHost On
</VirtualHost>";

file_put_contents($mapFile, $content);

add_line($cpPath, $mapFile, $type);

function add_line($cpPath, $mapFile, $type) {

    $fp = fopen($cpPath . 'coffeecp.inc', "rw+");

    $i = 1;
    $insertPos = 0;

    $newline = '';

    while (!feof($fp)) {

        $line = fgets($fp);

        if ($type == "wildfly" && stripos($line, "NameVirtualHost") !== false && stripos($line, '9990') !== false) {
            $insertPos = ftell($fp);
            $newline = 'include "' . $mapFile . '"' . "\n";
        } else if ($type == "glass_fish" && stripos($line, "NameVirtualHost") !== false && stripos($line, '4848') !== false) {
            $insertPos = ftell($fp);
            $newline = 'include "' . $mapFile . '"' . "\n";
        } else {
            $newline .= $line;
        }

        $i++;
    }

    fseek($fp, $insertPos);   // move pointer to the file position where we saved above 
    fwrite($fp, $newline);

    fclose($fp);
}

function remove_line($cpPath, $mapFile) {

    $content = file_get_contents($cpPath . 'coffeecp.inc');
    $newline = 'include "' . $mapFile . '"';
    $content = str_replace($newline, "", $content);
    file_put_contents($cpPath . 'coffeecp.inc', $content);
}
