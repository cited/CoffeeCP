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
print $cpanel->header('Application Mapping ');

include('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Map.php');
include('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Server.php');

$cs = new Coffeecp_Server;

$server = $cs->get_installed_json();


$exPath  = (isset($server->ex_map_path)) ? $server->ex_map_path : array();

$ex_map_path = array();

if(isset($exPath->ex_map_path) && is_array($ex_map_path)){
    $ex_map_path = $exPath->ex_map_path;
}

?>
<style>
    .page-header .page-icon {
        background-image: url('mapping.png');
        height: 41px;
        width: 50px;
    }
</style>
    <p id="descMysql" class="description">
        Use the buttons below to Map your Java applications. Choose "Wildcard" to map entire server. Choose "Path" to map application path(s) only.</p>

        <?php
        $cm = new Coffeecp_Map();

        if (isset($_POST['ex_map_path']) && is_array($_POST['ex_map_path'])) {

            $data = $cm->ex_map_path($cpanel);
            $server->ex_map_path = $data;
            $exPath = $data; 
            $ex_map_path = $server->ex_map_path['ex_map_path'];
            $cs->installed_json($server);
            $exPath = json_encode($exPath);
            $exPath = json_decode($exPath);
        }
       
         // Retrieve the account's main domain.
        $domain = $cpanel->api2(
                'DomainLookup', 'getmaindomain'
        );

       
        $domain = $domain['cpanelresult']['data'][0]['main_domain'];
        
        ?>

        <p class="description">Exclude the path(s) you would like to add. For example, app, app/login.</p>

        <form action="" method="post" name="ex_map_path_form" class="form-horizontal">
            
            <div class="form-group row">
                <label class="control-label col-md-1">HostName</label>
                <div class="col-md-5">
                    <input type="text" name="hostname" class="form-control" value="<?php echo isset($exPath->hostname) ? $exPath->hostname : $domain ?>"/>
                </div>
            </div>
            
            <div class="form-group row">
                <label class="control-label col-md-1">Path 1</label>
                <div class="col-md-5">
                    <input type="text" name="ex_map_path[]" class="form-control" value="<?php echo isset($ex_map_path[0]) ? $ex_map_path[0] : '' ?>"/>
                </div>
            </div>

            <div class="form-group row">
                <label class="control-label col-md-1">Path 2</label>
                <div class="col-md-5">
                    <input type="text" name="ex_map_path[]" class="form-control"  value="<?php echo isset($ex_map_path[1]) ? $ex_map_path[1] : '' ?>"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="control-label col-md-1">Path 3</label>
                <div class="col-md-5">
                    <input type="text" name="ex_map_path[]" class="form-control"  value="<?php echo isset($ex_map_path[2]) ? $ex_map_path[2] : '' ?>"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="control-label col-md-1">Path 4</label>
                <div class="col-md-5">
                    <input type="text" name="ex_map_path[]" class="form-control"  value="<?php echo isset($ex_map_path[3]) ? $ex_map_path[3] : '' ?>"/>
                </div>
            </div>

            <div class="form-group row">
                <label class="control-label col-md-1">&nbsp;</label>
                <div class="col-md-5">
                    <input type="submit"  class="btn btn-success" value="Submit" name="submit"/>
                </div>
            </div>
        </form>

        <p class="description"></p><br />

        <div class="return-link"><a href="index.live.php">&larr; Go Back to CoffceCP Mapping</a></div>
   
<?php
print $cpanel->footer();

$cpanel->end();
?>