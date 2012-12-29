<?php
//
// Description
// -----------
// This function will return a new Universal Unique ID
//
// Arguments
// ---------
// ciniki: 				
// module:				The name of the module for the transaction, which should include the 
//						package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbUUID($ciniki, $module) {
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
	$result = mysqli_query($dh, "SELECT UUID() AS uuid");
	if( $result == false ) {
		error_log("SQLERR: " . mysqli_error($dh) . " -- '$strsql'");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'139', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
	}

	if( ($row = mysqli_fetch_row($result)) ) {
		$uuid = $row[0];
	} else {
		mysqli_free_result($result);
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'138', 'msg'=>'Database Error'));
	}

	mysqli_free_result($result);

	return array('stat'=>'ok', 'uuid'=>$uuid);
}
?>
