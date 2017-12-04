<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
//
//
function ciniki_core_dbGetModuleHistoryList(&$ciniki, $module, $history_table, $tnid, $table_name, $table_key, $table_field, $table_id_field) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //

    // *********************************
    // FIXME:  THIS FUNCTION IS NOT FINISHED!!!!!!!!!!!!
    // *********************************
    return array('stat'=>'ok', 'history'=>array());






    //
    // Get the history log from ciniki_core_change_logs table.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

    //
    // Get the list of table_keys we're interested in
    // This query is broken into three stages for speed
    //
    $strsql = "SELECT DISTINCT table_key "
        . "FROM $history_table "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_id_field) . "' "
        . "AND new_value = '" . ciniki_core_dbQuote($ciniki, $table_key) . "' "
        . "";
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, $module, 'keys', 'table_key');  
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['keys']) || count($rc['keys']) == 0 ) {
        return array('stat'=>'ok', 'history'=>array());
    }
    $keys = $rc['keys'];

    //
    // Finally get the history
    //
    $date_format = ciniki_users_datetimeFormat($ciniki);
    $strsql = "SELECT user_id, DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as date, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
        . "action, "
        . "table_key, "
        . "table_field, "
        . "ciniki_users.display_name AS user_display_name, "
        . "new_value as value "
        . "FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
        . "LEFT JOIN ciniki_users ON ($history_table.user_id = ciniki_users.id) "
        . "WHERE tnid ='" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
        . "AND (table_field = '" . ciniki_core_dbQuote($ciniki, $table_field) . "' OR table_field = '*') "
        . "AND table_key IN (" . ciniki_core_dbQuoteList($ciniki, $keys) . ") "
        . "ORDER BY $history_table.log_date ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, $module, 'row');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Build the history based on additions (action:1) and deletions(action:3)
    //
    $subscriptions = array();
    $history = array();
    foreach($rc['rows'] as $row) {
        if( $row['table_field'] == $table_field ) {
            $subscriptions[$row['table_key']] = $row['value'];
            $history[] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 
                'action'=>'1', 'value'=>$row['value'], 
                'age'=>ciniki_core_dbParseAge($ciniki, $row['age']), 
                'user_display_name'=>$row['user_display_name']));
        } elseif( $row['table_field'] == '*' && isset($subscriptions[$row['table_key']]) ) {
            $history[] = array('action'=>array('user_id'=>$row['user_id'], 'date'=>$row['date'], 
                'action'=>'3', 'value'=>$subscriptions[$row['table_key']], 
                'age'=>ciniki_core_dbParseAge($ciniki, $row['age']), 
                'user_display_name'=>$row['user_display_name']));
        }
    }

    return array('stat'=>'ok', 'history'=>array_reverse($history));
}
?>
