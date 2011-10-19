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
// business_id:			The business to attach the thread to.
// state:				The opening state of the thread.
// subject:				The subject for the thread.
// source:				The source of the thread.
// source_link:			The link back to the source object.
// 
// Returns
// -------
//
function moss_core_threadAdd($moss, $module, $table, $args) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInsert.php');

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "INSERT INTO $table (business_id, user_id, subject, state, "
		. "source, source_link, options, "
		. "date_added, last_updated) VALUES (";

	// business_id
	if( isset($args['business_id']) && $args['business_id'] != '' && $args['business_id'] > 0 ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['business_id']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'201', 'msg'=>'Required argument missing', 'pmsg'=>'No business_id'));
	}

	// user_id
	if( isset($args['user_id']) && $args['user_id'] != '' && $args['user_id'] > 0 ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['user_id']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'214', 'msg'=>'Required argument missing', 'pmsg'=>'No user_id'));
	}

	// subject
	if( isset($args['subject']) && $args['subject'] != '' ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['subject']) . "', ";
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'210', 'msg'=>'Required argument missing', 'pmsg'=>'No subject'));
	}

	// state - optional
	if( isset($args['state']) && $args['state'] != '' ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['state']) . "', ";
	} else {
		$strsql .= "'', ";
	}

	// source - optional
	if( isset($args['source']) && $args['source'] != '' ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['source']) . "', ";
	} else {
		$strsql .= "'', ";
	}

	// source_link - optional
	if( isset($args['source_link']) && $args['source_link'] != '' ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['source_link']) . "', ";
	} else {
		$strsql .= "'', ";
	}

	// options - optional
	if( isset($args['options']) && $args['options'] != '' ) {
		$strsql .= "'" . moss_core_dbQuote($moss, $args['options']) . "', ";
	} else {
		$strsql .= "'0', ";
	}

	$strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	return moss_core_dbInsert($moss, $strsql, $module);
}
?>
