<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
// sync_id:         The ID of the sync to check compatibility with.
//
function ciniki_core_logMsg($ciniki, $lvl, $msg) {

//  if( isset($_SERVER['argc']) ) {
    if( php_sapi_name() == 'cli' ) {
        error_log('[' . date('d/M/Y:H:i:s O') . '] ' . $msg);
    } else {
        error_log($msg);
    }

    return array('stat'=>'ok');
}
?>
