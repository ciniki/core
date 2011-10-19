<?php
//
// Description
// -----------
// This function will initialize the database structure for the $moss variable.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// 
//
//
function moss_core_dbInit(&$moss) {

	$moss['databases'] = array();

	if( !isset($moss['config']['core']['database.names']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'10', 'msg'=>'Internal configuration error', 'pmsg'=>'missing core.database.name from config.'));
	}

	$databases = preg_split('/\s*\,\s*/', $moss['config']['core']['database.names']);

	foreach($databases as $db) {
		$moss['databases'][$db] = array();
	}

	//
	// Check if core database has been defined
	//
	if( !isset($moss['databases']['core']) || !is_array($moss['databases']['core']) ) {
		$moss['databases']['core'] = array();
	}

	require_once($moss['config']['core']['modules_dir'] . "/core/private/dbConnect.php");

	$rc = moss_core_dbConnect($moss, 'core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
