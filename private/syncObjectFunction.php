<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_core_syncObjectFunction(&$ciniki, &$sync, $business_id, $method, $args) {
	//
	// Check for custom sync function (get, list, lookup, update, delete, push)
	//
	$method_filename = $ciniki['config']['ciniki.core']['root_dir'] . preg_replace('/^(.*)\.(.*)\.(.*)\.(.*)$/','/\1-mods/\2/sync/\3_\4.php', $method);
	$method_function = preg_replace('/^(.*)\.(.*)\.(.*)\.(.*)$/','\1_\2_\3_\4', $method);
	if( file_exists($method_filename) ) {
		require_once($method_filename);
		if( is_callable($method_function) ) {
//			error_log("SYNC-INFO: [$business_id] " . $method . '(' . serialize($args) . ')');
			ciniki_core_syncLog($ciniki, 3, $method . '--(' . serialize($args) . ')', null);
			$rc = $method_function($ciniki, $sync, $business_id, $args);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_syncLog($ciniki, 0, $method . '(' . serialize($args) . ')', $rc['err']);
//				error_log('SYNC-ERR: ' . $method . '(' . serialize($args) . ') - (' . serialize($rc['err']) . ')');
			}
			return $rc;
		} else {
			ciniki_core_syncLog($ciniki, 0, 'Not executable ' . $method . '(' . serialize($args) . ')', null);
//			error_log('SYNC-ERR: Not executable ' . $method . '(' . serialize($args) . ')');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1176', 'msg'=>'Unable to call sync method'));
		}
	} 

	ciniki_core_syncLog($ciniki, 0, 'Does not exist ' . $method . '(' . serialize($args) . ')', null);
//	error_log('SYNC-ERR: Doesn\'t exist' . $method . '(' . serialize($args) . ')');
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1177', 'msg'=>'Unable to call sync method'));
}
?>
