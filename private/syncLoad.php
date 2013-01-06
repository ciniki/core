<?php
//
// Description
// -----------
// This function will load the sync details for running sync commands.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
//
function ciniki_core_syncLoad($ciniki, $business_id, $sync_id) {

	//
	// Get the sync information required to send the request
	//
	$strsql = "SELECT ciniki_business_syncs.id, ciniki_businesses.uuid AS local_uuid, "
		. "ciniki_business_syncs.status, "
		. "ciniki_business_syncs.flags, local_private_key, "
		. "ciniki_business_syncs.remote_name, ciniki_business_syncs.remote_uuid, "
		. "ciniki_business_syncs.remote_url, ciniki_business_syncs.remote_public_key, "
		. "UNIX_TIMESTAMP(last_sync) AS last_sync "
		. "FROM ciniki_businesses, ciniki_business_syncs "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_businesses.id = ciniki_business_syncs.business_id "
		. "AND ciniki_business_syncs.id = '" . ciniki_core_dbQuote($ciniki, $sync_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sync']) || !is_array($rc['sync']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'113', 'msg'=>'Invalid sync'));
	}
	$sync = $rc['sync'];
	$sync['type'] = 'business';

	//
	// Get the user uuidmaps
	//
	$strsql = "SELECT "
		. "remote_uuid, local_uuid "
		. "FROM ciniki_business_sync_uuidmaps "
		. "WHERE sync_id = '" . ciniki_core_dbQuote($ciniki, $sync['id']) . "' "
		. "AND module = 'ciniki.users' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'uuids', 'remote_uuid');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['uuids']) ) {
		$sync['uuidmaps'] = array('ciniki.users'=>$rc['uuids']);
	} else {
		$sync['uuidmaps'] = array();
	}

	return array('stat'=>'ok', 'sync'=>$sync);
}
?>
