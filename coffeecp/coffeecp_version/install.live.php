<?php

/* This is an example of how to use LiveAPI with paper_lantern
 * paper_lantern has adopted a bootstrap CSS base, for information on CSS classes to use
 * please see: http://bootstrap.com
 *
 * As for method available to the $cpanel object in the example, cpanel.php contains 
 * etensive inline documentation in the PHP Doc format.  Taking a look at it is highly advised.
 *
 * You can also look at the test.live.php file shipped along side this example. 
 */

include("/usr/local/cpanel/php/cpanel.php");
$cpanel = new CPANEL();
print $cpanel->header('Java Server Version');

include_once ('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Server.php');
include('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Manage.php');
include('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/twg_users.php');

?>
<style>
    .page-header .page-icon{
        background-image: url('version.png');
        height: 40px;
        width: 50px;
    }
</style>
<?php

$id = isset($_GET['id']) ? $_GET['id'] : '';
$username = $_ENV['REMOTE_USER'];

$cs = new Coffeecp_Server;
$username = $_ENV['REMOTE_USER'];
$dbserver = $cs->getServer($id);

$config = $cs->getConfig();

$install_flag = true;
if ($dbserver->type == 2) {
//check JDK veersion
    $major = substr($cs->JDK_INFO->version, 5, 1);

    if ($major == 7) {
        $install_flag = false;
        echo '<div class="alert alert-danger">Wildfly' . $dbserver->version . ' is not supported with JDK 7. Please go to JDK module and choose JDK 8 version.</div>';
    }
}

if ($install_flag === true) {

    $cs->install($dbserver->version, $dbserver->type, $config, $dbserver, $cpanel);

    $s = $cs->get_installed_json();

    if (!$s) {
        $s = new stdClass();
    }

    $s->server = $dbserver;

    if (!isset($s->jdk)) {
        $s1 = $cs->getServerLists();
        $s->jdk = $s1->JDK[0];
    }

    $cs->installed_json($s);
}

print $cpanel->footer();
$cpanel->end();
?>