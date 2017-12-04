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
function ciniki_core_syncLog($ciniki, $lvl, $msg, $err) {

    if( isset($ciniki['syncloglvl']) && $ciniki['syncloglvl'] < $lvl ) {
        return array('stat'=>'ok');
    }

    $log_msg = '';
    $prefix = '';
    if( isset($ciniki['synclogprefix']) ) {
        $prefix = $ciniki['synclogprefix'] . ' ';
    }
    if( $lvl == 0 ) {
        $log_msg = "SYNC-ERR: $prefix$msg";
    } else {
        $log_msg = "SYNC-INFO: $prefix$msg";
    }

    if( $err != null && isset($err['msg']) ) {
        $log_msg .= ' {' . serialize($err) . '}';
    }
    
    if( isset($ciniki['synclogfile']) && $ciniki['synclogfile'] != '' ) {
        error_log('[' . date('d/M/Y:H:i:s O') . '] ' . $log_msg . "\n", 3, $ciniki['synclogfile']);
    } else {
        error_log($log_msg);
    }

    return array('stat'=>'ok');
}
?>
