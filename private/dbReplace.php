<?php
//
// Description
// -----------
// This function should only be used when the REPLACE sql command
// for mysql is used.  This should be used instead of a update/insert 
// combination.  
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// 
//
function ciniki_core_dbReplace($ciniki, $strsql, $module) {
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'337', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh), 'dberrno'=>mysql_errno($dh), 'sql'=>$strsql));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_affected_rows'] = mysql_affected_rows($dh);

	return $rsp;
}
?>
