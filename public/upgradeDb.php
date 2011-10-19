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
function moss_core_upgradeDb($moss) {
	//
	// Check access restrictions to monitorChangeLogs
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = moss_core_checkAccess($moss, 0, 'moss.core.upgradeDb');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbGetMOSSTables.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbUpgradeTable.php');

	$tables = moss_core_dbGetMOSSTables($moss);

	// FIXME: If in multiple databases, this script will need to be updated.

	$strsql = "SHOW TABLE STATUS";
	$rc = moss_core_dbHashIDQuery($moss, $strsql, 'core', 'tables', 'Name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	foreach($rc['tables'] as $table_name => $table) {
		if( isset($tables[$table_name]) ) {
			if( preg_match('/(v[0-9]+\.[0-9]+)([^0-9]|$)/i', $table['Comment'], &$matches) ) {
				$tables[$table_name]['database_version'] = $matches[1];
			}
		}
	}
	
	foreach($tables as $table_name => $table) {
		$schema = file_get_contents($moss['config']['core']['modules_dir'] . "/" . $table['module']	. "/db/$table_name.schema");
		if( preg_match('/comment=\'(v[0-9]+\.[0-9]+)\'/i', $schema, &$matches) ) {
			$new_version = $matches[1];
			if( $new_version != $tables[$table_name]['database_version'] ) {
				$rc = moss_core_dbUpgradeTable($moss, $tables[$table_name]['module'], $table_name, 
					$tables[$table_name]['database_version'], $new_version);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
	}

	return array('stat'=>'ok');
}
?>
