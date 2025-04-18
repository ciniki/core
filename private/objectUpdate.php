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
//              0x08 - Insert temporary history to be removed later
//
// Returns
// -------
//
function ciniki_core_objectUpdate(&$ciniki, $tnid, $obj_name, $oid, $args, $tmsupdate=0x07, $history_notes='') {
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
    // Check for serialized fields
    //
    $fields = '';
    foreach($o['fields'] as $field => $options) {
        if( isset($options['sfields']) ) {
            $fields .= ($fields != '' ? ', ' : '') . $field;
        }
    }
    if( $fields != '' ) {
        $strsql = "SELECT $fields "
            . "FROM " . $o['table'] . " "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.405', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.406', 'msg'=>'Unable to find requested item'));
        }
        $item = $rc['item'];
    }

    //
    // Build the SQL string to update object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistoryNotes');
    $strsql = "UPDATE " . $o['table'] . " SET last_updated = UTC_TIMESTAMP()";
    $num_fields = 0;
    foreach($o['fields'] as $field => $options) {
        if( isset($args[$field]) ) {
            $num_fields++;
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
        }
        elseif( isset($options['sfields']) ) {  
            $s_values = array();
            if( isset($item[$field]) && $item[$field] != '' ) {
                $s_values = json_decode($item[$field], true);
            }
            foreach($options['sfields'] as $s_field => $s_options) {
                if( isset($args[$s_field]) && (!isset($s_values[$s_field]) || $s_values[$s_field] != $args[$s_field]) ) {
                    $s_values[$s_field] = $args[$s_field];
                }
            }
            $serialized_values = json_encode($s_values);
            if( !isset($item[$field]) || $serialized_values != $item[$field] ) {
                $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $serialized_values) . "' ";
            }
        }
    }
    $strsql .= " WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
        . "";

    //
    // Nothing to update, ignore
    //
    if( $num_fields == 0 ) {
        return array('stat'=>'ok');
    }

    //
    // Update the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, $m);
    if( $rc['stat'] != 'ok' ) { 
        if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || ($rc['num_affected_rows'] != 1 && $rc['num_affected_rows'] != 0) ) {
        if( ($tmsupdate&0x01) == 1 ) { 
            ciniki_core_dbTransactionRollback($ciniki, $m); 
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.120', 'msg'=>'Unable to update object'));
    }

    //
    // Setup the action
    //
    $action = 2;
    if( ($tmsupdate&0x08) == 0x08 ) {
        $action = 8;
    }

    //
    // Add the history
    //
    if( isset($o['history_table']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefAdd');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefClear');
        foreach($o['fields'] as $field => $options) {
            if( isset($args[$field]) && (!isset($options['history']) || $options['history'] == 'yes') ) {
                //
                // Delete old autosave so we don't fill history up with auto save rows, just keep last one.
                //
                if( ($tmsupdate&0x08) == 0x08 ) {
                    $strsql = "DELETE FROM " . $o['history_table'] . " "
                        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . "AND table_name = '" . ciniki_core_dbQuote($ciniki, $o['table']) . "' "
                        . "AND table_key = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
                        . "AND table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
                        . "AND action = 8 "
                        . "";
                    $rc = ciniki_core_dbUpdate($ciniki, $strsql, $m);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.404', 'msg'=>'Unable to remove auto save history', 'err'=>$rc['err']));
                    }
                }
                if( isset($o['history_notes']) && $o['history_notes'] == 'yes' ) {
                    ciniki_core_dbAddModuleHistoryNotes($ciniki, $m, $o['history_table'], $tnid, $action, $o['table'], $oid, $field, $args[$field], $history_notes);
                } else {
                    ciniki_core_dbAddModuleHistory($ciniki, $m, $o['history_table'], $tnid, $action, $o['table'], $oid, $field, $args[$field]);
                }
            }

            //
            // Check if this column is a reference to another modules object, 
            // and see if there should be a reference updated
            //
            if( isset($options['ref']) && $options['ref'] != '' && isset($args[$field]) ) {
                //
                // Clear any old refs
                //
                $rc = ciniki_core_objectRefClear($ciniki, $tnid, $options['ref'], array(
                    'object'=>$obj_name,            // The local object ref (this objects ref)
                    'object_id'=>$oid,              // The local object ID
                    ));
                if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
                    return $rc;
                }
                //
                // Add the new ref 
                //
                if( $args[$field] != '' && $args[$field] > 0 ) {
                    $rc = ciniki_core_objectRefAdd($ciniki, $tnid, $options['ref'], array(
                        'ref_id'=>$args[$field],        // The remote ID (other modules object id)
                        'object'=>$obj_name,            // The local object ref (this objects ref)
                        'object_id'=>$oid,      // The local object ID
                        'object_field'=>$field,         // The local object table field name of remote ID
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.121', 'msg'=>'Unable to add reference to ' . $options['ref'], 'err'=>$rc['err']));
                    }
                }
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
        $ciniki['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('id'=>$oid));
    }   

    return array('stat'=>'ok');
}
?>
