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
function ciniki_core_dbHashQueryIDTree(&$ciniki, $strsql, $module, $tree) {
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
	$result = mysqli_query($dh, $strsql);
	if( $result == false ) {
		error_log("SQLERR: " . mysqli_error($dh) . " -- '$strsql'");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'183', 'msg'=>'Database Error', 'pmsg'=>mysqli_error($dh)));
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
	while( $row = mysqli_fetch_assoc($result) ) {
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
					foreach($tree[$i]['fields'] as $field_id => $field) {
						if( !is_string($field_id) && is_int($field_id) ) {
							// Field is in integer and should not be mapped
							$field_id = $field;
						}
						//
						// Items that are mapped to another value
						//
						if( isset($tree[$i]['maps']) && isset($tree[$i]['maps'][$field]) ) {
							//
							// Check if the value is specified in the mapped array for this field
							// If no mapped value specified, check for blank index
							// Last resort, set it to current value
							//
							if( isset($tree[$i]['maps'][$field][$row[$field]]) ) {
//								$data[$tree[$i]['container']][$num_elements[$i]][$tree[$i]['name']][$field_id] = $tree[$i]['maps'][$field][$row[$field]];
								$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field_id] = $tree[$i]['maps'][$field][$row[$field]];
							} elseif( isset($tree[$i]['maps'][$field]['']) ) {
//								$data[$tree[$i]['container']][$num_elements[$i]][$tree[$i]['name']][$field_id] = $tree[$i]['maps'][$field][''];
								$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field_id] = $tree[$i]['maps'][$field][''];
							} else {
								$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field_id] = $row[$field];
//								$data[$tree[$i]['container']][$num_elements[$i]][$tree[$i]['name']][$field_id] = $row[$field];
							}
						} 
						//
						// Check if utc dates should be converted to local timezone
						//
						elseif( isset($tree[$i]['utctotz']) && isset($tree[$i]['utctotz'][$field_id]) ) {
							if( $row[$field] == '0000-00-00 00:00:00' || $row[$field] == '0000-00-00' ) {
								$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field_id] = '';
							} else {
								$date = new DateTime($row[$field], new DateTimeZone('UTC'));
								$date->setTimezone(new DateTimeZone($tree[$i]['utctotz'][$field_id]['timezone']));
								$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field_id] = 
									$date->format($tree[$i]['utctotz'][$field_id]['format']);
							}
						} 

						//
						// Normal item, copy the data
						//
						else {
							$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field_id] = $row[$field];
						}
					}
//					$data = &$data[$tree[$i]['container']][$row[$tree[$i]['fname']]];
				}
			}
			else {
// Untested code: Was added, but then not needed.
//				foreach($tree[$i]['fields'] as $field) {
//					if( isset($tree[$i]['idlists']) && in_array($field, $tree[$i]['idlists']) 
//						&& $prev_row != null && $prev_row[$field] != $row[$field] ) {
//						//
//						// Check if field was declared in fields array, if not it can be added now
//						//
//						if( isset($data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field]) ) {
//							$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field] .= ',' . $row[$field];
//						} else {
//							$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$field] = $row[$field];
//						}
//					}
//				}

//				$data = &$data[$tree[$i]['container']][$row[$tree[$i]['fname']]];
			}


			//
			// Check for subcontainers
			//
			if( isset($tree[$i]['containers']) ) {
				foreach($tree[$i]['containers'] as $cname => $container) {
					//
					// Check container exists
					//
					if( !isset($data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$cname]) ) {
						$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$cname] = array();
					}
					
					// 
					// Check key does not exist
					//
					$c_key = $container['fname'];
					if( !is_null($row[$c_key])
						&& !isset($data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$cname][$row[$c_key]]) ) {
						$s_data = array();
						foreach($container['fields'] as $s_field_id => $s_field) {
							if( !is_string($s_field_id) && is_int($s_field_id) ) {
								// Field is in integer and should not be mapped
								$s_field_id = $s_field;
							}
							
							$s_data[$s_field_id] = $row[$s_field];
						}
						$data[$tree[$i]['container']][$row[$tree[$i]['fname']]][$cname][$row[$c_key]] = $s_data;
					}
				}
			}

			$data = &$data[$tree[$i]['container']][$row[$tree[$i]['fname']]];

			$prev[$i] = $row[$tree[$i]['fname']];
		}
	}

	mysqli_free_result($result);

	return $rsp;
}
?>
