<?php
//
// Description
// -----------
// This function is the same as dbRspQuery but will add an addition output of the users
// if finds via the user_id specifed in the query.
//
// Info
// ----
// status:				beta
//
// Arguments
// ---------
// moss: 				The moss data structure with current session.
// strsql:				The SQL string to query the database with.
// module:				The name of the module to pull the data from.
//						The module name is used for database connection cache.
// container_name:		The container name to attach the data when only one row returned.
// row_name:			The row name to attached each row to.
// no_row_error:		The error code and msg to return when no rows were returned from the query.
//
function moss_core_dbRspQueryPlusUsers($moss, $strsql, $module, $container_name, $row_name, $no_row_error) {
	//
	// Check connection to database, and open if necessary
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
		return array('stat'=>'fail', 'err'=>array('code'=>'148', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
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
	$users = array();
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbParseAge.php');
	while( $row = mysql_fetch_assoc($result) ) {
		$rsp[$container_name][$rsp['num_rows']] = array($row_name=>$row);
		$users[$row['user_id']] = 1;
		if( isset($row['age']) ) {
			$rsp[$container_name][$rsp['num_rows']][$row_name]['age'] = moss_core_dbParseAge($moss, $row['age']);
		}
		$rsp['num_rows']++;
	}

	//
	// Get the users who contributed to the actions
	//
	$rc = moss_core_dbConnect($moss, 'users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$dh = $rc['dh'];

	//
	$strsql = "SELECT id, display_name "
		. "FROM users "
		. "WHERE id IN (" . moss_core_dbQuote($moss, implode(',', array_keys($users))) . ") ";
	$result = mysql_query($strsql, $dh);
	if( $result == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'149', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	if( mysql_num_rows($result) <= 0 ) {
		return array('stat'=>'ok', 'history'=>array(), 'users'=>array());
	}

	$num_users = 0;
	while( $row = mysql_fetch_assoc($result) ) {
		$num_users++;
		$rsp['users'][$row['id']] = array('user'=>$row);
	}

	return $rsp;
}
?>
