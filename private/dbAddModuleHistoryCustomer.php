<?php
//
// Description
// -----------
// This function will add the change log entry for an object, checking to see 
// if the change was made via the website
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
// tnid:
// table_name:      The table name that the data was inserted/replaced in.
// table_key:       The key to be able to get back to the row that was 
//                  changed in the table_name.
// table_field:     The field in the table_name that was updated.
// value:           The new value for the field.
//
function ciniki_core_dbAddModuleHistoryCustomer(&$ciniki, $module, $history_table, $tnid, $action, $table_name, $table_key, $table_field, $value) {

    $customer_id = 0;
    if( isset($ciniki['session']['change_log_customer_id']) ) {
        $customer_id = $ciniki['session']['change_log_customer_id'];
    }

    $strsql = "INSERT INTO " . ciniki_core_dbQuote($ciniki, $history_table) . " "
        . "(uuid, tnid, user_id, customer_id, session, action, "
        . "table_name, table_key, table_field, new_value, log_date) VALUES ("
        . "uuid(), "
        . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $action) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $table_name) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $table_key) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $table_field) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $value) . "', "
        . "UTC_TIMESTAMP() "
        . ")";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    return ciniki_core_dbInsert($ciniki, $strsql, $module);
}
?>
