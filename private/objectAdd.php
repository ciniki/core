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
function ciniki_core_objectAdd(&$ciniki, $tnid, $obj_name, $args, $tmsupdate=0x07) {
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
    // Check if UUID was passed
    //
    if( !isset($args['uuid']) || $args['uuid'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, $m);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.99', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $args['uuid'] = $rc['uuid'];
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
    // Build the SQL string to insert object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "INSERT INTO " . $o['table'] . " (uuid, tnid, ";
    $values = "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', ";
    foreach($o['fields'] as $field => $options) {
        $strsql .= $field . ', ';
        if( isset($args[$field]) ) {
            $values .= "'" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', ";
        } elseif( isset($options['sfields']) ) {
            $s_values = array();
            foreach($options['sfields'] as $s_field => $s_options) {
                if( isset($args[$s_field]) ) {
                    $s_values[$s_field] = $args[$s_field];
                } elseif( isset($s_options['default']) ) {
                    $s_values[$s_field] = $s_options['default'];
                }
            }
            $values .= "'" . serialize($s_values) . "', ";
        } elseif( isset($options['default']) ) {
            $args[$field] = $options['default'];
            $values .= "'" . ciniki_core_dbQuote($ciniki, $options['default']) . "', ";
        } else {
            if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.100', 'msg'=>'Missing object field: ' . $field));
        }
    }
    $strsql .= "date_added, last_updated) VALUES (" . $values . " UTC_TIMESTAMP(), UTC_TIMESTAMP())";

    //
    // Insert the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    $rc = ciniki_core_dbInsert($ciniki, $strsql, $m);
    if( $rc['stat'] != 'ok' ) { 
        if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        if( ($tmsupdate&0x01) == 1 ) { ciniki_core_dbTransactionRollback($ciniki, $m); }
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.101', 'msg'=>'Unable to add object'));
    }
    $insert_id = $rc['insert_id'];

    //
    // Add the history and check for foreign module ref table
    //
    if( isset($o['history_table']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefAdd');
        ciniki_core_dbAddModuleHistory($ciniki, $m, $o['history_table'], $tnid,
            1, $o['table'], $insert_id, 'uuid', $args['uuid']);
        foreach($o['fields'] as $field => $options) {
            //
            // Some field we don't store the history for, like binary content of files
            //
            if( !isset($options['history']) || $options['history'] == 'yes' ) {
                ciniki_core_dbAddModuleHistory($ciniki, $m, $o['history_table'], $tnid,
                    1, $o['table'], $insert_id, $field, $args[$field]);
            }
            //
            // Check if this column is a reference to another modules object, 
            // and see if there should be a reference added
            //
            if( isset($options['ref']) && $options['ref'] != '' && $args[$field] != '' && $args[$field] > 0 ) {
                $rc = ciniki_core_objectRefAdd($ciniki, $tnid, $options['ref'], array(
                    'ref_id'=>$args[$field],        // The remote ID (other modules object id)
                    'object'=>$obj_name,            // The local object ref (this objects ref)
                    'object_id'=>$insert_id,        // The local object ID
                    'object_field'=>$field,         // The local object table field name of remote ID
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
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
        $ciniki['syncqueue'][] = array('push'=>$obj_name, 'args'=>array('id'=>$insert_id));
    }   

    return array('stat'=>'ok', 'id'=>$insert_id, 'uuid'=>$args['uuid']);
}
?>
