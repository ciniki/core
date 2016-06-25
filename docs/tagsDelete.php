<?php
//
// Description
// -----------
// This function will remove ALL tags from an item.  
//
// Arguments
// ---------
// ciniki:
// module:              The package.module the tag is located in.
// business_id:         The ID of the business the tag belongs to.
// history_table:       The database table which stores the modules history.
// table:               The database table that stores the tags.
// key_name:            The name of the ID field that links to the item the tag is for.
// key_value:           The value for the ID field.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_core_tagsDelete($ciniki, $module, $business_id, $history_table, $table, $key_name, $key_value) {
    //
    // All arguments are assumed to be un-escaped, and will be passed through dbQuote to
    // ensure they are safe to insert.
    //

    // Required functions
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');

    //
    // Don't worry about autocommit here, it's taken care of in the calling function
    //

    //
    // Get the list of tag ID's that were deleted, used to update history
    //
    $strsql = "SELECT id, tag_type AS type, tag_name AS name "
        . "FROM $table "
        . "WHERE $key_name = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' "
        . "";
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, $module, 'tags', 'id');
    if( $rc['stat'] != 'rc' ) {
        return $rc;
    }
    if( !isset($rc['tags']) ) {
        // No tags found
        return array('stat'=>'ok');
    }
    $tags = $rc['tags'];

    // 
    // Remove all the tags for an item.  This is faster than doing one at a time.
    //
    $strsql = "DELETE FROM $table WHERE $key_name = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' ";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, $module);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
        
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    foreach($tags as $tag_id => $tag) {
        //
        // Update history
        //
        $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag_id, $key_name, $key_value);
        $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag_id, 'type', $tag['type']);
        $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag_id, 'name', $tag['name']);
    }

    return array('stat'=>'ok');
}
?>
