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
function ciniki_core_errorLogDelete($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'error_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Error'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
    $args = $rc['args'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
	$rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.errorLogDelete');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$strsql = "DELETE FROM ciniki_core_error_logs "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['error_id']) . "' "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	return array('stat'=>'ok');
}
?>
