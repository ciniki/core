<?php
//
// This file is the install script which will configure and setup the database
// and configuration files on disk.  The script will only run if it can't find
// a ciniki-api.ini file
//


//
// Figure out where the root directory is.  This file may be symlinked
//
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api') ) {

	$ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
$modules_dir = $ciniki_root . '/ciniki-api';

//
// Verify no ciniki-api.ini file
//
if( file_exists($ciniki_root . '/ciniki-api.ini') ) {
	print_page('no', 'ciniki.installer.15', 'Already installed.</p><p><a href="/manage/">Login</a>');
	exit();
}

//
// Check for ../.htaccess file
//
if( file_exists($ciniki_root . '/.htaccess') ) {
	print_page('no', 'ciniki.installer.14', 'Already installed.</p><p><a href="/manage/">Login</a>');
	exit();
}

//
// If they didn't post anything, display the form, otherwise run an install
//
if( !isset($_POST['database_host']) ) {
	print_page('yes', '', '');
} else {
	install($ciniki_root, $modules_dir);
}

exit();





function print_page($display_form, $err_code, $err_msg) {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Ciniki Installer</title>
<link rel='stylesheet' type='text/css' href='themes/default/style.css' />
<link rel='stylesheet' type='text/css' href='themes/default/e-webkit.css' />
<link rel='stylesheet' type='text/css' href='themes/default/s-normal.css' />
<link rel='stylesheet' type='text/css' href='themes/default/d-generic.css' />
<link rel='stylesheet' type='text/css' href='themes/default/s-normal-webkit.css' />

</head>
<body id="m_body">
<div id='m_container' class="s-normal">
	<table id="mc_header" class="headerbar" cellpadding="0" cellspacing="0">
		<tr>
		<td id="mc_home_button" style="display:none;"><img src="themes/default/img/home_button.png" onClick="mossi_showMenu('home');"/></td>
		<td id="mc_title" class="title" >Ciniki Installer</td>
		<td id="mc_help_button" style="display:none;"><img src="themes/default/img/help_button.png" onClick="mossi_toggleHelp();"/></td>
		</tr>
	</table>
	<div id="mc_content">
	<div id="mc_content_scroller" class="scrollable">
	<div id="mc_apps">
		<div id="mapp_installer" class="mapp">
			<div id="mapp_installer_content" class="panel">
				<div class="medium">
				<?php
					if( $err_code == 'installed' ) {
						print "<h2 class=''>Installed</h2><div class='bordered error'><p>Ciniki installed and configured, you can now login and finished installing the database.  </p><p><a href='/mossi/'>Login</a></p></div>";

					}
					elseif( $err_code != '' ) {
						print "<h2 class='error'>Error</h2><div class='bordered error'><p>Error $err_code - $err_msg</p></div>";
					}
				?>
				<?php if( $display_form == 'yes' ) { ?>
					<form id="mapp_installer_form" method="POST" name="mapp_installer_form">
						<h2>Database</h2>
						<table class="list noheader form outline" cellspacing='0' cellpadding='0'>
							<tbody>
							<tr class="textfield"><td class="label"><label for="database_host">Host</label></td>
								<td class="input"><input id="database_host" name="database_host" type="text"/></td></tr>
							<tr class="textfield"><td class="label"><label for="database_username">User</label></td>
								<td class="input"><input type="text" id="database_username" name="database_username" /></td></tr>
							<tr class="textfield"><td class="label"><label for="database_password">Password</label></td>
								<td class="input"><input type="password" id="database_password" name="database_password" /></td></tr>
							<tr class="textfield"><td class="label"><label for="database_name">Name</label></td>
								<td class="input"><input type="text" id="database_name" name="database_name" /></td></tr>
							</tbody>
						</table>
						<h2>Admin</h2>
						<table class="list noheader form outline" cellspacing='0' cellpadding='0'>
							<tbody>
							<tr class="textfield"><td class="label"><label for="admin_email">Email</label></td>
								<td class="input"><input type="email" id="admin_email" name="admin_email" /></td></tr>
							<tr class="textfield"><td class="label"><label for="admin_username">Username</label></td>
								<td class="input"><input type="text" id="admin_username" name="admin_username" /></td></tr>
							<tr class="textfield"><td class="label"><label for="admin_password">Password</label></td>
								<td class="input"><input type="password" id="admin_password" name="admin_password" /></td></tr>
							<tr class="textfield"><td class="label"><label for="admin_firstname">First</label></td>
								<td class="input"><input type="text" id="admin_firstname" name="admin_firstname" /></td></tr>
							<tr class="textfield"><td class="label"><label for="admin_lastname">Last</label></td>
								<td class="input"><input type="text" id="admin_lastname" name="admin_lastname" /></td></tr>
							<tr class="textfield"><td class="label"><label for="admin_display_name">Display</label></td>
								<td class="input"><input type="text" id="admin_display_name" name="admin_display_name" /></td></tr>
							</tbody>
						</table>
						<h2>Master Business</h2>
						<div class="section">
						<table class="list noheader form outline" cellspacing='0' cellpadding='0'>
							<tbody>
							<tr class="textfield"><td class="label"><label for="master_name">Name</label></td>
								<td class="input"><input type="text" id="master_name" name="master_name" /></td></tr>
							<tr class="textfield"><td class="label"><label for="master_website">Domain</label></td>
								<td class="input"><input type="text" id="master_website" name="master_website" /></td></tr>
							</tbody>
						</table>
						</div>
						<h2>Websites</h2>
						<div class="section">
						<table class="list noheader form outline" cellspacing='0' cellpadding='0'>
							<tbody>
							<tr class="textfield"><td class="label"><label for="www_password">Password</label></td>
								<td class="input"><input type="password" id="www_password" name="www_password" /></td></tr>
							<tr class="textfield"><td class="label"><label for="www_password2">Again</label></td>
								<td class="input"><input type="password" id="www_password2" name="www_password2" /></td></tr>
							</tbody>
						</table>
						</div>
						<h2>Notifications</h2>
						<div class="section">
						<table class="list noheader form outline" cellspacing='0' cellpadding='0'>
							<tbody>
							<tr class="textfield"><td class="label"><label for="alert_notify">Alerts</label></td>
								<td class="input"><input type="email" id="alert_notify" name="alert_notify" /></td></tr>
							<tr class="textfield"><td class="label"><label for="help_notify">Questions</label></td>
								<td class="input"><input type="email" id="help_notify" name="help_notify" /></td></tr>
							</tbody>
						</table>
						</div>
						<div style="text-align:center;">
							<input type="submit" value=" Install " class="button">
						</div>
					</form>
				<?php } ?>
			</div>
			</div>
		</div>
	</div>
	</div>
	</div>
</div>
</body>
</html>
<?php
}


//
// Install Procedure
//

function install($ciniki_root, $modules_dir) {

	$database_host = $_POST['database_host'];
	$database_username = $_POST['database_username'];
	$database_password = $_POST['database_password'];
	$database_name = $_POST['database_name'];
	$admin_email = $_POST['admin_email'];
	$admin_username = $_POST['admin_username'];
	$admin_password = $_POST['admin_password'];
	$admin_firstname = $_POST['admin_firstname'];
	$admin_lastname = $_POST['admin_lastname'];
	$admin_display_name = $_POST['admin_display_name'];
	$master_name = $_POST['master_name'];
	$master_website = $_POST['master_website'];
	$www_password = $_POST['www_password'];
	$www_password2 = $_POST['www_password2'];
	$alert_notify = $_POST['alert_notify'];
	$help_notify = $_POST['help_notify'];

	$mossi_api_key = md5(date('Y-m-d-H-i-s') . rand());
	$www_api_key = md5(date('Y-m-d-H-i-s') . rand());

	//
	// Connect to the database
	//
	$dh = mysql_connect($database_host, $database_username, $database_password);
	if( $dh == false ) {
		print_page('yes', 'ciniki.installer.04', "Failed to connect to the database, please check your database connection settings");
		exit();
	}
	if( mysql_select_db($database_name, $dh) == false ) {
		print_page('yes', 'ciniki.installer.05', "Failed to connect to the database '$database_name', please check your database connection settings, " 
			. mysql_error($dh));
		exit();
	}

	//
	// Create the tables
	//
	$users = file_get_contents($modules_dir . '/users/db/users.schema');
	if( $users == false ) {
		print_page('yes', 'ciniki.installer.06', "Failed to create the database, please check the installation.");
		exit();
	}

	$user_details = file_get_contents($modules_dir . '/users/db/user_details.schema');
	if( $user_details == false ) {
		print_page('yes', 'ciniki.installer.26', "Failed to create the database, please check the installation.");
		exit();
	}

	$businesses = file_get_contents($modules_dir . '/businesses/db/businesses.schema');
	if( $businesses == false ) {
		print_page('yes', 'ciniki.installer.07', "Failed to create the database, please check the installation.");
		exit();
	}

	$business_details = file_get_contents($modules_dir . '/businesses/db/business_details.schema');
	if( $businesses == false ) {
		print_page('yes', 'ciniki.installer.28', "Failed to create the database, please check the installation.");
		exit();
	}

	$business_users = file_get_contents($modules_dir . '/businesses/db/business_users.schema');
	if( $business_users == false ) {
		print_page('yes', 'ciniki.installer.18', "Failed to create the database, please check the installation.");
		exit();
	}

	$core_session_data = file_get_contents($modules_dir . '/core/db/core_session_data.schema');
	if( $core_session_data == false ) {
		print_page('yes', 'ciniki.installer.07', "Failed to create the database, please check the installation.");
		exit();
	}

	$core_api_keys = file_get_contents($modules_dir . '/core/db/core_api_keys.schema');
	if( $core_api_keys == false ) {
		print_page('yes', 'ciniki.installer.20', "Failed to create the database, please check the installation.");
		exit();
	}

	$rc = mysql_query("SHOW TABLE STATUS", $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.11', "Unable to create database");	
		exit();
	}
	$businesses_table_exist = 0;
	$business_users_table_exist = 0;
	$users_table_exist = 0;
	$user_details_table_exist = 0;
	$core_session_data_exist = 0;
	$core_api_keys_exist = 0;
	while( $row = mysql_fetch_assoc($rc) ) {
		if( $row['Name'] == 'businesses' ) {
			$businesses_table_exist = 1;	
		}
		if( $row['Name'] == 'business_users' ) {
			$business_users_table_exist = 1;	
		}
		if( $row['Name'] == 'users' ) {
			$users_table_exist = 1;	
		}
		if( $row['Name'] == 'user_details' ) {
			$user_details_table_exist = 1;	
		}
		if( $row['Name'] == 'core_session_data' ) {
			$core_session_data_exist = 1;	
		}
		if( $row['Name'] == 'core_api_keys' ) {
			$core_api_keys_exist = 1;	
		}
	}
	
	// Ensure the tables do not already exist
	if( $businesses_table_exist == 1 ) {
		print_page('yes', 'ciniki.installer.11', "Unable to create database");	
		exit();
	}
	if( $business_users_table_exist == 1 ) {
		print_page('yes', 'ciniki.installer.19', "Unable to create database");	
		exit();
	}
	if( $users_table_exist == 1 ) {
		print_page('yes', 'ciniki.installer.11', "Unable to create database");	
		exit();
	}
	if( $user_details_table_exist == 1 ) {
		print_page('yes', 'ciniki.installer.11', "Unable to create database");	
		exit();
	}
	if( $core_session_data_exist == 1 ) {
		print_page('yes', 'ciniki.installer.15', "Unable to create database");	
		exit();
	}
	if( $core_api_keys_exist == 1 ) {
		print_page('yes', 'ciniki.installer.22', "Unable to create database");	
		exit();
	}

	$rc = mysql_query($users, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.08', "Failed to create database");
		exit();
	}

	$rc = mysql_query($user_details, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.27', "Failed to create database");
		mysql_query("DROP TABLE users", $dh);
		exit();
	}

	$rc = mysql_query($businesses, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.09', "Failed to create database");
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		exit();
	}

	$rc = mysql_query($core_session_data, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.16', "Failed to create database");
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE businesses", $dh);
		exit();
	}

	$rc = mysql_query($business_users, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.17', "Failed to create database");
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		exit();
	}

	$rc = mysql_query($core_api_keys, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.21', "Failed to create database");
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		exit();
	}

	// Create www user
	$strsql = "INSERT INTO users (id, email, username, password, perms, status, timeout, "
		. "firstname, lastname, display_name, date_added, last_updated) VALUES ( "
		. "'1', 'www@nodomain.com', 'www', SHA1('$www_password'), 4, 1, 0, "
		. "'Web', 'User', 'anonymous', UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = mysql_query($strsql, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.10', "Failed to create user.</p><p>" . mysql_error($dh));
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	// Create Ciniki user
	$strsql = "INSERT INTO users (id, email, username, password, perms, status, timeout, "
		. "firstname, lastname, display_name, date_added, last_updated) VALUES ( "
		. "'2', 'nobody@nodomain.com', 'mossi', SHA1('" . md5(rand() + date('H-i-s')) . "'), 0, 2, 0, "
		. "'ciniki-manage', 'User', 'anonymous', UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = mysql_query($strsql, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.10', "Failed to create user.</p><p>" . mysql_error($dh));
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	// Create admin user
	$strsql = "INSERT INTO users (id, email, username, password, perms, status, timeout, "
		. "firstname, lastname, display_name, date_added, last_updated) VALUES ( "
		. "'3', '$admin_email', '$admin_username', SHA1('$admin_password'), 1, 1, 0, "
		. "'$admin_firstname', '$admin_lastname', '$admin_display_name', UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = mysql_query($strsql, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.12', "Failed to create user.</p><p>" . mysql_error($dh));
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	//
	// Create master company
	// Turn on website, bug tracking, feature requests and questions by default.
	//
	$strsql = "INSERT INTO businesses (id, modules, name, tagline, description, status, date_added, last_updated) VALUES ("
		. "'1', 0x3808, '$master_name', '', '', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = mysql_query($strsql, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.13', "Failed to create user.</p><p>" . mysql_error($dh));
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}
	$master_business_id = mysql_insert_id($dh);

	//
	// Attach admin user to 
	//
	$strsql = "INSERT INTO business_users (business_id, user_id, groups, type, status, date_added, last_updated) VALUES ("
		. "'1', '3', '1', '1', '1', UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = mysql_query($strsql, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.29', "Failed to create user.</p><p>" . mysql_error($dh));
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	// Create api key for www
	$strsql = "INSERT INTO core_api_keys (api_key, status, perms, user_id, appname, notes, "
		. "last_access, expiry_date, date_added, last_updated) VALUES ("
		. "'$www_api_key', 1, 0, 1, 'www', '', 0, 0, now(), now())";
	$rc = mysql_query($strsql, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.23', "Failed to create user.</p><p>" . mysql_error($dh));
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	// Create api key for ciniki-manage
	$strsql = "INSERT INTO core_api_keys (api_key, status, perms, user_id, appname, notes, "
		. "last_access, expiry_date, date_added, last_updated) VALUES ("
		. "'$mossi_api_key', 1, 0, 2, 'ciniki-manage', '', 0, 0, now(), now())";
	$rc = mysql_query($strsql, $dh);
	if( $rc == false ) {
		print_page('yes', 'ciniki.installer.24', "Failed to create user.</p><p>" . mysql_error($dh));
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	//
	// Setup config.ini -- use config.ini.default
	//
	$config = parse_ini_file("../config.ini.default", true);
	$config['core']['ciniki_root'] = $ciniki_root;
	$config['core']['modules_dir'] = $ciniki_root . '/moss-modules';
	$config['core']['admin_dir'] = $ciniki_root . '/mossi';
	$config['core']['content_dir'] = $ciniki_root . '/moss-content';
	$config['core']['lib_dir'] = $ciniki_root . '/moss-lib';

	$config['core']['database'] = $database_name;
	$config['core']['database.names'] = $database_name;
	$config['core']["database.$database_name.hostname"] = $database_host;
	$config['core']["database.$database_name.username"] = $database_username;
	$config['core']["database.$database_name.password"] = $database_password;
	$config['core']["database.$database_name.database"] = $database_name;

	$config['core']['alerts.notify'] = $alert_notify;
	$config['core']['master_business_id'] = $master_business_id;
	$config['mossi']['help.notify'] = $help_notify;

	$config['websites']['default'] = $master_website;
	$config['websites']['api_key'] = $www_api_key;
	$config['websites']['url'] = 'http://' . $master_website . '/rest.php';
	$config['websites']['username'] = 'www';
	$config['websites']['password'] = $www_password;

	// Write the config file
	$new_config = "";
	foreach($config as $module => $settings) {
		$new_config .= "[$module]\n";
		foreach($settings as $key => $value) {
			$new_config .= "	$key = $value\n";
		}
		$new_config .= "\n";
	}
	$num_bytes = file_put_contents($ciniki_root . '/config.ini', $new_config);
	if( $num_bytes == false || $num_bytes < strlen($new_config)) {
		print_page('yes', 'ciniki.installer.01', "Unable to write configuration, please check your website settings.");
		unlink($ciniki_root . '/config.ini');
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	$mossi_config = "api_key = $mossi_api_key\n\n";
	$num_bytes = file_put_contents($ciniki_root . '/mossi/config.ini', $mossi_config);
	if( $num_bytes == false || $num_bytes < strlen($mossi_config)) {
		print_page('yes', 'ciniki.installer.25', "Unable to write configuration, please check your website settings.");
		unlink($ciniki_root . '/config.ini');
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}


	//
	// Setup .htaccess
	//
	$htaccess = file_get_contents($ciniki_root . '/.htaccess.default');
	if( $htaccess == false ) {
		print_page('yes', 'ciniki.installer.02', "Unable to save configuration");
		unlink($ciniki_root . '/.htaccess');
		unlink($ciniki_root . '/mossi/config.ini');
		unlink($ciniki_root . '/config.ini');
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}
	$num_bytes = file_put_contents($ciniki_root . '/.htaccess', $htaccess);
	if( $num_bytes == false || $num_bytes < strlen($htaccess)) {
		print_page('yes', 'ciniki.installer.03', "Unable to save configuration");
		unlink($ciniki_root . '/.htaccess');
		unlink($ciniki_root . '/mossi/config.ini');
		unlink($ciniki_root . '/config.ini');
		mysql_query("DROP TABLE businesses", $dh);
		mysql_query("DROP TABLE business_users", $dh);
		mysql_query("DROP TABLE users", $dh);
		mysql_query("DROP TABLE user_details", $dh);
		mysql_query("DROP TABLE core_session_data", $dh);
		mysql_query("DROP TABLE core_api_keys", $dh);
		exit();
	}

	print_page('no', 'installed', '');
}
?>
