<?php
//
// Description
// -----------
// This function will check for and upgrade any tables which are out of date.
//
// *alert* When the database is split between database installs, this file will need to be modified.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//	<tables>
//		<table_name name='users' />
//	</tables>
//
function ciniki_core_upgradeDb($ciniki) {
	//
	// Check access restrictions to monitorChangeLogs
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.upgradeDb');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpgradeTables.php');
	return ciniki_core_dbUpgradeTables($ciniki);
}
?>
