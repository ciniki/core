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
function ciniki_core_syncObjectHistoryUpdate(&$ciniki, &$sync, $tnid, $o, $args) {
    //
    // Check for custom history update function
    //
    if( isset($o['history_update']) && $o['history_update'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
        return ciniki_core_syncObjectFunction($ciniki, $sync, $tnid, $o['history_update'], $args);
    }
    
    //
    // Check the args
    //
    if( (!isset($args['uuid']) || $args['uuid'] == '') 
        && (!isset($args['object']) || $args['object'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.258', 'msg'=>'No ' . $o['name'] . ' history specified'));
    }

    if( isset($args['object']) ) {
        $remote_history = $args['object'];
    } 
    elseif( isset($args['uuid']) && $args['uuid'] != '' ) {
        //
        // Get the remote object to update
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
        $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>$o['pmod'] . '.' . $o['oname'] . '.history.get', 'uuid'=>$args['uuid']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.259', 'msg'=>'Unable to get the remote ' . $o['name'] . ' history', 'err'=>$rc['err']));
        }
        if( !isset($rc['object']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.260', 'msg'=>$o['oname'] . ' not found on remote server'));
        }
        $remote_history = $rc['object'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.261', 'msg'=>'Unable to update history for ' . $o['oname']));
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryGet');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectUpdateHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, $o['pmod']);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Translate the remote object, but only if it looks like an actual object
    //
    if( (!isset($o['type']) || $o['type'] != 'settings')
        && $remote_history['table_key'] != '' 
        && strncmp($remote_history['table_key'], 'uuid-', 5) != 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
        $rc = ciniki_core_syncObjectLookup($ciniki, $sync, $tnid, $o, array('remote_uuid'=>$remote_history['table_key']));
        if( $rc['stat'] != 'ok' ) {
            //
            // If the object does not exist local in the history or on remote as an object, then it must have been deleted,
            // and the table_key should be uuid- + the uuid.
            //
            $remote_history['table_key'] = 'uuid-' . $remote_history['table_key'];
    //      return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.262', 'msg'=>$o['pmod'] . " history table key does not exist: " . $remote_history['table_key']));
        } else {
            $remote_history['table_key'] = $rc['id'];
        }
    }

    //
    // Translations of the new_value are taken care of in the syncObjectUpdateHistory function
    // Translate the new_value into a uuid if required
    //
//  if( isset($o['fields'][$remote_history['table_field']]) && isset($o['fields'][$remote_history['table_field']]['ref']) 
//      && $remote_history['new_value'] != '' && strncmp($remote_history['new_value'], 'uuid-', 5) != 0 && $remote_history['new_value'] != '0' ) {
//      $ref = $o['fields'][$remote_history['table_field']]['ref'];
//      ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
//      $rc = ciniki_core_syncObjectLoad($ciniki, $sync, $tnid, $ref, array());
//      if( $rc['stat'] != 'ok' ) {
//          return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.263', 'msg'=>'Unable to load object ' . $ref));
//      }
//      $ref_o = $rc['object'];
//      ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
//      $rc = ciniki_core_syncObjectLookup($ciniki, $sync, $tnid, $ref_o, 
//          array('remote_uuid'=>$remote_history['new_value']));
//      if( $rc['stat'] != 'ok' ) {
//          //
//          // May be a deleted item, add as uuid
//          //
//          return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.264', 'msg'=>'Unable to find reference for ' . $ref_o['name'] . '(' . $remote_history['new_value'] . ')', 'err'=>$rc['err']));
//      }
//      $remote_history['new_value'] = $rc['id'];
//  }

    //
    // Get the local history
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectHistoryGet');
    $rc = ciniki_core_syncObjectHistoryGet($ciniki, $sync, $tnid, $o, array('uuid'=>$remote_history['uuid'], 'translate'=>'no'));
    if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.265', 'msg'=>'Unable to get ' . $o['name'] . ' history', 'err'=>$rc['err']));
    }
    if( isset($rc['object']) ) {
        $local_history = $rc['object'];
        $rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $tnid, $o, $local_history['table_key'], 
            array($remote_history['uuid']=>$remote_history), array($local_history['uuid']=>$local_history));
    } else {
        // Remote table key should be a uuid
        $rc = ciniki_core_syncObjectUpdateHistory($ciniki, $sync, $tnid, $o, $remote_history['table_key'], 
            array($remote_history['uuid']=>$remote_history), array());
    }

    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, $o['pmod']);
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.266', 'msg'=>'Unable to save history for ' . $o['name'], 'err'=>$rc['err']));
    }
    
    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, $o['pmod']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Add to syncQueue to sync with other servers.  This allows for cascading syncs.
    //
    // FIXME: Add code to push history changes
//  if( $db_updated > 0 ) {
//      $ciniki['syncqueue'][] = array('push'=>$o['pmod'] . '.' . $o['oname'], 
//          'args'=>array('id'=>$object_id, 'ignore_sync_id'=>$sync['id']));
//  }

    return array('stat'=>'ok');
}
?>
