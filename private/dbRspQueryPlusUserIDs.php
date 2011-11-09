<?php
//
// Description
// -----------
// This function will execute a SQL query, and if it finds any user_id fields, they will be appended to 
// a list and returned.
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
// row_name:			The row name to attached each row to.
// no_row_error:		The error code and msg to return when no rows were returned from the query.
//
function ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, $module, $container_name, $row_name, $no_row_error) {
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'147', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	if( mysql_num_rows($result) <= 0 ) {
		return $no_row_error;
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
	$rsp['user_ids'] = array();
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbParseAge.php');
	while( $row = mysql_fetch_assoc($result) ) {
		$rsp[$container_name][$rsp['num_rows']] = array($row_name=>$row);
		if( $row['user_id'] > 0 ) {
			array_push($rsp['user_ids'], $row['user_id']);
		}
		if( isset($row['age']) ) {
			$rsp[$container_name][$rsp['num_rows']][$row_name]['age'] = ciniki_core_dbParseAge($ciniki, $row['age']);
		}
		$rsp['num_rows']++;
	}

	return $rsp;
}
?>
