<?php
//
// Description
// -----------
// This function will sync the data for a business from the remote
// server to the local server.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
//
function ciniki_core_syncBusiness($ciniki, $business_id, $sync_id) {

	//
	// Check the versions of tables and modules enabled are the same between servers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncCheckVersions');
	$rc = ciniki_core_syncCheckVersions($ciniki, $business_id, $sync_id);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'567', 'msg'=>'Incompatible versions', 'err'=>$rc['err']));
	}
	$sync = $rc['sync'];
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessModule');

	$last_sync_time = date('U');

	//
	// Sync the core modules first
	//
//	$core_modules = array('ciniki.users', 'ciniki.images');
	$core_modules = array();
	foreach($core_modules as $module) {
		$rc = ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, $module);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'251', 'msg'=>'Unable to sync module ' . $module, 'err'=>$rc['err']));
		}
	}

	//
	// Go through the optional modules configured for the business
	//
	foreach($modules as $name => $module) {
		$rc = ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, $name);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'252', 'msg'=>'Unable to sync module ' . $name, 'err'=>$rc['err']));
		}
	}

	//
	// Updated the last sync time
	//
	$strsql = "UPDATE ciniki_business_syncs SET last_sync = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $last_sync_time) . "') "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
