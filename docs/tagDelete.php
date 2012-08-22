<?php
//
// Description
// -----------
// This function will remove a tag name from an item.  
//
// Arguments
// ---------
// ciniki:
// module:				The package.module the tag is located in.
// business_id:			The ID of the business the tag belongs to.
// history_table:		The database table which stores the modules history.
// table:				The database table that stores the tags.
// key_name:			The name of the ID field that links to the item the tag is for.
// key_value:			The value for the ID field.
// type:				The type of the tag.
// name:				The name of the tag.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_core_tagDelete($ciniki, $module, $business_id, $history_table, $table, $key_name, $key_value, $type, $name) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');

	//
	// Don't worry about autocommit here, it's taken care of in the calling function
	//

	//
	// Get the list of tag ID's that were deleted, used to update history
	//
	$strsql = "SELECT id FROM $table "
		. "WHERE $key_name = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' "
		. "AND type = '" . ciniki_core_dbQuote($ciniki, $type) . "' "
		. "AND name = '" . ciniki_core_dbQuote($ciniki, $name) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'tag');
	if( $rc['stat'] != 'rc' ) {
		return $rc;
	}
	if( !isset($rc['tag']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'577', 'msg'=>'No tag found'));
	}
	$tag = $rc['tag'];
	$tag_id = $rc['tag']['id'];

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "DELETE FROM $table WHERE $key_name = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' "
		. "AND type = '" . ciniki_core_dbQuote($ciniki, $type) . "' "
		. "AND name = '" . ciniki_core_dbQuote($ciniki, $name) . "' "
		. "";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, $module);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	
	//
	// Update history
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag_id, $key_name, $key_value);
	$rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag_id, 'type', $type);
	$rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag_id, 'name', $name);

	return array('stat'=>'ok', 'id'=>$tag_id);
}
?>
