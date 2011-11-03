<?php
//
// Description
// -----------
// This function is optimized to retrieve detail information,
// from any of the table_details tables, and return it in
// a structured hash form.  This is useful for returning
// as XML through the API, or used internally.
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
//
function ciniki_core_dbDetailsQueryHash($ciniki, $table, $key, $key_value, $detail_key, $module) {
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
	$strsql = "SELECT detail_key, detail_value FROM " . ciniki_core_dbQuote($ciniki, $table) . " "
		. "WHERE " . ciniki_core_dbQuote($ciniki, $key) . " = '" . ciniki_core_dbQuote($ciniki, $key_value) . "' ";
	if( $detail_key != '' ) {
		$strsql .= " AND detail_key LIKE '" . ciniki_core_dbQuote($ciniki, $detail_key) . ".%'";
	}
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'125', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok', 'details'=>array());

	//
	// Build array of rows
	//
	while( $row = mysql_fetch_row($result) ) {
		$split_key = preg_split('/\./', $row[0]);
		$cur_key = &$rsp['details'];
		for($i=0;$i<count($split_key)-1;$i++) {
			if( !isset($cur_key[$split_key[$i]]) ) {
				$cur_key[$split_key[$i]] = array();
			}
			$cur_key = &$cur_key[$split_key[$i]];
		}
		$cur_key[$split_key[$i]] = $row[1];
	}
	
	return $rsp;
}
?>
