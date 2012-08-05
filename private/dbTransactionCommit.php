<?php
//
// Description
// -----------
// This function will commit a transaction for a module.
//
// *note* All transaction control should be managed by the 
// public API method not any of the private ones.
//
// *alert* Currently all tables are in the same database,
// and there's no independence between Commit.  If you commit
// for one module, it will commit for all.  The $module variable
// is for the future.
//
// Arguments
// ---------
// ciniki:
// module:			The name of the module for the transaction, which should include the 
//					package in dot notation.  Example: ciniki.artcatalog
//
function ciniki_core_dbTransactionCommit($ciniki, $module) {

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbConnect.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuery.php');

	$rc = ciniki_core_dbConnect($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return ciniki_core_dbQuery($ciniki, "COMMIT", $module);
}
?>
