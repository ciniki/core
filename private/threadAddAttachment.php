<?php
//
// Description
// -----------
// This function will "attach" a task to other module elements.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// user_id:				The user who submitted the followup.
// content:				The content of the followup.
// 
// Returns
// -------
//
function ciniki_core_threadAddAttachment($ciniki, $module, $table, $prefix, $id, $package, $a_module, $element, $element_id) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $table) . " (" . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
		. "flags, package, module, element, element_id, date_added, last_updated"
		. ") VALUES (";

	// $prefix_id (bug_id, help_id, comment_id, etc...
	if( $id != null && $id > 0 ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $id) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'223', 'msg'=>'Required argument missing', 'pmsg'=>"No {$prefix}_id"));
	}

	$strsql .= "'1', "; // Primary attachment
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $package) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $a_module) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $element) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $element_id) . "', ";

	$strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	$rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
	//
	// Chech for a duplicate key error, and then run an update
	//
	if( $rc['stat'] != 'ok' && ($rc['err']['dberrno'] == 1062 || $rc['err']['dberrno'] == 1022) ) {
		//
		// If the insert failed, then try to update an existing row
		//
		$strsql = "UPDATE " . ciniki_core_dbQuote($ciniki, $table) . " SET last_updated = UTC_TIMESTAMP() "
			. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "'";
		return ciniki_core_dbUpdate($ciniki, $strsql, $module);
	}

	return $rc;
}
?>
