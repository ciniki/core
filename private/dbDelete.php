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
function moss_core_dbDelete($moss, $strsql, $module) {
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	return moss_core_dbUpdate($moss, $strsql, $module);
}
?>
