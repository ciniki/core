<?php
//
// Description
// -----------
// This function will update a list of tags.
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
// type:                The type of the tag. 
//
//                      0 - unknown
//                      1 - List
//                      2 - Category **future**
//
// list:                The array of tag names to add.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_core_tagsUpdate($ciniki, $module, $business_id, $history_table, $table, $key_name, $key_value, $type, $list) {
    //
    // All arguments are assumed to be un-escaped, and will be passed through dbQuote to
    // ensure they are safe to insert.
    //

    // Required functions
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Don't worry about autocommit here, it's taken care of in the calling function
    //

    //
    // Get the existing list of tags for the item
    //
    $strsql = "SELECT id, $key_name, tag_type AS type, tag_name AS name "
        . "FROM $table "
        . "WHERE $key_name = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' "
        . "AND tag_type = '" . ciniki_core_dbQuote($ciniki, $type) . "' "
        . "";
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, $module, 'tags', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tags']) || $rc['num_rows'] == 0 ) {
        $dbtags = array();
    } else {
        $dbtags = $rc['tags'];
    }

    //
    // Delete tags no longer used
    //
    foreach($dbtags as $tag_name => $tag) {
        if( !in_array($tag_name, $list, true) ) {
            //
            // The tag does not exist in the new list, so it should be deleted.
            //
            $strsql = "DELETE FROM $table WHERE id = '" . ciniki_core_dbQuote($ciniki, $tag['id']) . "' ";
            $rc = ciniki_core_dbDelete($ciniki, $strsql, $module);
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
            
            //
            // Update history
            //
            $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag['id'], $key_name, $tag[$key_name]);
            $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag['id'], 'type', $tag['type']);
            $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 3, $table, $tag['id'], 'name', $tag['name']);
        }
    }

    //
    // Add new tags lists
    //
    foreach($list as $tag) {
        if( !in_array($tag, $dbtags) ) {
            // 
            // Setup the SQL statement to insert the new thread
            //
            $strsql = "INSERT INTO $table ($key_name, tag_type, tag_name, date_added, last_updated) VALUES ("
                . "'" . ciniki_core_dbQuote($ciniki, $key_value) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $type) . "', "
                . "'" . ciniki_core_dbQuote($ciniki, $tag) . "', "
                . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
            // 
            // Only return the error if it was not a duplicate key problem.  Duplicate key error
            // just means the tag name is already assigned to the item.
            //
            if( $rc['stat'] != 'ok' && $rc['err']['code'] != '73' ) {
                return $rc;
            }

            //
            // Update the history
            //
            if( $rc['stat'] == 'ok' && $rc['num_affected_rows'] == 1 ) {
                $tag_id = $rc['insert_id'];
                //
                // Update module history
                //
                $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 1, $table, $tag_id, $key_name, $key_value);
                $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 1, $table, $tag_id, 'type', $type);
                $rc = ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id, 1, $table, $tag_id, 'name', $tag);
            }
            
        }
    }

    return array('stat'=>'ok');
}
?>
