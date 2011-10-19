<?php
//
// Description
// -----------
// This function will run an update query against the database.  The
// difference is this function will not look for result rows.
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
//
function ciniki_core_dbUpdate($ciniki, $strsql, $module) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = ciniki_core_dbConnect($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	// Prepare and Execute Query
	//
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		error_log("SQLERR: " . mysql_error($dh) . " -- '$strsql'");
		return array('stat'=>'fail', 'err'=>array('code'=>'11', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_affected_rows'] = mysql_affected_rows($dh);

	return $rsp;
}
?>
