<?php
//
// Description
// -----------
// This function will create a comma delimited list of integers
// for use in WHERE variable IN (list) sql statements.  This ensures
// that everything is safe and properly escaped.
//
// Info
// ----
// Status:          beta
//
// Arguments
// ---------
// ciniki:
// arr:         The array of ID's which need to be escaped.
//
function ciniki_core_dbQuoteIDs(&$ciniki, $arr) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbConnect');
    $rc = ciniki_core_dbConnect($ciniki, 'ciniki.core');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $str = '';
    $comma = '';
    foreach($arr as $i) {
        if( is_int($i) ) {
            $str .= $comma . mysqli_real_escape_string($rc['dh'], $i);
            $comma = ',';
        } else if( is_numeric($i) ) {
            $str .= $comma . mysqli_real_escape_string($rc['dh'], intval($i));
            $comma = ',';
        }
    }

    return $str;
}
?>
