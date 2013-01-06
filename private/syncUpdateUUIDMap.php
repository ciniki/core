<?php
//
// Description
// -----------
// This function will update a uuid mapping between a remote business and a local business.  This is
// used primarily when the same user exists on different systems (sysadmin typically) and has a
// different uuid by same email address.  The map to show the different is stored here.
//
// Arguments
// ---------
//
function ciniki_core_syncUpdateUUIDMap(&$ciniki, &$sync, $business_id, $table, $remote_uuid, $local_id) {

	$strsql = "INSERT INTO ciniki_business_sync_uuidmaps (sync_id, table, "
		. "remote_uuid, local_id) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $sync['id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $table) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $remote_uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $local_id) . "' "
		. ")";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
	//
	// Ignore error if a duplicate record warning
	//
	if( $rc['stat'] != 'ok' && $rc['err']['code'] != 73 ) {
		return $rc;
	}

	if( !isset($sync['uuidmaps'][$table]) ) {
		$sync['uuidmaps'][$table] = array();
	}

	$sync['uuidmaps'][$table][$remote_uuid] = $local_id;

	return array('stat'=>'ok');
}
?>
