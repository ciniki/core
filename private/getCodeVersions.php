<?php
//
// Description
// -----------
// This function will return the module and package version information from the _versions.info file.
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_getCodeVersions($ciniki) {
	//
	// Check for the _versions.info file
	//
	if( !file_exists($ciniki['config']['ciniki.core']['root_dir'] . "/_versions.ini") ) {
		return array('stat'=>'ok', 'versions'=>array('package'=>array(), 'modules'=>array()));
	} 
	$modules_ini = parse_ini_file($ciniki['config']['ciniki.core']['root_dir'] . "/_versions.ini", true);

	$package = $modules_ini['package'];
	unset($modules_ini['package']);
	$modules = array();
	foreach($modules_ini as $mod_name => $module) {
		$modules[] = array('module'=>array('name'=>$mod_name, 'version'=>$module['version'], 'author'=>$module['author'], 'hash'=>$module['hash']));
	}

	return array('stat'=>'ok', 'package'=>$package, 'modules'=>$modules);
}
?>
