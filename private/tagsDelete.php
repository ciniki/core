<?php
//
// Description
// -----------
// This function will remove ALL tags from an item.  
//
// Arguments
// ---------
// ciniki:
// module:				The package.module the tag is located in.
// table:				The database table that stores the tags.
// key_name:			The name of the ID field that links to the item the tag is for.
// key_value:			The value for the ID field.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_core_tagsDelete(&$ciniki, $module, $object, $business_id, $table, $history_table, $key_name, $key_value) {
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
	// Grab the list of tags, so we can add the delete history
	//
	$strsql = "SELECT id, uuid FROM $table "
		. "WHERE $key_name = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'tag');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'43', 'msg'=>'Unable to get tags', 'err'=>$rc['err']));
	}
	$tags = $rc['rows'];

	// 
	// Remove all the tags for an item.  This is faster than doing one at a time.
	//
	$strsql = "DELETE FROM $table "
		. "WHERE $key_name = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, $module);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}

	foreach($tags as $tid => $tag) {
		ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
			3, $table, $tag['id'], '*', '');
		//
		// Sync push delete
		//
		$ciniki['syncqueue'][] = array('push'=>$module . '.' . $object, 
			'args'=>array('delete_uuid'=>$tag['uuid'], 'delete_id'=>$tag['id']));
	}
		
	return array('stat'=>'ok');
}
?>
