<?php
//
// Description
// -----------
// This function take for an SQL string, give 2 hash structures to get fields and values from.
//
// Info
// ----
// Status:          started
//
// Arguments
// ---------
// fields:          The array of field names to be used.
// record:          The hash structure to pull the information from.
// prefix:          The start of the SQL string.    "INSERT INTO table("
// middle:          The middle of the SQL string between the field names and values.
//                  eg: "date_added, last_updated) VALUES ("
// suffix:          The end of the SQL string. eg: "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
// 
function ciniki_core_dbHashToSQL(&$ciniki, $fields, $record, $prefix, $middle, $suffix) {

    //
    // Loop through the fields given, and add them if there is
    // and entry in the record with the same name
    //
    $strsql_prefix = '';
    $strsql_middle = '';
    $strsql_suffix = '';

    foreach($fields as $field) {
        if( isset($record[$field]) ) {
            $strsql_prefix .= "$field, ";
            $strsql_middle .= "'" . ciniki_core_dbQuote($ciniki, $record[$field]) . "', ";
        }
    }
    
    return array('stat'=>'ok', 'strsql'=>$strsql_prefix . $strsql_middle . $strsql_suffix);
}
?>
