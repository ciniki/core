<?php
//
// Description
// -----------
// This function will return the info about a business.  This information is used
// to compare with the remote system to determine if the two are compatitable for 
// a sync.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to get the sync information for.
//
// Returns
// -------
// <business uuid="4829482023-138492-829499849839-34839934">
//		<modules>
//			<module name="ciniki.artcatalog" permissions="">
//				<tables>
//					<table name="ciniki_artcatalog" version="v1.01" />
//					<table name="ciniki_artcatalog_history" version="v1.01" />
//				</tables>
//			</module>
//		</modules>
// </business>
//
function ciniki_core_syncInfo($ciniki, $business_id) {

	//
	// Check to make sure a business is specified
	//
	if( $business_id < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'557', 'msg'=>'No business specified'));
	}

	//
	// Result array
	//
	$rsp = array('stat'=>'ok', 'tables'=>array(), 'business'=>array('modules'=>array()));

	//
	// Get modules which are enabled for the business, and their checksums
	//
	$strsql = "SELECT package, module, ruleset, last_change "
		. "FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (status = 1 OR status = 2) "
		. "";

	//
	// Get the tables and their versions from the database
	//
	$strsql = "SHOW TABLE STATUS";

	//
	// Check each package/module for table.schema's and get version from database
	//
	foreach($modules as $module) {
		$dir = $package . '-api/' . $module . '/db';

	}


	//
	// Return information
	//
}
?>
