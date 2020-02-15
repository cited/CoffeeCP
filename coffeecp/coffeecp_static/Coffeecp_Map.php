<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Coffeecp_Map {

    public $MAP_DIR;
    public $DB_LINK;
    public $BASE_URL;

    function __construct() {

        $this->MAP_DIR = "/usr/local/apache/conf/coffeecppath/";
    }

    public function mapwildcard($cp) {

        $data = array(
            "type" => "wildcard"
        );


        $this->insert_data("wildcard", $cp);

        echo "<p>Wildcard Mapping has been set. This may take up to 30 seconds.</p>";
    }

    public function mappath($cp) {

        $data = array(
            "type" => "map_path"
        );

        $mappath = $_POST['mappath'];
        $map_path = array();

        foreach ($mappath as $r) {
            if (!empty($r)) {
                $map_path[] = $r;
            }
        }

        $data['map_path'] = $map_path;

        $this->insert_data("map_path", $cp);
        return $data;
    }

    public function ex_map_path($cp) {

        $data = array(
            "type" => "ex_map_path",
            "hostname" => $_POST['hostname']
        );

        $mappath = $_POST['ex_map_path'];
        $map_path = array();

        foreach ($mappath as $r) {
            if (!empty($r)) {
                $map_path[] = $r;
            }
        }

        $data['ex_map_path'] = $map_path;

        $this->insert_data("ex_map_path", $cp);
        return $data;
    }

    public function insert_data($type, $cp) {

        // Retrieve the account's main domain.
        $domain = $cp->api2(
                'DomainLookup', 'getmaindomain'
        );

        $username = $_ENV['REMOTE_USER'];
        //$domain = $_ENV['DOMAIN'];
        $domain = $domain['cpanelresult']['data'][0]['main_domain'];

        try {

            $command = "$username $domain";

            file_put_contents("/home/" . $username . '/reload.txt', $command);

            return;
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            exit;
        }
    }

    public function unmapping($cp) {

        $data = array(
            "type" => "unmapping"
        );


        $this->insert_data("unmapping", $cp);

        echo "<p>All map paths have been removed.  This may take up to 30 seconds.</p>";
    }

}

function check_dir($dir) {


    if (!file_exists($dir)) {
        mkdir($dir, 0700);
    }
}
