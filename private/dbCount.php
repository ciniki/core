<?php
//
// Description
// -----------
// This function is optimized for queries which do a count of rows.
// The query should be in the form of SELECT hash_id, count(*) as count_number FROM ...
//
// Arguments
// ---------
// ciniki: 				The ciniki data structure with current session.
// strsql:				The SQL string to query the database with.
// module:				The name of the module for the transaction, which should include the 
//						package in dot notation.  Example: ciniki.artcatalog
// container_name:		The container name to attach the data when only one row returned.
//
function ciniki_core_dbCount($ciniki, $strsql, $module, $container_name) {
	//
	// Check connection to database, and open if necessary
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'145', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// FIXME: If hash, then return all rows together as a hash
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_rows'] = 0;

	//
	// Build array of rows
	//
	$rsp[$container_name] = array();
	while( $row = mysql_fetch_row($result) ) {
		$rsp[$container_name][$row[0]] = $row[1];
		$rsp['num_rows']++;
	}

	return $rsp;
}
?>
