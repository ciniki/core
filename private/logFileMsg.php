<?php
//
// Description
// -----------
// Log a message to a specific file in the logs folder, if specified.
// Automatically adds year-month-day to end of file name so no combining or rolling required.
//
// Note: In the future the tnid is available is required to log to the database.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
//
function ciniki_core_logFileMsg($ciniki, $tnid, $file, $msg) {

    if( isset($ciniki['config']['ciniki.core']['log_dir']) && $ciniki['config']['ciniki.core']['log_dir'] != '' ) {
        $ip = '- ';
        if( isset($_SERVER['REMOTE_ADDR']) ) {
            $ip = $_SERVER['REMOTE_ADDR'] . ' ';
        }
        $date = '[' . date('d/M/Y:H:i:s O') . '] ';
        $filename = $ciniki['config']['ciniki.core']['log_dir'] . '/' . $file . '-' . date('Y-m') . '.log';
        if( file_put_contents($filename, $ip . $date . $msg . "\n", FILE_APPEND) === FALSE ) {
            error_log($msg);
        }
    } else {
        error_log($msg);
    }

    return array('stat'=>'ok');
}
?>
