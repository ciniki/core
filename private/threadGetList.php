<?php
//
// Description
// -----------
// This function will retrieve the list of thread subjects from the
// database.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// business_id:			The business to get the threads for.
// state:				(optional) Find threads with the matching state.
// user_id:				(optional) Find threads that were opened by this user.
// subject:				(optional) Find threads with the matching subject.
// source:				(optional) Find threads with the matching source.
// source_link:			(optional) Find threads with the matching source_link.
// 
// Returns
// -------
// <THREADs>
// 	<THREAD id="1" subject="The thread subject"
// </THREADs>
//
function ciniki_core_threadGetList($ciniki, $module, $table, $container_name, $row_name, $args) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQueryPlusDisplayNames.php');

	//
	// FIXME: Add timezone information from business settings
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "SELECT id, business_id, user_id, subject, state, "
		. "source, source_link, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM " . ciniki_core_dbQuote($ciniki, $table) . " "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";

	// state - optional
	if( isset($args['state']) ) {
		$strsql .= "AND state = '" . ciniki_core_dbQuote($ciniki, $args['state']) . "' ";
	} else {
		//
		// Default to a blank state, which should be none for any threads using states,
		// or will be blank if the thread does not use a state.   Either way, it's the
		// best default option.
		//
		$strsql .= "AND state = '' ";
	}

	// user_id
	if( isset($args['user_id']) && $args['user_id'] > 0 ) {
		$strsql .= "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
	}

	// subject
	if( isset($args['subject']) ) {
		$strsql .= "AND subject = '" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
	}

	// source - optional
	if( isset($args['source']) ) {
		$strsql .= "AND source = '" . ciniki_core_dbQuote($ciniki, $args['source']) . "' ";
	}

	// source_link - optional
	if( isset($args['source_link']) ) {
		$strsql .= "AND source_link = '" . ciniki_core_dbQuote($ciniki, $args['source_link']) . "' ";
	}

	$strsql .= "ORDER BY id ";

	return ciniki_core_dbRspQueryPlusDisplayNames($ciniki, $strsql, $module, $container_name, $row_name, array('stat'=>'ok', $container_name=>array()));
}
?>
