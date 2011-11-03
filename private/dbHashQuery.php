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
// strsql: 			The SQL string to query the database.
// module:			The module name the query is acting on.
// container_name:	The name of the xml/hash tag to return the data under, 
//					when there is only one row returned.
//
function ciniki_core_dbHashQuery($ciniki, $strsql, $module, $container_name) {
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'46', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_rows'] = 0;

	//
	// Build array of rows
	//
	$rsp['rows'] = array();
	while( $row = mysql_fetch_assoc($result) ) {
		$rsp['rows'][$rsp['num_rows']++] = $row;
	}


	//
	// Setup the container name if specified
	//
	if( $rsp['num_rows'] == 1 && $container_name != '' ) {
		$rsp[$container_name] = $rsp['rows']['0'];
	}

	return $rsp;
}
?>
