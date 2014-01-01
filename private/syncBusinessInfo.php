<?php
//
// Description
// -----------
// This function will return the info about a business.  This information is used
// to compare with the remote system to determine if the two are compatitable for 
// a sync.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to get the sync information for.
//
// Returns
// -------
// <modules>
//		<module name="ciniki.artcatalog" permissions="" version="20121226.2232">
//			<tables>
//				<table name="ciniki_artcatalog" version="v1.01" />
//				<table name="ciniki_artcatalog_history" version="v1.01" />
//			</tables>
//		</module>
// </modules>
//
function ciniki_core_syncBusinessInfo($ciniki, $business_id) {

	//
	// Check to make sure a business is specified
	//
	if( $business_id < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'557', 'msg'=>'No business specified'));
	}

	//
	// Grab the _versions.ini info
	//
	if( !file_exists($ciniki['config']['ciniki.core']['root_dir'] . "/_versions.ini") ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'290', 'msg'=>'Unable to get module version information'));
	}
	$modules_ini = parse_ini_file($ciniki['config']['ciniki.core']['root_dir'] . "/_versions.ini", true);

	//
	// Result array
	//
	$rsp = array('stat'=>'ok', 'tables'=>array(), 'business'=>array('modules'=>array()));

	//
	// Get all the table versions
	//
	$strsql = "SHOW TABLE STATUS";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.core', 'tables', 'Name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'404', 'msg'=>'Unable to get table versions', 'err'=>$rc['err']));
	}

	if( !isset($rc['tables']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'295', 'msg'=>'Unable to get table versions'));
	}
	$db_tables = $rc['tables']; 

	//
	// Get modules which are enabled for the business, and their checksums
	//
	$strsql = "SELECT CONCAT_WS('.', package, module) AS fname, "
		. "package, module AS name, UNIX_TIMESTAMP(last_change) AS last_change "
		. "FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (status = 1 OR status = 2) "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'modules', 'fname'=>'fname', 'name'=>'module',
			'fields'=>array('package', 'name', 'last_change')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'574', 'msg'=>'Unable to get active modules', 'err'=>$rc['err']));
	}
	if( !isset($rc['modules']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'569', 'msg'=>'Unable to get active modules'));
	}
	$modules = $rc['modules'];

	$mods = array();
	foreach($modules as $mid => $module) {
		$mod[$module['module']['package'] . '.' . $module['module']['name']] = $module['module']['last_change'];
	}
	if( !isset($mods['ciniki.users']) ) {
		$modules[] = array('module'=>array('package'=>'ciniki', 'name'=>'users', 'last_change'=>0));
	}
	if( !isset($mods['ciniki.images']) ) {
		$modules[] = array('module'=>array('package'=>'ciniki', 'name'=>'images', 'last_change'=>0));
	}
	if( !isset($mods['ciniki.businesses']) ) {
		$modules[] = array('module'=>array('package'=>'ciniki', 'name'=>'businesses', 'last_change'=>0));
	}

	//
	// Check each package/module for table.schema's and get version from database
	//
	foreach($modules as $mnum => $module) {
		//
		// Setup module version
		//
		if( isset($modules_ini[$module['module']['package'] . '.api.' . $module['module']['name']]) ) {
			$modules[$mnum]['module']['version'] = $modules_ini[$module['module']['package'] .'.api.' . $module['module']['name']]['version'];
			$modules[$mnum]['module']['hash'] = $modules_ini[$module['module']['package'] .'.api.' . $module['module']['name']]['hash'];
		} else {
			$modules[$mnum]['module']['version'] = '';
			$modules[$mnum]['module']['hash'] = '';
		}
		$modules[$mnum]['module']['tables'] = array();
		$dir = $ciniki['config']['core']['root_dir'] . '/' . $module['module']['package'] . '-mods/' . $module['module']['name'] . '/db';
		if( !is_dir($dir) ) {
			continue;
		}
		$dh = opendir($dir);
		if( $dh == false ) {
			continue;
		}
		while( false !== ($filename = readdir($dh))) {
			if( $filename[0] == '.' ) {
				continue;
			}
			if( preg_match('/^(.*)\.schema$/', $filename, $matches) ) {
				$table = $matches[1];
				if( isset($db_tables[$table]) && preg_match('/(v[0-9]+\.[0-9]+)([^0-9]|$)/i', $db_tables[$table]['Comment'], $matches) ) {
					array_push($modules[$mnum]['module']['tables'], array('table'=>array('name'=>$table, 'version'=>$matches[1])));
				}
			}
		}
	}

	//
	// Return information
	//
	return array('stat'=>'ok', 'modules'=>$modules);
}
?>
