<?php
//
// Description
// -----------
// This function will return 
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// moss:
// business_id:			The id of the business to get the tags for.
// module:				The module the tags are in.
// prefix:				The table prefix used in the query.
// container:			The name of the xml container to hold the return values.
// row_name:			The name for the xml item inside the container, one for each row.
// no_row_err:			If no rows are returned in the query, what should be returned.  This must be in the format of array('stat'=>'ok')
// options:				An associated array for options.
//						
//						state - select only tags where the thread is this state.
//						source - select only tags where the thread is from this source.
// 
// Returns
// -------
// <$container_name>
//		<$row_name field=value ... />
// </$container_name>
//
function moss_core_threadGetTags($moss, $business_id, $module, $prefix, $container, $row_name, $no_row_err, $options) {

	$strsql = "SELECT DISTINCT tag FROM " . moss_core_dbQuote($moss, "{$prefix}s") . ", " . moss_core_dbQuote($moss, "{$prefix}_tags") . " "
		. "WHERE " . moss_core_dbQuote($moss, "{$prefix}s.business_id") . " = '" . moss_core_dbQuote($moss, $business_id) . "' ";

	//
	// Check if there was state specified
	//
	if( is_array($options) && isset($options['state']) && $options['state'] != '' ) {
		$strsql .= "AND " . moss_core_dbQuote($moss, "{$prefix}s.state") . " = '" . moss_core_dbQuote($moss, $options['state']) . "' ";
	} 

	//
	// Check if there was source specified
	//
	if( is_array($options) && isset($options['source']) && $options['source'] != '' ) {
		$strsql .= "AND " . moss_core_dbQuote($moss, "{$prefix}s.state") . " = '" . moss_core_dbQuote($moss, $options['source']) . "' ";
	}

	// 
	// Connect the main to the tags table
	// eg: AND bugs.id = bug_tags.bug_id
	//
	$strsql .= "AND " . moss_core_dbQuote($moss, "{$prefix}s.id") . " = " . moss_core_dbQuote($moss, "{$prefix}_tags") . "." . moss_core_dbQuote($moss, "{$prefix}_id") . " ";

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return moss_core_dbRspQuery($moss, $strsql, $module, $container, $row_name, $no_row_err);
}
?>
