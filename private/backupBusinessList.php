<?php
//
// Description
// -----------
// This method will return the list of businesses to backup.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
//
//
function ciniki_core_backupBusinessList($ciniki) {

	$strsql = "SELECT id, uuid, name "
		. "FROM ciniki_businesses "
		. "WHERE status = 1 "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'businesses', 'fname'=>'id',
			'fields'=>array('id', 'uuid', 'name')),
			));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	return $rc;
}
?>
