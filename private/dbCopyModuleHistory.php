<?php
//
// Description
// -----------
// This function copies the history for a module element from one element to another.  This
// is used when two elements are combined into one.
//
// Arguments
// ---------
// ciniki:
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
// history_table:   The table name where the history for the module is kept.
// tnid:     The ID of the tenant copy the history for.
// table_name:      The table the history is for.
// old_table_key:
// new_table_key:
// table_field:
//
//
function ciniki_core_dbCopyModuleHistory(&$ciniki, $module, $history_table, $tnid, $table_name, $old_table_key, $new_table_key, $table_field) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = ciniki_core_dbConnect($ciniki, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    //
    // Get the history log from ciniki_core_change_logs table.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    $strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $history_table) . " "
        . "(uuid, tnid, user_id, session, action, table_name, table_key, table_field, new_value, log_date) "
        . "SELECT UUID(), tnid, user_id, session, action, table_name, "
        . "'" . ciniki_core_dbQuote($ciniki, $new_table_key) . "'"
        . ", table_field, new_value, log_date "
        . "FROM " . ciniki_core_dbQuote($ciniki, $history_table) . " "
        . " WHERE tnid ='" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . " AND table_name = '" . ciniki_core_dbQuote($ciniki, $table_name) . "' "
        . " AND table_key = '" . ciniki_core_dbQuote($ciniki, $old_table_key) . "' "
        . " AND table_field = '" . ciniki_core_dbQuote($ciniki, $table_field) . "' "
        . "";
    $result = mysqli_query($dh, $strsql);
    if( $result == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.35', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
    }
    
    return array('stat'=>'ok');
}
?>
