<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_core_syncObjectDelete(&$ciniki, &$sync, $business_id, $o, $args) {
    //
    // Check for custom delete function
    //
    if( isset($o['delete']) && $o['delete'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
        return ciniki_core_syncObjectFunction($ciniki, $sync, $business_id, $o['delete'], $args);
    }

    //
    // Check the args
    //
    if( !isset($args['uuid']) || $args['uuid'] == '' 
        || !isset($args['history']) || $args['history'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.224', 'msg'=>'No ' . $o['name'] . ' specified'));
    }
    $uuid = $args['uuid'];
    $remote_history = $args['history'];

    ciniki_core_syncLog($ciniki, 3, 'Removing ' . $o['name'] . '(' . serialize($args) . ')', null);

    if( isset($args['uuid']) && $args['uuid'] != '' ) {
        //
        // Get the local object to delete
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectGet');
        $rc = ciniki_core_syncObjectGet($ciniki, $sync, $business_id, $o, array('uuid'=>$args['uuid'], 'translate'=>'no'));
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.225', 'msg'=>'Unable to get ' . $o['name'], 'err'=>$rc['err']));
        }
        if( !isset($rc['object']) ) {
            //
            // Make sure the history is update to date
            //


            // Already deleted
            return array('stat'=>'ok');
        }
        $local_object = $rc['object'];
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectUpdateHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, $o['pmod']);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $db_updated = 0;

    //
    // Remove from the local server
    //
    $table = $o['table'];
    $strsql = "DELETE FROM $table "
        . "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, $o['pmod']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.226', 'msg'=>'Unable to delete the local ' . $o['name'], 'err'=>$rc['err']));
    }
    if( $rc['num_affected_rows'] > 0 ) {
        $db_updated = 1;
    }

    //
    // Update history
    //
    if( isset($local_object['history']) ) {
        $rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $local_object['id'], 
            array($remote_history['uuid']=>$remote_history), array());
    } else {
        $rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $business_id, $o, $local_object['id'], 
            array($remote_history['uuid']=>$remote_history), $local_object['history']);
    }
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.227', 'msg'=>'Unable to update ' . $o['name'] . ' history', 'err'=>$rc['err']));
    }

    //
    // Check if the details also need to be removed
    //
    if( isset($local_object) && isset($o['details']) 
        && isset($o['details']['key']) && isset($o['details']['table']) ) {
        $key = $o['details']['key'];
        $table = $o['details']['table'];
        $strsql = "DELETE FROM $table "
            . "WHERE $key = '" . ciniki_core_dbQuote($ciniki, $local_object['id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, $o['pmod']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.228', 'msg'=>'Unable to delete ' . $o['name'] . ' details', 'err'=>$rc['err']));
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, $o['pmod']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Add to syncQueue to sync with other servers.  This allows for cascading syncs.  Don't need to
    // include the delete_id because the history is already specified.
    //
    if( $db_updated > 0 ) {
        $ciniki['syncqueue'][] = array('push'=>$o['pmod'] . '.' . $o['oname'], 
            'args'=>array('delete_uuid'=>$args['uuid'], 'history'=>$args['history'], 'ignore_sync_id'=>$sync['id']));
    }

    return array('stat'=>'ok');
}
?>
