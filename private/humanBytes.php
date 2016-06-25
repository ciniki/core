<?php
//
// Description
// -----------
// This function will return the preferred data format for a user
// if they are logged in, otherwise the default date format.
//
// Info
// ----
// Status:          beta
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_core_humanBytes($ciniki, $value, $e) {
    if( $value == null ) { return ''; }
    $exts = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    if( $value > 1024 ) {
        return ciniki_core_humanBytes($ciniki, $value/1024, $e+1);
    }
    
    return sprintf("%0.1f %s", $value, $exts[$e]);
}
?>
