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
function ciniki_core_syncSettingGet($ciniki, $sync, $business_id, $o, $args) {
    //
    // Check the args
    //
    if( (!isset($args['uuid']) || $args['uuid'] == '' )
        && (!isset($args['id']) || $args['id'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'153', 'msg'=>'No setting specified'));
    }

    if( !isset($args['id']) && isset($args['uuid']) ) {
        $args['id'] = $args['uuid'];
    }

    //
    // Prepare the query to fetch the list
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Get the setting information
    //
    $table = $o['table'];
    $history_table = $o['history_table'];
    $strsql = "SELECT $table.detail_key, "
        . "$table.detail_value, "
        . "UNIX_TIMESTAMP($table.date_added) AS date_added, "
        . "UNIX_TIMESTAMP($table.last_updated) AS last_updated, "
        . "$history_table.id AS history_id, "
        . "$history_table.uuid AS history_uuid, "
        . "ciniki_users.uuid AS user_uuid, "
        . "$history_table.session, "
        . "$history_table.action, "
        . "$history_table.table_field, "
        . "$history_table.new_value, "
        . "UNIX_TIMESTAMP($history_table.log_date) AS log_date "
        . "FROM $table "
        . "LEFT JOIN $history_table ON ($table.detail_key = $history_table.table_key "
            . "AND $history_table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND $history_table.table_name = '$table' "
            . ") "
        . "LEFT JOIN ciniki_users ON ($history_table.user_id = ciniki_users.id) "
        . "WHERE $table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND $table.detail_key = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
        . "ORDER BY log_date "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'settings', 'fname'=>'detail_key', 
            'fields'=>array('detail_key', 'detail_value', 'date_added', 'last_updated')),
        array('container'=>'history', 'fname'=>'history_uuid', 
            'fields'=>array('user'=>'user_uuid', 'session', 
                'action', 'table_field', 'new_value', 'log_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'975', 'msg'=>'Unable to get customer setting', 'err'=>$rc['err']));
    }
    if( !isset($rc['settings'][$args['id']]) ) {
        return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'152', 'msg'=>'Setting does not exist'));
    }
    $object = $rc['settings'][$args['id']];

    //
    // FIXME: Add translate for new_value if required
    //
    //
    // Translate any references for settings
    //
    if( !isset($args['translate']) || $args['translate'] == 'yes' ) {
        if( isset($o['refs']) && isset($o['refs'][$object['detail_key']]) 
            && isset($o['refs'][$object['detail_key']]['ref']) && $object['detail_value'] != 0 ) {
            $ref = $o['refs'][$object['detail_key']]['ref'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
            $rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $ref, array());
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1161', 'msg'=>'Unable to load object ' . $ref));
            }
            $ref_o = $rc['object'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
            $rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
                array('local_id'=>$object['detail_value']));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1160', 'msg'=>'Unable to find reference for ' . $ref_o['name'] . '(' . $object['detail_value'] . ')'));
            }
            $object['detail_value'] = $rc['uuid'];

            // FIXME: Add history translate
            if( isset($object['history']) ) {
                foreach($object['history'] as $uuid => $history) {
                    if( $history['table_field'] == 'detail_value' && $history['new_value'] != '0' ) {
                        $ref = $o['refs'][$object['detail_key']]['ref'];
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLoad');
                        $rc = ciniki_core_syncObjectLoad($ciniki, $sync, $business_id, $ref, array());
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1201', 'msg'=>"Unable to load object $ref"));
                        }
                        $ref_o = $rc['object'];
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectLookup');
                        $rc = ciniki_core_syncObjectLookup($ciniki, $sync, $business_id, $ref_o, 
                            array('local_id'=>$history['new_value']));
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1163', 'msg'=>'Unable to find reference for ' . $ref_o['name'] . '(' . $history['new_value'] . ')', 'err'=>$rc['err']));
                        }
                        $object['history'][$uuid]['new_value'] = $rc['uuid'];
                    }
                }
            } else {
                ciniki_core_syncLog($ciniki, 0, 'No history for ' . $o['pmod'] . '.' . $o['oname'] . '(' . $object['detail_value'] . ')', null);                
            }
        }
    }

    return array('stat'=>'ok', 'object'=>$object);
}
?>
