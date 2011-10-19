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
function moss_core_threadGetList($moss, $module, $table, $container_name, $row_name, $args) {
	//
	// All arguments are assumed to be un-escaped, and will be passed through dbQuote to
	// ensure they are safe to insert.
	//

	// Required functions
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbRspQueryPlusUsers.php');

	// 
	// Setup the SQL statement to insert the new thread
	//
	$strsql = "SELECT id, business_id, user_id, subject, state, "
		. "source, source_link, date_added, last_updated "
		. "FROM " . moss_core_dbQuote($moss, $table) . " "
		. "WHERE business_id = '" . moss_core_dbQuote($moss, $args['business_id']) . "' ";
	
	// state - optional
	if( isset($args['state']) ) {
		$strsql .= "AND state = '" . moss_core_dbQuote($moss, $args['state']) . "' ";
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
		$strsql .= "AND user_id = '" . moss_core_dbQuote($moss, $args['user_id']) . "' ";
	}

	// subject
	if( isset($args['subject']) ) {
		$strsql .= "AND subject = '" . moss_core_dbQuote($moss, $args['subject']) . "', ";
	}

	// source - optional
	if( isset($args['source']) ) {
		$strsql .= "AND source = '" . moss_core_dbQuote($moss, $args['source']) . "' ";
	}

	// source_link - optional
	if( isset($args['source_link']) ) {
		$strsql .= "AND source_link = '" . moss_core_dbQuote($moss, $args['source_link']) . "' ";
	}

	$strsql .= "ORDER BY id ";

	return moss_core_dbRspQueryPlusUsers($moss, $strsql, $module, $container_name, $row_name, array('stat'=>'ok', $container_name=>array()));
}
?>
