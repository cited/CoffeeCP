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
print $cpanel->header('Java Server Version', 'java_version', 'icon-coffeecp_version');

include_once ('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Server.php');

$cs = new Coffeecp_Server;
$servers = $cs->getServerLists();

//get server configuration.
$config = $cs->getConfig();

$cs->xmlconnect();
$server = $cs->get_installed_json();
$install_check = false;


if ($server != false) {
    $install_check = true;
}
?>

<style>
    .page-header .page-icon{
        background-image: url('version.png');
        height: 40px;
        width: 50px;
    }
</style>


<p class="description">Select the Java Server version you wish to install below. Important: Installing a new Java Server will overwrite any existing Java Server.</p>

<div class="clearfix">&nbsp;</div>
<div  class="row">

    <div class="col-md-12">

        <?php if ($config) : ?>
            <?php
            if ($install_check) {
                echo '<div class="alert alert-success">Running - ' . $server->server->name . '-' . $server->server->version . " JDK: " . $server->jdk->name . '-' . $server->jdk->version . '</div>';
            }
            ?>


            <table id="sql_db_tbl" class="sortable table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="cell" scope="col">Server</th>
                        <th class="cell" scope="col">Version</th>
                        <th class="cell sorttable_nosort" scope="col">Install?</th>
                    </tr>
                </thead>  <tbody>

                    <?php
                    $index = 0;
                    foreach ($servers->servers as $row) {

                        $codes = '<a href="javascript:void(0);" data-id="' . $index . '" onclick="open_modal(this);">Install</a>';

                        if ($install_check && $row->type == $server->server->type && $row->version == $server->server->version) {
                            $codes = '<a href="javascript:void(0);" data-id="' . $index . '" onclick="open_modal(this);">Re-Install</a>';
                        }

                        echo '<tr>';
                        echo '<td>' . $row->name . '</td><td>' . $row->version . '</td><td>' . $codes . '</td>';
                        echo '</tr>';
                        $index++;
                    }
                    ?>


                </tbody>

            </table>
        <?php else : ?>
            <div class="aler alert-info">
                <div class="">This Feature is not avaliable yet.</div>
            </div>
        <?php endif; ?>

        <div id="coffeecp_confirm_modal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Install</h4>
                    </div>
                    <div class="modal-body">                       
                        <h3>Are you sure?</h3>
                        <div class="alert alert-warning">
                            <label>Warning: This will replace any existing installation.  Be sure to back up any files you wish to save.</label>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="yes-install">Install</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>

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
                require(["jquery"], function ($) {
                    jQuery(document).ready(function () {
                        jQuery('#yes-install').click(function () {
                            window.location = "install.live.php?id=" + id;
                        })
                    })
                });
            });
        }
    });

    function open_modal(obj) {
        require(["jquery", "bootstrap"], function ($) {
            id = $(obj).attr("data-id");
            $('#coffeecp_confirm_modal').modal("show");
        });
    }
</script>
<?php
$cpanel->end();
?>