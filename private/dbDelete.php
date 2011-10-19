<?php
//
// Description
// -----------
// This function will run an delete query against the database. 
// This function is a placeholder and just a passthrough to dbUpdate
//
// Info
// ----
// status:			beta
//
// Arguments
// ---------
// 
//
function ciniki_core_dbDelete($ciniki, $strsql, $module) {
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	return ciniki_core_dbUpdate($ciniki, $strsql, $module);
}
?>
