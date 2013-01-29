<?php
//
// Description
// -----------
// This function will "attach" a task to other module elements.
//
// Arguments
// ---------
// module:				The package.module the thread is located in.
// user_id:				The user who submitted the followup.
// content:				The content of the followup.
// 
// Returns
// -------
//
function ciniki_core_threadAddAttachment(&$ciniki, $module, $object, $business_id, $table, $history_table, $prefix, $id, $package, $a_module, $element, $element_id) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$uuid = $rc['uuid'];

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $table) . " (uuid, business_id, " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
		. "flags, package, module, element, element_id, date_added, last_updated"
		. ") VALUES ('" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
		. "";

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
	if( $rc['stat'] != 'ok' && $rc['err']['dberrno'] != 1062 && $rc['err']['dberrno'] != 1022 ) {
		return $rc;

		//
		// If the insert failed, then try to update an existing row
		//
//		$strsql = "UPDATE " . ciniki_core_dbQuote($ciniki, $table) . " SET last_updated = UTC_TIMESTAMP() "
//			. "WHERE " . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . " = '" . ciniki_core_dbQuote($ciniki, $id) . "' "
//			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "'";
//		$rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
//		if( $rc['stat'] != 'ok' ) {	
//			return $rc;
//		}
	}
	$attachment_id = $rc['insert_id'];

	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $attachment_id, 'uuid', $uuid);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $attachment_id, $prefix . '_id', $id);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $attachment_id, 'flags', 1);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $attachment_id, 'package', $package);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $attachment_id, 'module', $a_module);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $attachment_id, 'element', $element);
	ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
		1, $table, $attachment_id, 'element_id', $element_id);

	//
	// Sync push
	//
	$ciniki['syncqueue'][] = array('push'=>$module . '.' . $object, 
		'args'=>array('id'=>$attachment_id));

	return $rc;
}
?>
