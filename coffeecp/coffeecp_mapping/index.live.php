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
?>

<style>
    .page-header .page-icon {
        background-image: url('mapping.png');
        height: 41px;
        width: 50px;
    }
</style>
<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ="  crossorigin="anonymous"></script>
<p id="descMysql" class="description">
    Use the buttons below to map your applications. Choose "Wildcard" to map to entire server. Choose "Path" to map application path(s) only.</p>


<?php
$action = isset($_GET['action']) ? $_GET['action'] : '';
if (!empty($action)) {

    $cm = new Coffeecp_Map();
    //just for security setting value manually instead of taking from get request.
    if ($action == "mapwildcard") {

        $server->mapping = array("type" => "wildcard");
        $cs->installed_json($server);

        $cm->mapwildcard($cpanel);
    } elseif ($action == "unmapping") {

        $server->mapping = array("type" => "unmapping");
        $cs->installed_json($server);

        $cm->unmapping($cpanel);
    }
}
?>


<?php if ($server != false) : ?>

    <a  id="wildcard_btn" class="btn <?php echo empty($mapping_active) ? ' btn-danger' : ($mapping_active == "wildcard" ? 'btn-success' : 'btn-danger') ?>" href="javascript:void(0);">WildCard Mapping</a>
    <a id="" class="btn <?php echo $mapping_active == "map_path" ? 'btn-success' : 'btn-danger'; ?>" href="mappath.live.php">Path Mapping</a>
    <a id="" class="btn <?php echo $mapping_active == "ex_map_path" ? 'btn-success' : 'btn-danger'; ?>" href="exmappath.live.php">Excluded Path</a>
    <?php if (!empty($mapping_active)) : ?>
        <a  id="unmapping_btn" class="btn btn-danger" href="javascript:void(0)">Disable Mapping</a>
    <?php endif ?>
<?php else : ?>
    <div class="alert alert-info">Error: Your instance is not ready yet.</div>
<?php endif ?>


<p class="description"></p><br />



<div id="coffeecp_unmapping_modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Disable Mapping</h4>
            </div>
            <div class="modal-body">                       
                <p>Disabled mapping</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="unmapping_confirm_btn">Disabled Mapping</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
</div>
<div id="coffeecp_wildcard_modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Wildcard</h4>
            </div>
            <div class="modal-body">                       
                <p>Main Domain Will used as wildcard address.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="wildcard_confirm_btn">Wildcard</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
</div>


<?php
print $cpanel->footer();
?>

<script type="text/javascript">
    var id;

    window.addEventListener("library-loaded", function (e) {
        if (e.detail.library.match(/requirejs/)) {
            require(["frameworksBuild"], function () {
                require(["jquery", "bootstrap"], function ($) {
                    $(document).ready(function () {

                        jQuery('#wildcard_btn').click(function () {
                            $('#coffeecp_wildcard_modal').modal("show");
                        })

                        jQuery('#wildcard_confirm_btn').click(function () {
                            window.location = "index.live.php?action=mapwildcard";
                        })

                        jQuery('#unmapping_btn').click(function () {
                            $('#coffeecp_unmapping_modal').modal("show");
                        })

                        jQuery('#unmapping_confirm_btn').click(function () {
                            window.location = "index.live.php?action=unmapping";
                        })
                    })
                });
            });
        }
    });

</script>
<?php
$cpanel->end();
?>