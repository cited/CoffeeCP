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
print $cpanel->header('JDK Selection ');

include('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/header.php');

include_once ('/usr/local/cpanel/base/frontend/paper_lantern/coffeecp_static/Coffeecp_Server.php');

$cs = new Coffeecp_Server;
$servers = $cs->getServerLists();
$jdk = $servers->JDK;

$s = $cs->get_installed_json();

if (isset($_POST['jdk']) && $_POST['jdk']) {
    $index = $_POST['jdk'];    
    
    $cs->update_java_path($s, $jdk[$index-1]);

    $s->jdk = $jdk[$index - 1];   
    
    $cs->installed_json($s);
}


$jdkVersion = isset($s->jdk) ? $s->jdk->version : '';
?>
<style>
    .page-header .page-icon {
        background-image: url('jdk.png');
        height: 41px;
        width: 50px;
    }
</style>


    <p id="descMysql" class="description">
        Select the JDK you wish to use.</p>

  
        <form action="" method="post" class="form-horizontal">
            <div class="form-group row">
                <label class="control-label col-md-2">Default JDK</label>
                <div class="controls col-md-5">
                    <select name="jdk" class="form-control">
<?php $i = 1;
foreach ($jdk as $j) : ?>
                            <option value="<?php echo $i++; ?>" <?php echo ($j->version == $jdkVersion) ? 'selected="selected"' : ''; ?> ><?php echo $j->version; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="">
                <button submit class="col-md-offset-2 btn btn-primary">Submit</button>
            </div>

        </form>    

        <div class="return-link"><a href="../index.html">&larr; <cptext "Go Back"></a></div>
   <?php
print $cpanel->footer();

$cpanel->end();
?>