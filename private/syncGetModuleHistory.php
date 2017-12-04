<?php
//
// Description
// -----------
// This function will update the history elements.
//
// Arguments
// ---------
//
function ciniki_core_syncGetModuleHistory(&$ciniki, &$sync, $tnid, $args) {

    if( !isset($args['history_table']) || $args['history_table'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.204', 'msg'=>'No history table specified'));
    }
    if( !isset($args['uuid']) || $args['uuid'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.205', 'msg'=>'No uuid specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Get the history information
    //
    $history_table = $args['history_table'];
    $strsql = "SELECT $history_table.id AS history_id, "
        . "$history_table.uuid AS history_uuid, "
        . "ciniki_users.uuid AS user_uuid, "
        . "$history_table.session, "
        . "$history_table.action, "
        . "$history_table.table_name, "
        . "$history_table.table_key, "
        . "$history_table.table_field, "
        . "$history_table.new_value, "
        . "UNIX_TIMESTAMP($history_table.log_date) AS log_date "
        . "FROM $history_table "
        . "LEFT JOIN ciniki_users ON ($history_table.user_id = ciniki_users.id) "
        . "WHERE $history_table.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND $history_table.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
        . "ORDER BY log_date "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, $args['module'], array(
        array('container'=>'history', 'fname'=>'history_uuid', 
            'fields'=>array('uuid'=>'history_uuid', 'user'=>'user_uuid', 'session', 
                'action', 'table_name', 'table_key', 'table_field', 'new_value', 'log_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.206', 'msg'=>"Unable to get " . $args['module'] . " history", 'err'=>$rc['err']));
    }
    if( !isset($rc['history'][$args['uuid']]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.207', 'msg'=>$args['module'] . " history does not exist: " . $args['uuid']));
    }
    $history = $rc['history'][$args['uuid']];

    //
    // Translate the table_key (ciniki_customer_relationships.id) into a uuid
    //
    if( isset($args['table_key_maps']) && isset($args['table_key_maps'][$history['table_name']]) ) {
        $details = $args['table_key_maps'][$history['table_name']];
        //
        // Lookup the object.  If the table_key is blank, then most likely the entire record was deleted. 
        // The blank table_key object can be propagated through all servers, and may only have a non blank
        // on the server where the object was add and deleted.
        //
        if( $history['table_key'] != '' ) {
            ciniki_core_loadMethod($ciniki, $details['package'], $details['module'], 'sync', $details['lookup']);
            $lookup = $details['package'] . '_' . $details['module'] . '_' . $details['lookup'];
            $rc = $lookup($ciniki, $sync, $tnid, array('local_id'=>$history['table_key']));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_syncLog($ciniki, 0, "Unable to locate local table key for $history_table(" . $history['table_key'] . ')', $rc['err']);
                $history['table_key'] = '';
            } else {
                $history['table_key'] = $rc['uuid'];
            }
        }
    }

    if( isset($args['new_value_maps']) && isset($args['new_value_maps'][$history['table_field']]) ) {
        $details = $args['new_value_maps'][$history['table_field']];
        //
        // Lookup the uuid mapping for field
        //
        ciniki_core_loadMethod($ciniki, $details['package'], $details['module'], 'sync', $details['lookup']);
        $lookup = $details['package'] . '_' . $details['module'] . '_' . $details['lookup'];
        $rc = $lookup($ciniki, $sync, $tnid, array('local_id'=>$history['new_value']));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_syncLog($ciniki, 0, 'Unable to locate local new_value (' . $history['table_name'] . ' - ' . $history['table_field'] . ':' . $history['new_value'] . ')', $rc['err']);
            $history['new_value'] = '';
        } else {
            $history['new_value'] = $rc['uuid'];
        }
    }
    
    return array('stat'=>'ok', 'history'=>$history);
}
?>
