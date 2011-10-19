<?php
//
// Description
// -----------
// This function is optimized to retrieve detail information,
// in the form of key=value for a module.
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
//
function moss_core_dbDetailsQuery($moss, $table, $key, $key_value, $module, $container_name, $detail_key) {
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
	$strsql = "SELECT detail_key, detail_value FROM " . moss_core_dbQuote($moss, $table) . " "
		. "WHERE " . moss_core_dbQuote($moss, $key) . " = '" . moss_core_dbQuote($moss, $key_value) . "' ";
	if( $detail_key != '' ) {
		$strsql .= " AND detail_key like '" . moss_core_dbQuote($moss, $detail_key) . ".%'";
	}
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'44', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok', $container_name=>array());

	//
	// Build array of rows
	//
	while( $row = mysql_fetch_row($result) ) {
		$rsp[$container_name][$row[0]] = $row[1];
	}

	return $rsp;
}
?>
