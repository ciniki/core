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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');	
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
		. "(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(last_sync)) AS incremental_age, "
		. "(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(last_partial)) AS partial_age, "
		. "(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(last_full)) AS full_age "
		. "FROM ciniki_business_syncs "
		. "WHERE status = 10 "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'syncs', 'id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['syncs']) ) {
		return array('stat'=>'ok', 'syncs'=>array(), 'cur_time'=>$cur_time);
	}

	return array('stat'=>'ok', 'syncs'=>$rc['syncs'], 'cur_time'=>$cur_time);
}
?>
