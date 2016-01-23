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

	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.uuid, ciniki_businesses.name "
		. "FROM ciniki_businesses, ciniki_business_modules "
		. "WHERE ciniki_businesses.status = 1 "
        . "AND ciniki_businesses.id = ciniki_business_modules.business_id "
        . "AND ciniki_business_modules.package = 'ciniki' "
        . "AND ciniki_business_modules.module = 'businesses' "
        . "AND (ciniki_business_modules.flags&0x020000) > 0 "
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
