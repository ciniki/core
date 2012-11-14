<?php
//
// Description
// -----------
// This function will get the current table versions from the database comments sections
// and compare with what's in the .schema file.  If upgrades need to happen, then the
// ciniki.core.upgradeDb API call be made.
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
function ciniki_core_checkDbTableVersions($ciniki) {
	//
	// Check access restrictions to checkAPIKey
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
	$rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.checkDbTableVersions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetTables');
	$rc = ciniki_core_dbGetTables($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$tables = $rc['tables'];

	//
	// FIXME: If in multiple databases, this script will need to be updated.
	//

	$strsql = "SHOW TABLE STATUS";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.core', 'tables', 'Name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	foreach($rc['tables'] as $table_name => $table) {
		if( isset($tables[$table_name]) ) {
			if( preg_match('/(v[0-9]+\.[0-9]+)([^0-9]|$)/i', $table['Comment'], $matches) ) {
				$tables[$table_name]['database_version'] = $matches[1];
			}
		}
	}
	
	foreach($tables as $table_name => $table) {
		$schema = file_get_contents($ciniki['config']['core']['root_dir'] . '/' . $table['package'] . '-api/' . $table['module']	. "/db/$table_name.schema");
		if( preg_match('/comment=\'(v[0-9]+\.[0-9]+)\'/i', $schema, $matches) ) {
			$tables[$table_name]['schema_version'] = $matches[1];
		}
	}

	return array('stat'=>'ok', 'tables'=>$tables);
}
?>
