<?php
//
// Description
// -----------
// This function will add a followup response to a thread.
//
// Info
// ----
// Status:      beta
//
// Arguments
// ---------
// module:              The package.module the thread is located in.
// user_id:             The user who submitted the followup.
// content:             The content of the followup.
// 
// Returns
// -------
//
function ciniki_core_threadAddFollowup(&$ciniki, $module, $object, $business_id, $table, $history_table, $prefix, $id, $args) {
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
    $strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $table) . " (uuid, business_id, "
        . "" . ciniki_core_dbQuote($ciniki, "{$prefix}_id") . ", "
        . "user_id, content, date_added, last_updated"
        . ") VALUES ('" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
        . "";

    // $prefix_id (bug_id, help_id, comment_id, etc...
    if( $id != null && $id > 0 ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $id) . "', ";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'233', 'msg'=>'Required argument missing', 'pmsg'=>"No {$prefix}_id"));
    }

    // user_id
    if( isset($args['user_id']) && $args['user_id'] != '' && $args['user_id'] > 0 ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', ";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'216', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
    }

    // content
    if( isset($args['content']) && $args['content'] != '' ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['content']) . "', ";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'217', 'msg'=>'Required argument missing', 'pmsg'=>'No content'));
    }

    $strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

    //
    // Add the followup
    //
    $rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'209', 'msg'=>'Unable to add followup', 'err'=>$rc['err']));
    }
    $followup_id = $rc['insert_id'];

    //
    // Add history
    //
    ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
        1, $table, $followup_id, 'uuid', $uuid);
    ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
        1, $table, $followup_id, $prefix . '_id', $id);
    ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
        1, $table, $followup_id, 'user_id', $args['user_id']);
    ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
        1, $table, $followup_id, 'content', $args['content']);

    //
    // Sync push
    //
    $ciniki['syncqueue'][] = array('push'=>$module . '.' . $object, 
        'args'=>array('id'=>$followup_id));

    // Updates to the main thread last_updated field should be done by the calling function,
    // incase there are instances where we don't want to update that field.

    return array('stat'=>'ok');
}
?>
