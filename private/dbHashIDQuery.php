<?php
//
// Description
// -----------
// This function will get a "column" of information from a table, and return
// it by the ID given in the arguments.
//
// *note* this is an advanced function, and if not used properly can result
// in bad data returned.  Row's can be overwritted in the hash output if
// two result rows has the same value for the col_name field.
//
// This function was developed to support moss_imports_autoMerge.
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
// container_name:	The name of the xml/hash tag to return the data under, 
//					when there is only one row returned.
// col_name:		The column to be used as the row ID within the result.
//
function moss_core_dbHashIDQuery($moss, $strsql, $module, $container_name, $col_name) {
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
		error_log("SQLERR: " . mysql_error($dh) . " -- '$strsql'");
		return array('stat'=>'fail', 'err'=>array('code'=>'19', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_rows'] = 0;

	//
	// Build array of rows
	//
	$rsp[$container_name] = array();
	while( $row = mysql_fetch_assoc($result) ) {
		$rsp[$container_name][$row[$col_name]] = $row;
		$rsp['num_rows']++;
	}

	return $rsp;
}
?>
