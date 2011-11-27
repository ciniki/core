<?php
//
// Description
// -----------
// This function will query the database, and build a hash tree based
// on the elements of the $tree variable.  
// 
// FIXME: add documentation
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
// container_name:	The name of the xml/hash tag to return the data under, 
//					when there is only one row returned.
// col_name:		The column to be used as the row ID within the result.
//
function ciniki_core_dbHashQueryTree($ciniki, $strsql, $module, $tree) {
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'184', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
	}

	//
	// Check if any rows returned from the query
	//
	$rsp = array('stat'=>'ok');
	$rsp['num_rows'] = 0;
	$rsp['num_cols'] = 0;

	//
	// Build array of rows
	//
	$rsp = array();
	$prev = array();
	$num_elements = array();
	for($i=0;$i<count($tree);$i++) {
		$prev[i] = null;
		$num_elements[i] = 0;
	}
	while( $row = mysql_fetch_assoc($result) ) {
		// 
		// Check if we have anything new at each depth
		//
		$data = &$rsp;
		for($i=0;$i<count($tree);$i++) {
			if( $i > 0 ) {
				// $data = $data[$tree[$i]['container'];
			}
			if( $prev[$i] != $row[$tree[$i]['fname']] ) {
				// Check if container exists
				if( $data[$tree[$i]['container']] == null ) {
					$data[$tree[$i]['container']] = array();
				}
				$data[$tree[$i]['container']][$num_elements[$i]] = array($tree[$i]['name']=>array());
				// Copy Data
				foreach($tree[$i]['fields'] as $field) {
					$data[$tree[$i]['container']][$num_elements[$i]][$tree[$i]['name']][$field] = $row[$field];
				}
				$num_elements[$i]++;
			}
			$prev[$i] = $row[$tree[$i]['fname'];
			$data = $data[$tree[$i]['container']][$num_elements[$i]][$tree[$i]['name']];
		}
	}

	$rsp['num_rows'] = $num_col_x;

	return $rsp;
}
?>
