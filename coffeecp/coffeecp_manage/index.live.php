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
print $cpanel->header('Java Server Control');

include('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Manage.php');
include('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Server.php');
?>

<style>
    .page-header .page-icon{
        background-image: url('power.png');
        height: 40px;
        width: 48px;
    }
</style>


<p id="descMysql" class="description">
    Use the buttons below to stop, start, or restart your instance.  The PID for any running instance should be displayed below.</p>

<?php
$cm = new Coffeecp_Manage($cpanel);
$cs = new Coffeecp_Server;
$cm->running();

$server = $cs->get_installed_json();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!empty($action)) {

    //just for security setting value manually instead of taking from get request.
    $a = "";


    if ($action == "start") {
        $cm->start();
    } elseif ($action == "restart") {
        $cm->restart();
    } elseif ($action == "stop") {
        $cm->stop();
    }
}
?>

<?php
if ($server != false) :
    $server = $server->server;
    $command = 'ps axww | grep ' . $_ENV['REMOTE_USER'] . ' | grep java | grep -v grep | awk "{print $1}"';
//echo $command; exit;

    $pid = exec($command, $output);
    if (empty($pid)) {
        print("<br>");
        print("$server->name is stopped");
        print("<br><br>");
        echo '<a id="lnkDelete" class="btn btn-success btn-lg" href="?action=start">Start ' . $server->name . '</a>';
    } else {
        print("<br>");
        print("$server->name is running with PID : " . (substr($pid, 0, 5)));
        print("<br><br>");
        echo '<a id="lnkDelete" class="btn btn-danger btn-lg" href="?action=stop">Stop ' . $server->name . '</a>';
        echo '&nbsp;';
        echo '<a id="lnkDelete" class="btn btn-warning btn-lg" href="?action=restart">Re-Start ' . $server->name . '</a>';
//echo '&nbsp;';
//echo '<a id="lnkDelete" class="btn btn-info" href="index.php">Reload Page</a>';
    }
else :
    ?>
    <div class="alert alert-info">Error: Your instance is not ready yet.</div>
<?php endif ?>

<p class="description"></p><br />
<?php
print $cpanel->footer();

$cpanel->end();
?>