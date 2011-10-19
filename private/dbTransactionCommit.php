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
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// module:			The name of the module for the transaction.
//
function moss_core_dbTransactionCommit($moss, $module) {

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbConnect.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuery.php');

	$rc = moss_core_dbConnect($moss, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return moss_core_dbQuery($moss, "COMMIT", $module);
}
?>
