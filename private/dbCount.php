<?php
//
// Description
// -----------
// This function is optimized for queries which do a count of rows.
// The query should be in the form of SELECT hash_id, count(*) as count_number FROM ...
//
// Info
// ----
// status:				beta
//
// Arguments
// ---------
// ciniki: 				The ciniki data structure with current session.
// strsql:				The SQL string to query the database with.
// module:				The name of the module to pull the data from.
//						The module name is used for database connection cache.
// container_name:		The container name to attach the data when only one row returned.
// no_row_error:		The error code and msg to return when no rows were returned from the query.
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
		return array('stat'=>'fail', 'err'=>array('code'=>'145', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
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

	// 
	// FIXME: If tmpl, then  apply template to each row 
	//

	return $rsp;
}
?>
