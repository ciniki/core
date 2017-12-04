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
function ciniki_core_syncObjectList($ciniki, &$sync, $tnid, $o, $args) {
    //
    // Check for custom list function
    //
    if( isset($o['list']) && $o['list'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncObjectFunction');
        return ciniki_core_syncObjectFunction($ciniki, $sync, $tnid, $o['list'], $args);
    }

    //
    // Check the args
    //
    if( !isset($args['type']) ||
        ($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.267', 'msg'=>'No type specified'));
    }
    if( $args['type'] == 'incremental' 
        && (!isset($args['since_uts']) || $args['since_uts'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.268', 'msg'=>'No timestamp specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');

    //
    // Prepare the query to fetch the list
    //
    $table = $o['table'];
    $table_key = 'uuid';
    if( isset($o['type']) && $o['type'] == 'settings' ) {
        $table_key = 'detail_key';
    }
    $strsql = "SELECT $table_key, UNIX_TIMESTAMP(last_updated) AS last_updated "    
        . "FROM $table "
        . "WHERE $table.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( $args['type'] == 'incremental' ) {
        $strsql .= "AND UNIX_TIMESTAMP($table.last_updated) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
    }
    $strsql .= "ORDER BY last_updated "
        . "";
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, $o['pmod'], 'objects', $table_key);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.269', 'msg'=>'Unable to get list', 'err'=>$rc['err']));
    }

    if( !isset($rc['objects']) ) {
        return array('stat'=>'ok', 'list'=>array());
    }
    $list = $rc['objects'];

    //
    // For type settings, there are no deleted objects
    //
    if( isset($o['type']) && $o['type'] == 'settings' ) {
        return array('stat'=>'ok', 'list'=>$list, 'deleted'=>array());
    }

    //
    // Get any deleted objects
    //
    $deleted = array();
    $history_table = $o['history_table'];
    $strsql = "SELECT h1.id AS history_id, "
        . "h1.uuid AS history_uuid, "
        . "ciniki_users.uuid AS user_uuid, "
        . "h1.session, "
        . "h1.action, "
        . "h1.table_field, "
        . "h1.table_key, "
        . "h1.new_value, "
        . "UNIX_TIMESTAMP(h1.log_date) AS log_date, h2.new_value AS uuid "
        . "FROM $history_table AS h1 "
        . "LEFT JOIN $history_table AS h2 ON (h1.table_key = h2.table_key "
            . "AND h2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND h2.table_field = 'uuid') "
        . "LEFT JOIN ciniki_users ON (h1.user_id = ciniki_users.id) "
        . "WHERE h1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND h1.table_name = '$table' "
        . "AND h1.table_key IN (SELECT DISTINCT table_key FROM $history_table "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND action = 3 "
            . "AND table_name = '$table' "
            . "AND table_field = '*' ";
    if( $args['type'] == 'incremental' ) {
        $strsql .= "AND UNIX_TIMESTAMP(log_date) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
    }
    $strsql .= ") "
        . "ORDER BY h1.table_key, h1.log_date DESC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $o['pmod'], 'history');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.270', 'msg'=>'Unable to find deleted ' . $o['name']));
    }
    $prev_key = 0;
    foreach($rc['rows'] as $rid => $row) {
        // Check for delete as the most recent history item
        if( $prev_key != $row['table_key'] && $row['action'] == 3 ) {
            $deleted[$row['uuid']] = array(
                'id'=>$row['history_id'],
                'uuid'=>$row['history_uuid'],
                'user'=>$row['user_uuid'],
                'session'=>$row['session'],
                'action'=>$row['action'],
                'table_field'=>$row['table_field'],
                'new_value'=>$row['new_value'],
                'log_date'=>$row['log_date']);
        }
        $prev_key = $row['table_key'];
    }

    return array('stat'=>'ok', 'list'=>$list, 'deleted'=>$deleted);
}
?>
