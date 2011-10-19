<?php
//
// Description
// -----------
// This function will get a "column" of infomration from a table, and return
// it by the ID given in the arguments.
//
// *note* this is an advanced function, and if not used properly can result
// in bad data returned.  Row's can be overwritted in the hash output if
// two result rows has the same value for the col_name field.
//
// This function was developed to support moss_imports_autoMerge.
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// moss:			The moss data structure.
// strsql: 			The SQL string to query the database.
// module:			The module name the query is acting on.
// container_name:	The name of the xml/hash tag to return the data under, 
//					when there is only one row returned.
// col_name:		The column to be used as the row ID within the result.
//
function moss_core_dbHashIDQuery3($moss, $strsql, $module, $col_x_container, $col_x_fname, $col_x_name, $col_y_container, $col_y_fname, $col_y_name) {
	//
	// Open a connection to the database if one doesn't exist.  The
	// dbConnect function will return an open connection if one 
	// exists, otherwise open a new one
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
		return array('stat'=>'fail', 'err'=>array('code'=>'185', 'msg'=>'Database Error', 'pmsg'=>mysql_error($dh)));
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
	$rsp[$col_x_container] = array();
	$num_col_x = -1;
	$num_col_y = -1;
	$prev_col_x_value = "";
	$prev_col_y_value = "";
	while( $row = mysql_fetch_assoc($result) ) {
		//
		// If we have a new value for column X, then start a new container
		//
		if( $row[$col_x_fname] != $prev_col_x_value ) {
			$num_col_x++;
			$num_col_y=0;
			$rsp[$col_x_container][$num_col_x] = array($col_x_name=>array('id'=>$row[$col_x_fname], $col_y_container=>array()));
		}
		$rsp[$col_x_container][$num_col_x][$col_x_name][$col_y_container][$num_col_y] = array($col_y_name=>$row);
		unset($rsp[$col_x_container][$num_col_x][$col_x_name][$col_y_container][$num_col_y][$col_y_name][$col_x_fname]);
		unset($rsp[$col_x_container][$num_col_x][$col_x_name][$col_y_container][$num_col_y][$col_y_name][$col_y_fname]);
		$num_col_y++;
		if( $num_col_y > $rsp['num_cols'] ) {
			$rsp['num_cols'] = $num_col_y;
		}
		$prev_col_x_value = $row[$col_x_fname];
	}

	$rsp['num_rows'] = $num_col_x;

	return $rsp;
}
?>
