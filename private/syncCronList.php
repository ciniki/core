<?php
//
// Description
// -----------
// This function will return the list of syncs for all businesses.
//
// Arguments
// ---------
// ciniki:
//
function ciniki_core_syncCronList($ciniki) {

	$strsql = "SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) AS cur_time ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$cur_time = $rc['sync']['cur_time'];

	//
	// Get the sync information required to send the request
	//
	$strsql = "SELECT id, business_id, "
		. "UNIX_TIMESTAMP(last_sync) AS last_sync, "
		. "UNIX_TIMESTAMP(last_partial) AS last_partial, "
		. "UNIX_TIMESTAMP(last_full) AS last_full "
		. "FROM ciniki_business_syncs "
		. "WHERE status = '10' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.businesses', 'syncs', 'id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['syncs']) ) {
		return array('stat'=>'ok', 'sync'=>array(), 'cur_time'=>$cur_time);
	}

	return array('stat'=>'ok', 'syncs'=>$syncs, 'cur_time'=>$cur_time);
}
?>
