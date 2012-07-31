<?php
//
// Description
// -----------
// This function will query the database, and build a hash tree based
// on the elements of the $tree variable.  This is similar to 
// dbHashQueryTree but does not build the sub container xml friendly model,
// this function returns indexed hash tree structure, where nodes are the ID's from the database.
// 
// FIXME: add documentation
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// ciniki:			
// strsql: 			The SQL string to query the database.
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
// container_name:	The name of the xml/hash tag to return the data under, 
//					when there is only one row returned.
// col_name:		The column to be used as the row ID within the result.
//
function ciniki_core_dbHashQueryIDTree($ciniki, $strsql, $module, $tree) {
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'183', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
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
	$prev = array();
	for($i=0;$i<count($tree);$i++) {
		$prev[$i] = null;
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
			// error_log($tree[$i]['fname'] . ' = ' . $row[$tree[$i]['fname']]);
			if( is_null($row[$tree[$i]['fname']]) ) {
				continue;
			}
			if( $prev[$i] != $row[$tree[$i]['fname']] ) {
				// Reset all this depth and below
				for($j=$i+1;$j<count($tree);$j++) {
					$prev[$j] = null;
				}
				//
				// Check if this is a simple value, or if there are fields
				//
				if( isset($tree[$i]['value']) && $tree[$i]['value'] != '' ) {
					$data[$tree[$i]['container']][$row[$tree[$i]['fname']]]	= $row[$tree[$i]['value']];
				} else {
					// Check if container exists
					if( !isset($data[$tree[$i]['container']]) ) {
						$data[$tree[$i]['container']] = array();
					}
					$data[$tree[$i]['container']][$row[$tree[$i]['fname']]] = array();
						
					// Copy Data
					foreach($tree[$i]['fields'] as $field) {
						$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field] = $row[$field];
					}
					$data = &$data[$tree[$i]['container']][$row[$tree[$i]['fname']]];
				}
			}
			else {
				$data = &$data[$tree[$i]['container']][$row[$tree[$i]['fname']]];
			}
			$prev[$i] = $row[$tree[$i]['fname']];
		}
	}

	return $rsp;
}
?>
