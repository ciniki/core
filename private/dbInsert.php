<?php
//
// Description
// -----------
// This function will run an insert query against the database. 
//
// Arguments
// ---------
// ciniki:
// strsql:			The SQL statement to execute which will INSERT a row into the database.
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
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
		// Error a different code if a duplicate key problem
		//
		if( mysql_errno($dh) == 1062 || mysql_errno($dh) == 1022 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'73', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh), 'dberrno'=>mysql_errno($dh), 'sql'=>$strsql));
		} else {
			error_log("SQLERR: " . mysql_error($dh) . " -- '$strsql'");
	 	}
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'74', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh), 'dberrno'=>mysql_errno($dh), 'sql'=>$strsql));
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
