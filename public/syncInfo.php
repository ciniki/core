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
// <rsp stat="ok" />
//
function ciniki_core_syncInfo($ciniki) {
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
	$rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.syncInfo');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the list of syncs setup for this business
	//
	$strsql = "SELECT ciniki_businesses.name AS business_name, ciniki_businesses.uuid AS business_uuid, "
		. "ciniki_business_syncs.id AS id, ciniki_business_syncs.business_id, "
		. "ciniki_business_syncs.flags, ciniki_business_syncs.flags AS type, "
		. "ciniki_business_syncs.status, ciniki_business_syncs.status AS status_text, "
		. "remote_name, remote_url, remote_uuid, "
		. "IFNULL(DATE_FORMAT(last_sync, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_sync, "
		. "IFNULL(DATE_FORMAT(last_partial, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_partial, "
		. "IFNULL(DATE_FORMAT(last_full, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_full "
		. "FROM ciniki_business_syncs "
		. "LEFT JOIN ciniki_businesses ON (ciniki_business_syncs.business_id = ciniki_businesses.id) "
		. "ORDER BY ciniki_businesses.name, ciniki_business_syncs.remote_name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'syncs', 'fname'=>'id', 'name'=>'sync',
			'fields'=>array('id', 'business_id', 'business_name', 'business_uuid', 'flags', 'type', 'status', 'status_text', 'remote_name', 'remote_url', 'remote_uuid',
				'last_sync', 'last_partial', 'last_full'),
			'maps'=>array('status_text'=>array('10'=>'Active', '60'=>'Suspended'),
				'type'=>array('1'=>'Push', '2'=>'Pull', '3'=>'Bi'))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	return array('stat'=>'ok', 'name'=>$ciniki['config']['core']['sync.name'], 'local_url'=>$ciniki['config']['core']['sync.url'], 'syncs'=>$rc['syncs']);
}
?>
