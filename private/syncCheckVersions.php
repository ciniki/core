<?php
//
// Description
// -----------
// This function will check the local and remote business information
// for compatibility.  The tables must all be at the same version on
// either side of the sync, and the modules must be enabled.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
//
function ciniki_core_syncCheckVersions($ciniki, $business_id, $sync_id) {

	//
	// Get the sync information required to send the request
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.uuid AS local_uuid, local_private_key, "
		. "remote_name, remote_uuid, remote_url, remote_public_key "
		. "FROM ciniki_businesses, ciniki_business_syncs "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_businesses.id = ciniki_business_syncs.business_id "
		. "AND ciniki_business_syncs.id = '" . ciniki_core_dbQuote($ciniki, $args['sync_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sync']) || !is_array($rc['sync']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'559', 'msg'=>'Invalid sync'));
	}
	$sync = $rc['sync'];
	$sync['type'] = 'business';

	//
	// Make the request for the remote business information
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('action'=>'info'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	// 
	// Get the local business information
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncInfo');
	$rc = ciniki_core_syncInfo($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Compare local and remote business information
	//

	return array('stat'=>'ok', 'sync'=>$sync);
}
?>
