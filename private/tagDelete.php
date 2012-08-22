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
function ciniki_core_tagDelete($ciniki, $module, $table, $key_name, $key_value, $type, $name) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');

	//
	// Don't worry about autocommit here, it's taken care of in the calling function
	//

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
	
	return array('stat'=>'ok', 'id'=>$tag_id);
}
?>
