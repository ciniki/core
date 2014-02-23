<?php
//
// Description
// -----------
// This function will rollback a transaction for the database.
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
function ciniki_core_dbTransactionRollback(&$ciniki, $module) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbConnect');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuery');

	$rc = ciniki_core_dbConnect($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return ciniki_core_dbQuery($ciniki, "ROLLBACK", $module);
}
?>
