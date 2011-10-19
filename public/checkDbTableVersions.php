<?php
//
// Description
// -----------
// This function will get the current table versions from the database comments sections
// and compare with what's in the .schema file.  If upgrades need to happen, then the
// moss.core.upgradeDb API call be made.
//
// *alert* When the database is split between database installs, this file will need to be modified.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//	<tables>
//		<users database_version='v1.01' schema_version='v1.01' />
//		...
//	</tables>
//
function moss_core_checkDbTableVersions($moss) {
	//
	// Check access restrictions to checkAPIKey
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = moss_core_checkAccess($moss, 0, 'moss.core.checkDbTableVersions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbGetMOSSTables.php');
	$tables = moss_core_dbGetMOSSTables($moss);

	//
	// FIXME: If in multiple databases, this script will need to be updated.
	//

	$strsql = "SHOW TABLE STATUS";
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
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
			$tables[$table_name]['schema_version'] = $matches[1];
		}
	}

	return array('stat'=>'ok', 'tables'=>$tables);
}
?>
