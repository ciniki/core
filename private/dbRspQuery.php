<?php
//
// Description
// -----------
// The moss.core.dbRspQuery method will format a query response which
// can be directly handed back through the API if necessary.
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
// no_row_error:		The error code and msg to return when no rows were returned from the query.
//
function moss_core_dbRspQuery($moss, $strsql, $module, $container_name, $row_name, $no_row_error) {
	//
	// Check connection to database, and open if necessary
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbParseAge.php');
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
		return array('stat'=>'fail', 'err'=>array('code'=>'32', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
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
	while( $row = mysql_fetch_assoc($result) ) {
		$rsp[$container_name][$rsp['num_rows']] = array($row_name=>$row);
		if( isset($row['age']) ) {
			$rsp[$container_name][$rsp['num_rows']][$row_name]['age'] = moss_core_dbParseAge($moss, $row['age']);
		}
		$rsp['num_rows']++;
	}

	// 
	// FIXME: If tmpl, then  apply template to each row 
	//

	return $rsp;
}
?>
