<?php
//
// Description
// -----------
// This function will run an insert query against the database. 
// This function is a placeholder and just a passthrough to dbUpdate
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// 
//
function ciniki_core_dbInsert(&$ciniki, $strsql, $module) {
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
		//
		// Only error if not a duplicate key problem
		//
		if( mysql_errno($dh) != 1062 && mysql_errno($dh) != 1022 ) {
			error_log("SQLERR: " . mysql_error($dh) . " -- '$strsql'");
		}
		return array('stat'=>'fail', 'err'=>array('code'=>'73', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh), 'dberrno'=>mysql_errno($dh), 'sql'=>$strsql));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_affected_rows'] = mysql_affected_rows($dh);
	$rsp['insert_id'] = mysql_insert_id($dh);

	unset($rc);

	return $rsp;
}
?>
