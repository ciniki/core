<?php
//
// Description
// -----------
// This function will add a change log entry for a changed field. This will
// be entered in the ciniki_core_change_logs table.
//
// Info
// ----
// status:          beta
//
// Arguments
// ---------
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
// user_id:         The user making the request
// business_id:
// table_name:      The table name that the data was inserted/replaced in.
// table_key:       The key to be able to get back to the row that was 
//                  changed in the table_name.
// table_field:     The field in the table_name that was updated.
// value:           The new value for the field.
//
function ciniki_core_dbAddModuleHistory(&$ciniki, $module, $history_table, $business_id, $action, $table_name, $table_key, $table_field, $value) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
//  $rc = ciniki_core_dbConnect($ciniki, $module);
//  if( $rc['stat'] != 'ok' ) {
//      return $rc;
//  }
//
//  $dh = $rc['dh'];

    $strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $history_table) . " "
        . "(uuid, business_id, user_id, session, action, "
        . "table_name, table_key, table_field, new_value, log_date) VALUES ("
        . "uuid(), "
        . "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $action) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $table_name) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $table_key) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $table_field) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $value) . "', "
        . "UTC_TIMESTAMP()"
        . ")";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    return ciniki_core_dbInsert($ciniki, $strsql, $module);
}
?>
