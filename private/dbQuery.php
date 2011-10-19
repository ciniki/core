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
// moss:			The moss data structure.
// strsql: 			The SQL string to query the database.
// module:			The module name the query is acting on.
//
function moss_core_dbQuery($moss, $strsql, $module) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
	//
	$rc = moss_core_dbConnect($moss, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	// Prepare and Execute Query
	//
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'93', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	return array('stat'=>'ok', 'handle'=>$result);
}
?>
