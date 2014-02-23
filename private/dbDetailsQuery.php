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
// ciniki:			
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
// container_name:	The name of the xml/hash tag to return the data under, 
//					when there is only one row returned.
//
function ciniki_core_dbDetailsQuery(&$ciniki, $table, $key, $key_value, $module, $container_name, $detail_key) {
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
		$strsql .= " AND detail_key like '" . ciniki_core_dbQuote($ciniki, $detail_key) . ".%'";
	}
	$result = mysqli_query($dh, $strsql);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'44', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok', $container_name=>array());

	//
	// Build array of rows
	//
	while( $row = mysqli_fetch_row($result) ) {
		$rsp[$container_name][$row[0]] = $row[1];
	}

	mysqli_free_result($result);

	return $rsp;
}
?>
