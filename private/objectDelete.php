<?php
//
// Description
// -----------
// This function will add an object to the database.
//
// Arguments
// ---------
// ciniki:
// pkg:         The package the object is a part of.
// mod:         The module the object is a part of.
// obj:         The name of the object in the module.
// args:        The arguments passed to the API.
// tmsupdate:   The default is yes, and it will create a transaction,
//              update the module last_change date, and insert
//              into the sync queue.
//              
//              0x01 - run in a transaction
//              0x02 - update the module last change date
//              0x04 - Insert into sync queue
//
// Returns
// -------
//
function ciniki_core_objectDelete(&$ciniki, $tnid, $obj_name, $oid, $ouuid, $tmsupdate=0x07) {
    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Load the object file
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
    $rc = ciniki_core_objectLoad($ciniki, $obj_name);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    //
    // If the object uuid is not specified, lookup in the table first
    //
    if( $ouuid == NULL ) {
        $strsql = "SELECT uuid "
            . "FROM " . $o['table'] . " "
            . " WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $m, 'object');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        // Object does not exist
        if( $rc['stat'] == 'ok' && $rc['num_rows'] == 0 ) {
            return array('stat'=>'noexist');
        }
        if( !isset($rc['object']) || !isset($rc['object']['uuid']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.104', 'msg'=>'Unable to lookup UUID for ' . $obj_name));
        }
        $ouuid = $rc['object']['uuid'];
    }

    //
    // Start transaction
    //
    if( ($tmsupdate&0x01) == 1 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
        $rc = ciniki_core_dbTransactionStart($ciniki, $m);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Build the SQL string to update object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefClear');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "DELETE FROM " . $o['table'] . " "
        . " WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
        . "";

    //
    // Delete the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    $rc = ciniki_core_dbDelete($ciniki, $strsql, $m);
    if( $rc['stat'] != 'ok' ) { 
        if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.105', 'msg'=>'Unable to delete object'));
    }

    if( isset($o['history_table']) && $o['history_table'] != '' ) {
        ciniki_core_dbAddModuleHistory($ciniki, $m, $o['history_table'], $tnid,
            3, $o['table'], $oid, '*', ''); 
    }

    //
    // Check if any fields are references to other modules
    //
    foreach($o['fields'] as $field => $options) {
        //
        // Check if this column is a reference to another modules object, 
        // and see if there should be a reference added
        //
        if( isset($options['ref']) && $options['ref'] != '' ) {
            $rc = ciniki_core_objectRefClear($ciniki, $tnid, $options['ref'], array(
                'object'=>$obj_name,            // The local object ref (this objects ref)
                'object_id'=>$oid,      // The local object ID
                ));
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
                return $rc;
            }
        }
    }

    //
    // Commit the transaction
    //
    if( ($tmsupdate&0x01) == 1 ) {
        $rc = ciniki_core_dbTransactionCommit($ciniki, $m);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the last change date of the module
    //
    if( ($tmsupdate&0x02) == 2 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, $pkg, $mod);
    }

    //
    // Add the change to the sync queue
    //
    if( ($tmsupdate&0x04) == 4 ) {
        $ciniki['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('delete_uuid'=>$ouuid, 'delete_id'=>$oid));
    }

    return array('stat'=>'ok');
}
?>
