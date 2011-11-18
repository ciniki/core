<?php
//
// Description
// -----------
// This function will upgrade the tables to the current versions in 
// the ciniki-modules directory.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// 
//
//
function ciniki_core_dbUpgradeTable($ciniki, $package, $module, $table, $old_version, $new_version) {

	ciniki_core_loadMethod('ciniki', 'core', 'private', 'dbConnect');
	ciniki_core_loadMethod('ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbConnect($ciniki, $module);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if the table exists
	//
	if( $old_version == '-' ) {
		$schema = file_get_contents($ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . "/db/$table.schema");
		$rc = ciniki_core_dbUpdate($ciniki, $schema, $module);
		return $rc;
	}

	//
	// Check for upgrade files
	//
	$old_major = '';
	$old_minor = '';
	if( preg_match('/v([0-9]+)\.([0-9]+)$/', $old_version, &$matches) ) {
		$old_major = $matches[1];
		$old_minor = $matches[2];
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'191', 'msg'=>"Unrecognized old table version: $old_version"));
	}

	$new_major = '';
	$new_minor = '';
	if( preg_match('/v([0-9])+\.([0-9]+)$/', $new_version, &$matches) ) {
		$new_major = $matches[1];
		$new_minor = $matches[2];
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'192', 'msg'=>"Unrecognized new table version: $new_version"));
	}

	for($i=$old_major;$i<=$new_major;$i++) {
		//
		// Decide where to begin and end the minor number search.  This allows
		// for upgrades through major versions
		//
		if( $old_major == $new_major ) {
			$start_minor = $old_minor;
			$end_minor = $new_minor;
		} elseif( $i == $old_major ) {
			$start_minor = $old_minor;
			$end_minor = 99;
		} elseif( $i == $new_major ) {
			$start_minor = 0;
			$end_minor = $new_minor;
		}

		error_log("Upgrading table from: $i.$start_minor to $i.$end_minor");
		for($j=$start_minor+1;$j<=$end_minor;$j++) {
			$filename = $ciniki['config']['core']['root_dir'] . sprintf("/$package/$module/db/$table.$i.%02d.upgrade", $j);
			if( file_exists($filename) ) {
				$schema = file_get_contents($filename);
				$sqls = preg_split('/;\s*$/m', $schema);
				foreach($sqls as $strsql) {
					if( preg_match('/ALTER TABLE/', $strsql) 
						|| preg_match('/DROP INDEX/', $strsql)
						|| preg_match('/CREATE INDEX/', $strsql)
						|| preg_match('/UPDATE /', $strsql)
						) {
						$rc = ciniki_core_dbUpdate($ciniki, $strsql, $module);
						if( $rc['stat'] != 'ok' ) {
							return $rc;
						}
					}
				}
			}
		}
	}


	//
	// If old and new majors are different
	//
//	if( $old_major < $new_major ) {
//	}

	//
	// old and new major numbers the same
	//
//	elseif( $old_major == $new_major ) {
//		for($j=$old_minor;$j<$new_minor;$j++) {
//			$filename = $ciniki['config']['core']['modules_dir'] . sprintf("/$module/db/$table.$new_major.%02d.upgrade", $j);
//			if( file_exists($filename) ) {
//				$schema = file_get_contents($filename);
//				$rc = ciniki_core_dbUpdate($ciniki, $schema, $module);
//				if( $rc['stat'] != 'ok' ) {
//					return $rc;
//				}
//			}
//		}
//	}	
//	else {	
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'193', 'msg'=>"Old $table version $old_version is newer than $new_version, unable to upgrade"));
//	}

	return $rc;
}
?>
