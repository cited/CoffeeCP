<?php

/* * *
 * Used for wildcard, unmapping, excluded path, path
 */

function check_dir($dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0700);
    }
}

function update_apache_conf($domainPath, $ssldomainPath) {

    //check EA3 
    $file = '/etc/apache2/conf/httpd.conf';

    if (!file_exists($file)) {
        $file = '/usr/local/apache/conf/httpd.conf';
    }
    $result[] = $file;
    
    if (!is_file($file)) {
        die("Error: $file not found</br>");
    }

    $sContent = file_get_contents($file);
    if (empty($sContent)) {
        die("Failed to get $file</br>");
    }

    $aLines = array();
    $aLines = explode("\n", $sContent);

    $iVhostEnd = 0;
    $iLineNo = 0;
    $bStopOnNextVhost = false;

    $httpLine = "Include " . '"' . $domainPath . "*.conf" . '"';
    $httpsLine = "Include " . '"' . $ssldomainPath . "*.conf" . '"';
    //dd($aLines);

    $result = array();

    foreach ($aLines as &$sLine) {

        if (stripos($sLine, '# ' . $httpsLine) != false) {
            // echo "https" . $sLine;
            $result[] = $sLine;
            $sLine = " " . $httpsLine;
            $result[] = $sLine;
            break;
        }

        if (stripos($sLine, '# ' . $httpLine) != false) {
            // echo "http" . $sLine;
            $result[] = $sLine;
            $sLine = " " . $httpLine;
            $result[] = $sLine;
            break;
        }

        $iLineNo++;
    }


    file_put_contents($file, implode("\n", $aLines));
    
    return $result;
}

try {


    $username = $argv[1];


    if (strlen($username) > 30) {
        return "Invalid Username";
    }


    $domain = $argv[2];


    if (!filter_var(gethostbyname($domain), FILTER_VALIDATE_IP)) {
        echo "invalid domain name";
        exit;
    }

    $installed_version = file_get_contents('/home/' . $username . '/java_installed.json');
    $server = json_decode($installed_version);

    $mapping = $server->mapping;
    $ex_map_path = isset($server->ex_map_path) ? $server->ex_map_path : array();

    $type = $mapping->type;

    if ($mapping->type == "wildcard" || $mapping->type == "map_path" || $mapping->type == "unmapping") {
        
    } else {
        echo "Invalid type field value";
        exit;
    }
    
    check_dir("/usr/coffeecp/paths/logs/");
    $mylogFile = '/usr/coffeecp/paths/logs/' . $username . '.json';

    $logData = array();
    $currentIndex = 0;
    if (file_exists($mylogFile)) {
        $logData = file_get_contents($mylogFile);
        $logData = json_decode($logData, true);
        $currentIndex = count($logData);
    }

    if (!empty($username)) {


        //for http
        $folder = array("userdata", "std", "2_4", $username, $domain);

        $path = "/etc/apache2/conf.d/";

        if (!file_exists($path)) {
            $folder = array("userdata", "std", "2", $username, $domain);
            $path = "/usr/local/apache/conf/";
        }

        foreach ($folder as $f) {
            $path = $path . $f . '/';
            check_dir($path);
        }

        $domainpath = $path;

        //for ssl
        $sslfolder = array("userdata", "ssl", "2_4", $username, $domain);

        $sslpath = "/etc/apache2/conf.d/";

        if (!file_exists($sslpath)) {
            $sslfolder = array("userdata", "ssl", "2", $username, $domain);
            $sslpath = "/usr/local/apache/conf/";
        }


        foreach ($sslfolder as $f) {
            $sslpath = $sslpath . $f . '/';
            check_dir($sslpath);
        }

        $result = update_apache_conf($domainpath, $sslpath);
        
        $logData[$currentIndex]['ssl_path'] = $sslpath;
        $logData[$currentIndex]['path'] = $domainpath;
        $logData[$currentIndex]['httpd'] = $result;
        $logData[$currentIndex]['date'] = date("Y-m-d H:i:s");
        $logData[$currentIndex]['method'] = $mapping->type;
        file_put_contents($mylogFile, json_encode($logData));
        
        $domainConfPath = "/usr/local/apache/conf/coffeecppaths/{$domain}.conf";
        file_put_contents($domainpath . 'coffeecpconf.conf', "Include {$domainConfPath}");
        file_put_contents($sslpath . 'coffeecpconf.conf', "Include {$domainConfPath}");



        $configPath = "/home/" . $username . "/java_" . $username . '.json';
        $port = file_get_contents($configPath);
        $port = json_decode($port);
        $port = $port->ports->http;

        if (!is_numeric($port)) {
            echo false;
            exit;
        }

        $newFileContent = "";

        if (count($ex_map_path) > 0) {
            foreach ($ex_map_path->ex_map_path as $e) {
                $newFileContent .= "ProxyPass /{$e} ! \n";
            }
        }
               
        if ($mapping->type == "wildcard") {
            
            $newFileContent .= "ProxyPassMatch .*\.php$ ! \n";
            $newFileContent .= "ProxyPass /stats/ !  \n";
            $newFileContent .= "ProxyPass /webmail/ ! \n";
            $newFileContent .= "ProxyPass /cpanel/ ! \n";
            $newFileContent .= "ProxyPass / http://localhost:{$port}/\nProxyPassReverse / http://localhost:{$port}/\nProxyPreserveHost On";
        } elseif ($mapping->type == "map_path") {

            $map_path = $mapping->map_path;

            foreach ($map_path as $r) {
                $newFileContent .= "ProxyPass /{$r} http://localhost:{$port}/{$r}\nProxyPassReverse /{$r} http://localhost:{$port}/{$r}\nProxyPreserveHost On\n\n";
            }
        } elseif ($mapping->type == "unmapping") {
            $newFileContent = "";
        }


        if (file_put_contents($domainConfPath, $newFileContent) !== false) {
            echo json_encode(array("status" => 200));
            exit;
        } else {
            echo json_encode(array("status" => "error"));
            exit;
        }
    }

    echo json_encode(array("status" => "not found"));
} catch (\Exception $ex) {
    echo json_encode(array("status" => "error", "msg" => $ex->getMessage()));
}
