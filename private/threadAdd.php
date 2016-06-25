<?php
//
// Description
// -----------
// This function will take options from the 
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// ciniki:
// module:              The package.module the thread is located in.
// table:               The database table that stores the thread information.
// args:                Additional arguments provided function.
//
//                      business_id - The business to attach the thread to.
//                      state - The opening state of the thread.
//                      subject - The subject for the thread.
//                      source - The source of the thread.
//                      source_link - The link back to the source object.
// 
// Returns
// -------
//
function ciniki_core_threadAdd(&$ciniki, $module, $object, $table, $history_table, $args) {
    //
    // All arguments are assumed to be un-escaped, and will be passed through dbQuote to
    // ensure they are safe to insert.
    //

    // Required functions
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    //
    // Don't worry about autocommit here, it's taken care of in the calling function
    //

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
    $strsql = "INSERT INTO $table (uuid, business_id, user_id, subject, state, "
        . "source, source_link, options, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
        . "";

    // business_id
    if( isset($args['business_id']) && $args['business_id'] != '' && $args['business_id'] > 0 ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', ";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'201', 'msg'=>'Required argument missing', 'pmsg'=>'No business_id'));
    }

    // user_id
    if( isset($args['user_id']) && $args['user_id'] != '' && $args['user_id'] > 0 ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', ";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'214', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
    }

    // subject
    if( isset($args['subject']) && $args['subject'] != '' ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'210', 'msg'=>'Required argument missing', 'pmsg'=>'No subject'));
    }

    // state - optional
    if( isset($args['state']) && $args['state'] != '' ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['state']) . "', ";
    } else {
        $strsql .= "'', ";
    }

    // source - optional
    if( isset($args['source']) && $args['source'] != '' ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['source']) . "', ";
    } else {
        $strsql .= "'', ";
    }

    // source_link - optional
    if( isset($args['source_link']) && $args['source_link'] != '' ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['source_link']) . "', ";
    } else {
        $strsql .= "'', ";
    }

    // options - optional
    if( isset($args['options']) && $args['options'] != '' ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['options']) . "', ";
    } else {
        $strsql .= "'0', ";
    }

    $strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

    $rc = ciniki_core_dbInsert($ciniki, $strsql, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $thread_id = $rc['insert_id'];

    ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
        1, $table, $thread_id, 'uuid', $uuid);
    if( isset($args['user_id']) && $args['user_id'] != '' && $args['user_id'] > 0 ) {
        ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
            1, $table, $thread_id, 'user_id', $args['user_id']);
    }
    if( isset($args['subject']) && $args['subject'] != '' ) {
        ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
            1, $table, $thread_id, 'subject', $args['subject']);
    }
    if( isset($args['state']) && $args['state'] != '' ) {
        ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
            1, $table, $thread_id, 'state', $args['state']);
    }
    if( isset($args['source']) && $args['source'] != '' ) {
        ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
            1, $table, $thread_id, 'source', $args['source']);
    }
    if( isset($args['source_link']) && $args['source_link'] != '' ) {
        ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
            1, $table, $thread_id, 'source_link', $args['source_link']);
    }
    if( isset($args['options']) && $args['options'] != '' ) {
        ciniki_core_dbAddModuleHistory($ciniki, $module, $history_table, $business_id,
            1, $table, $thread_id, 'options', $args['options']);
    }
    //
    // Sync push
    //
    $ciniki['syncqueue'][] = array('push'=>$module . '.' . $object, 
        'args'=>array('id'=>$thread_id));

    return $rc;
}
?>
