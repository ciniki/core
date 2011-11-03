<?php
//
// Description
// -----------
// This function will add a new customer given a hash of keys.
//
// Info
// ----
// Status: started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
//
//
function ciniki_core_dbInsertFromHash($ciniki, $fields, $record, $prefix, $middle, $suffix) {

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashToSQL.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');

	//
	// Build the SQL string using the provide information
	//
	$rc = ciniki_core_dbHashToSQL($ciniki, $fields, $record, $prefix, $middle, $suffix);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	// 
	// If an SQL string was built, then try to run it
	//
	if( isset($rc['strsql']) && $rc['strsql'] != '') {
		$new_db_record = ciniki_core_dbInsert($ciniki, $rc['strsql'], 'customers');
		return $new_db_record;
	} 

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'70', 'msg'=>'Internal error', 'pmsg'=>'Unable to build SQL insert string'));
}
?>
