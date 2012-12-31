<?php
//
// Description
// -----------
// This function will sync a modules data between two ciniki installs.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync:			The sync object containing the sync keys and URL's.
// module:			The module to sync.
// type:			The type of sync (full, partial, incremental)
//
function ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, $module, $type) {

//	$method_filename = $ciniki['config']['core']['root_dir'] . preg_replace("^(.*)\.(.*)$", "/\1-api/\2/sync/pull.php", $module);
//	$method_function = preg_replace("^(.*)\.(.*)$", "/\1_\2_sync_pull", $module);
	$method_filename = $ciniki['config']['core']['root_dir'] . preg_replace('/^(.*)\.(.*)$/', '/\1-api/\2/private/syncModule.php', $module);
	$method_function = preg_replace('/^(.*)\.(.*)$/', '\1_\2_syncModule', $module);

	if( !file_exists($method_filename) ) {
		return array('stat'=>'ok');
	}

	require_once($method_filename);
	if( !is_callable($method_function) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'253', 'msg'=>'Unable to sync module'));
	}

	$rc = $method_function($ciniki, $sync, $business_id, array('type'=>$type));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
