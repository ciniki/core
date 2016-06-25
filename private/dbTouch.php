<?php
//
// Description
// -----------
// This function will update the last_updated field for a element in 
// a table.  This is used when updating related information and the
// main record needs to be updated for sync purposes.
//
// Arguments
// ---------
// ciniki: 
// module:      The name of the module.
// table:       The name of the database table to update.
// id:          The field name that contains the ID value.
// idvalue:     The value of the ID to match and update.
//
function ciniki_core_dbTouch(&$ciniki, $module, $table, $id, $idvalue) {
    //
    // Build the SQL string.  The table and id should be hard coded
    // into the calling function.  The id value may be passed from 
    // an argument.
    //
    $strsql = "UPDATE $table SET last_updated = UTC_TIMESTAMP() "
        . "WHERE $id = '" . ciniki_core_dbQuote($ciniki, $idvalue) . "'"
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    return ciniki_core_dbUpdate($ciniki, $strsql, $module);
}
?>
