<?php
//
// Description
// -----------
//
// Info
// ----
// Status: (defined|started|untested|alpha|beta|production)
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_core_logSecurity($ciniki, $strsql, $error_code, $method, $table, $data_id) {
    
    error_log("SECURITY: $error_code - $method - $table - $data_id");
    error_log("SQLINFO: $strsql");
}
?>
