<?php
//
// Description
// -----------
// This method will backup a business to the ciniki-backups folder
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
//
//
function ciniki_core_backupBusiness(&$ciniki, $business) {

	//
	// Check the backup directory exists
	//
	$zip_backup_dir = $ciniki['config']['ciniki.core']['backup_dir'] . '/'
		. $business['uuid'][0] . '/' . $business['uuid'];
	$business['backup_dir'] = $ciniki['config']['ciniki.core']['backup_dir'] . '/'
		. $business['uuid'][0] . '/' . $business['uuid'] . '/data';
	if( !file_exists($business['backup_dir']) ) {
		if( mkdir($business['backup_dir'], 0755, true) === false ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1727', 'msg'=>'Unable to create backup directory'));
		}
	}
	
	//
	// Get the list of modules for the business
	//
	$strsql = "SELECT package, module, flags "
		. "FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
		. "AND status > 0 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'module');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$modules = $rc['rows'];

	$skip_modules = array('ciniki.businesses', 
		'ciniki.calendars', 
		'ciniki.systemdocs', 
		'ciniki.cron', 
		'ciniki.newsaggregator',
		'ciniki.mail',
		'ciniki.filedepot',
		'ciniki.services',
		);

	//
	// Backup the modules
	//
	foreach($modules as $mod) {
		
		if( in_array($mod['package'] . '.' . $mod['module'], $skip_modules) ) {
			continue;
		}
/*
		//
		// Backup the objects in XML format first.
		// Check if there is a custom backup for this module, otherwise use the default backup.
		//
		$rc = ciniki_core_loadMethod($ciniki, $mod['package'], $mod['module'], 'private', 'backupBusinessModuleObjects');
		if( $rc['stat'] == 'ok' ) {
			$fn = $rc['function_call'];
			$rc = $fn($ciniki, $business);
			if( $rc['stat'] != 'ok' ) {
				error_log('BACKUP-ERR[' . $business['name'] . ']: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
			}
		} else {
			//
			// Backup the module
			//
			$rc = ciniki_core_backupBusinessModuleObjects($ciniki, $business, $mod['package'], $mod['module']);
			if( $rc['stat'] != 'ok' ) {
				error_log('BACKUP-ERR[' . $business['name'] . ']: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
			}
		}
*/
		//
		// Check if there is another backup script for the module to create a human readable backup
		//
		$rc = ciniki_core_loadMethod($ciniki, $mod['package'], $mod['module'], 'private', 'backupModule');
		if( $rc['stat'] == 'ok' ) {
			$fn = $mod['package'] . '_' . $mod['module'] . '_backupModule';
			$rc = $fn($ciniki, $business);
			if( $rc['stat'] != 'ok' ) {
				error_log('BACKUP-ERR[' . $business['name'] . ']: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
			}
		}	
	}

	//
	// Create the zip file
	//
	date_default_timezone_set('UTC');
	$date = date('Ymd-Hi');
	$zip = new ZipArchive;
	if( $zip->open($zip_backup_dir . "/backup-$date.zip", ZipArchive::CREATE) === TRUE ) {
		$rc = ciniki_core_backupZipAddDir($ciniki, $zip, $business['backup_dir'], "/backup-$date");
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$zip->close();
	} else {
		error_log($zip->getStatusString());
		error_log('BACKUP-ERR[' . $business['name'] . ']: Unable to create zip file: ' . $zip_backup_dir . '/backup.zip');
	}
	$cur_date = $date;

	//
	// Clear old zip files
	//
	$dh = opendir($zip_backup_dir);
	$today = date('Ymd');
	$today_datetime = strtotime($today);
	while( ($file = readdir($dh)) !== false ) {
		if( $file == "backup-$cur_date.zip" ) {
			continue;
		}
		if( preg_match("/backup-$today-.*zip/", $file) ) {
			unlink($zip_backup_dir . '/' . $file);
		}
		if( preg_match("/backup-([0-9]+)-([0-9]+)/", $file, $matches) ) {
			$file_date = strtotime($matches[1]);
			if( ($today_datetime - $file_date) > 604800 ) {
				unlink($zip_backup_dir . '/' . $file);
			}
		}
	}
	closedir($dh);

	return array('stat'=>'ok');
}
?>
