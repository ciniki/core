<?php
//
// Description
// -----------
// This function will take options from the 
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// ciniki:
// module:				The package.module the thread is located in.
// table:				The database table that stores the thread information.
// args:				Additional arguments provided function.
//
// 						business_id - The business to attach the thread to.
// 						state - The opening state of the thread.
// 						subject - The subject for the thread.
// 						source - The source of the thread.
// 						source_link - The link back to the source object.
// 
// Returns
// -------
//
function ciniki_core_threadAdd($ciniki, $module, $table, $args) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');

	//
	// Don't worry about autocommit here, it's taken care of in the calling function
	//

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "INSERT INTO $table (business_id, user_id, subject, state, "
		. "source, source_link, options, "
		. "date_added, last_updated) VALUES (";

	// business_id
	if( isset($args['business_id']) && $args['business_id'] != '' && $args['business_id'] > 0 ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'201', 'msg'=>'Required argument missing', 'pmsg'=>'No business_id'));
	}

	// user_id
	if( isset($args['user_id']) && $args['user_id'] != '' && $args['user_id'] > 0 ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'214', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
	}

	// subject
	if( isset($args['subject']) && $args['subject'] != '' ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'210', 'msg'=>'Required argument missing', 'pmsg'=>'No subject'));
	}

	// state - optional
	if( isset($args['state']) && $args['state'] != '' ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['state']) . "', ";
	} else {
		$strsql .= "'', ";
	}

	// source - optional
	if( isset($args['source']) && $args['source'] != '' ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['source']) . "', ";
	} else {
		$strsql .= "'', ";
	}

	// source_link - optional
	if( isset($args['source_link']) && $args['source_link'] != '' ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['source_link']) . "', ";
	} else {
		$strsql .= "'', ";
	}

	// options - optional
	if( isset($args['options']) && $args['options'] != '' ) {
		$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['options']) . "', ";
	} else {
		$strsql .= "'0', ";
	}

	$strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	return ciniki_core_dbInsert($ciniki, $strsql, $module);
}
?>
