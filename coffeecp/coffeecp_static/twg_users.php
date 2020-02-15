<?php

function RandomString($pw_size){
	$pw_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
    $randstring = '';
    $pw_len = strlen($pw_chars) - 1;
    for ($i = 0; $i < $pw_size; $i++) {
        $randstring .= $pw_chars[rand(0, $pw_len)];
    }
    return $randstring;
}

function tomcat_users($instance_dir){

	$manager_pass = RandomString(32);
	$admin_pass   = RandomString(32);

	$users_content =
"<?xml version='1.0' encoding='utf-8'?>
<tomcat-users>
<role rolename=\"manager-gui\" />
<user username=\"manager\" password=\"$manager_pass\" roles=\"manager-gui\" />

<role rolename=\"admin-gui\" />
<user username=\"admin\" password=\"$admin_pass\" roles=\"manager-gui,admin-gui\" />
</tomcat-users>";
       
       
        $fp = fopen($instance_dir.'/conf/tomcat-users.xml', 'w');
        fwrite($fp, $users_content);
        
//	if(file_put_contents($instance_dir.'/conf/tomcat-users.xml', $users_content) === FALSE){
//		die('Failed to save file content');
//	}

	echo "<p>Setting Tomcat users ...</p>";
	echo "<p>Username/Password: manager/$manager_pass</p>";
	echo "<p>Username/Password: admin/$admin_pass</p>";
}

function wildfly_user($wildfly_home ,$cm, $jdkPath){

	echo "<p>Setting Wildfly user...</p>";
	$manager_pass = RandomString(32);
        
        
        echo $out = $cm->exec("export JAVA_HOME={$jdkPath} && {$wildfly_home}bin/add-user.sh manager $manager_pass");       
        
	if($out != 0){
		die('Error: add-user.sh: '.$cmd_out."\n");
	}

	$users_content = "Username: manager\nPassword:$manager_pass\n";
	if(file_put_contents($wildfly_home.'/auth.txt', $users_content) === FALSE){
		die('Failed to save file content');
	}

	echo "<p>Setting WildFly user ...</p>";
	echo "<p>Username/Password: manager/$manager_pass\n</p>";
}

function domain_control($domain, $op, $glassfish_home, $cm){
	$cmd_rc  = 0;
	$cmd_out = array();
	$out = $cm->exec("$glassfish_home/bin/asadmin $op-domain $domain");
	if($cmd_rc != 0){
		die('Error: asadmin: '.$cmd_out."\n");
	}
	unset($cmd_out);
}

function setup_glassfish_admin($glassfish_home, $glassfish_user, $port_http_admin, $cm){

	$admin_pass = RandomString(32);

	#make password file
	$pw_file_content = "AS_ADMIN_PASSWORD=\n
AS_ADMIN_NEWPASSWORD=$admin_pass\n";
        
	if(file_put_contents($glassfish_home.'/domain1.auth', $pw_file_content) === FALSE){
		die("Error: Failed to set password file content\n");
	}

	$cmd_rc  = 0;
	$cmd_out = array();
	$out = $cm->exec($glassfish_home."/bin/asadmin -p $port_http_admin --user admin --passwordfile $glassfish_home/domain1.auth change-admin-password --domain_name domain1");
	if($cmd_rc != 0){
		die('Error: asadmin: '.$cmd_out."\n");
	}
	unset($cmd_out);

	domain_control('domain1', 'start', $glassfish_home, $cm);

	#remake password file
	$pw_file_content = "AS_ADMIN_PASSWORD=$admin_pass\n";
	if(file_put_contents($glassfish_home.'/domain1.auth', $pw_file_content) === FALSE){
		die("Error: Failed to set password file content\n");
	}

	echo "<p>Enabling GlassFish secure admin...</p>";

	#
	$cmd_out = array();
	$out = $cm->exec($glassfish_home."/bin/asadmin -p $port_http_admin --user admin --passwordfile $glassfish_home/domain1.auth enable-secure-admin");
	if($cmd_rc != 0){
		die('Error: asadmin: '.$cmd_out."\n");
	}
	unset($cmd_out);

	domain_control('domain1', 'restart', $glassfish_home, $cm);
}

//tomcat_users('/tmp/');
//wildfly_user('/opt/wildfly-10.0.0.Final');
//setup_glassfish_admin('/opt/glassfish-4.1.2', 'glassfish', 4848);

