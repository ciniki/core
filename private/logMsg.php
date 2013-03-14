<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
//
function ciniki_core_logMsg($ciniki, $lvl, $msg) {

	if( isset($_SERVER['argc']) ) {
		error_log('[' . date('d/M/Y:H:i:s O') . '] ' . $msg);
	} else {
		error_log($msg);
	}

	return array('stat'=>'ok');
}
?>
