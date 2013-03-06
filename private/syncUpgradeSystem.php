<?php
//
// Description
// -----------
// This function will check for the latest versions of modules and upgrade
// if required, including any database upgrades.  In order to work, the 
// config entry sync.code.url must be speified in ciniki-api.ini.  This is
// the url to fetch the latest versions and code from.
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_syncUpgradeSystem($ciniki) {

	if( !isset($ciniki['config']['ciniki.core']['sync.code.url']) 
		|| $ciniki['config']['ciniki.core']['sync.code.url'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'503', 'msg'=>'No sync code url specified, unable to upgrade module'));
	}

	$url = $ciniki['config']['ciniki.core']['sync.code.url'];

	//
	// Get the version information from the remote system
	//
	$remote_versions = file_get_contents($url . '/_versions.ini');
	if( $remote_versions === false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'504', 'msg'=>'Unable to get the remote versions'));
	}
	$remote_modules = parse_ini_string($remote_versions, true);
	if( $remote_modules === false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'259', 'msg'=>'Unable to parse remote versions file'));
	}

	//
	// Get the local version information
	//
	$local_versions_file = $ciniki['config']['ciniki.core']['root_dir'] . '/_versions.ini';
	if( !file_exists($local_versions_file) ) {
		$local_modules = array();
	} else {
		$local_modules = parse_ini_file($local_versions_file, true);
	}

	//
	// Upgrade the modules with different versions.  Don't check for
	// newer/older as we want the current code from the source site.
	// The source site may have older code due to bug and downgrade.
	// 
	// Note: This code also exists in the ciniki-install.php file
	//
	foreach($remote_modules as $mod_name => $module) {
		if( !isset($local_modules[$mod_name]) 
			|| $local_modules[$mod_name]['version'] != $remote_modules[$mod_name]['version'] ) {

			if( isset($local_modules[$mod_name]) ) {
				error_log('[' . date('d/M/Y:H:i:s O') . '] ' . "Upgrading $mod_name (" . $local_modules[$mod_name]['version'] . ' -> ' . $module['version'] . ')');
			} else {
				error_log('[' . date('d/M/Y:H:i:s O') . '] ' . "Upgrading $mod_name to " . $module['version'] . '');
			}
			//
			// Fetch the zip file into site/ciniki-code
			//
			$remote_zip = file_get_contents($url . "/$mod_name.zip");
			if( $remote_zip === false ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'546', 'msg'=>"Unable to get remote $mod_name.zip"));
			}
			$zipfilename = $ciniki['config']['ciniki.core']['root_dir'] . "/ciniki-code/$mod_name.zip";
			if( file_put_contents($zipfilename, $remote_zip) === false ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'559', 'msg'=>"Unable to write ciniki-code/$mod_name.zip"));
			}

			//
			// Unzip the file
			//
			$zip = new ZipArchive;
			$res = $zip->open($zipfilename);
			if ($res === TRUE) {
				$mpieces = preg_split('/\./', $mod_name);
				$zip->extractTo($ciniki['config']['ciniki.core']['root_dir'] . '/' . $mpieces[0] . '-' . $mpieces[1] . '/' . $mpieces[2]);
				$zip->close();
			} else {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'664', 'msg'=>"Unable to extract $mod_name.zip"));
			}
		}
	}

	//
	// Upgrade the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpgradeTables');
	$rc = ciniki_core_dbUpgradeTables($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the _versions.ini file
	//
	$versions = "";	
	$dir = opendir($ciniki['config']['ciniki.core']['root_dir']);
	while( ($file = readdir($dir)) !== false ) {
		if( preg_match('/^(ciniki)-(api|lib|manage-themes|manage)$/', $file, $matches) ) {
			$mdir = opendir($ciniki['config']['ciniki.core']['root_dir'] . "/$file");
			while( ($mfile = readdir($mdir)) != false ) {
				$vfilename = $ciniki['config']['ciniki.core']['root_dir'] . "/$file/$mfile/_version.ini";
				if( file_exists($vfilename) ) {
					$versions .= '[' . $matches[1] . '.' . $matches[2] . '.' . $mfile . "]\n";
					$versions .= file_get_contents($vfilename);
					$versions .= "\n";
				}
			}
			closedir($mdir);
		}
	}
	closedir($dir);
	if( !file_put_contents($ciniki['config']['ciniki.core']['root_dir'] . '/_versions.ini', $versions) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'505', 'msg'=>'Unable to write new versions file'));
	}

	if( !file_put_contents($ciniki['config']['ciniki.core']['root_dir'] . '/ciniki-code/_versions.ini', $versions) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'654', 'msg'=>'Unable to write new versions file'));
	}

	return array('stat'=>'ok');
}
?>
