<?php
//
// Description
// -----------
// This function will run an update query against the database.
// This function will not look for result rows.
//
// Arguments
// ---------
// ciniki:
// strsql:				The SQL update string.
// module:				The name of the module for the transaction, which should include the 
//						package in dot notation.  Example: ciniki.artcatalog
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
	$result = mysqli_query($dh, $strsql);
	if( $result == false ) {
		error_log("SQLERR: " . mysqli_error($dh) . " -- '$strsql'");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'11', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_affected_rows'] = mysqli_affected_rows($dh);

	return $rsp;
}
?>
