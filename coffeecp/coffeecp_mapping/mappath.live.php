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

$mapping_active = (isset($server->mapping)) ? $server->mapping->type : '';

$mapPath = array();
if ($mapping_active == "map_path") {
    $mapPath = $server->mapping->map_path;
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

        if (isset($_POST['mappath']) && is_array($_POST['mappath'])) {

            $data = $cm->mappath($cpanel);
            $server->mapping = $data;            
            $mapPath = $server->mapping['map_path'];
            $cs->installed_json($server);
        }
        ?>

        <p class="description">Add the path(s) you would like to add. For example, app, app/login.</p>

        <form action="" method="post" name="mappath_form" class="form-horizontal">

            <div class="form-group row">
                <label class="control-label col-md-1">Path 1</label>
                <div class="col-md-5">
                    <input type="text" name="mappath[]" class="form-control" value="<?php echo isset($mapPath[0]) ? $mapPath[0] : '' ?>"/>
                </div>
            </div>

            <div class="form-group row">
                <label class="control-label col-md-1">Path 2</label>
                <div class="col-md-5">
                    <input type="text" name="mappath[]" class="form-control"  value="<?php echo isset($mapPath[1]) ? $mapPath[1] : '' ?>"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="control-label col-md-1">Path 3</label>
                <div class="col-md-5">
                    <input type="text" name="mappath[]" class="form-control"  value="<?php echo isset($mapPath[2]) ? $mapPath[2] : '' ?>"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="control-label col-md-1">Path 4</label>
                <div class="col-md-5">
                    <input type="text" name="mappath[]" class="form-control"  value="<?php echo isset($mapPath[3]) ? $mapPath[3] : '' ?>"/>
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

        <div class="return-link"><a href="index.live.php">&larr; Go Back to CoffeeCP Mapping</a></div>
  
<?php
print $cpanel->footer();

$cpanel->end();
?>
