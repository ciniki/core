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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkSyncVersions');
	$rc = ciniki_core_syncCheckVersions($ciniki, $business_id, $sync_id);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'567', 'msg'=>'Incompatible versions', 'err'=>$rc['err']));
	}
	$sync = $rc['sync'];

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

	return array('stat'=>'ok');
}
?>


//
// Check compatibility between systems to make sure the
// business has the same modules enabled on both sides
// and table versions are the same.
//


//
// Get the info about the local business
//


//
// Get info about remote business
//


//
// Compare local and remote business tables and modules for compatibility
//



//
// Retrieve lists and elements for each module
//
foreach(module) {
	//
	// Get local list
	//

	//
	// Get remote list
	//

	//
	// Compare for differences, and update local
	//
}
