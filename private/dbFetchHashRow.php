<?php
//
// Description
// -----------
// This function will query the database and return a hash of rows.
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// ciniki:			The ciniki data structure.
// handle:			The mysql resource handle to fetch the next row from.
//
function ciniki_core_dbFetchHashRow($ciniki, $handle) {
	//
	// Prepare and Execute Query
	//
	if( $handle == 'false' ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'86', 'msg'=>'Database error'));
	}
	if( $row = mysql_fetch_assoc($handle) ) {
		return array('stat'=>'ok', 'row'=>$row);
	}

	return array('stat'=>'ok');
}
?>
