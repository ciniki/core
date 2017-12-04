<?php
//
// Description
// -----------
// This method will backup a tenant to the ciniki-backups folder
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
//
//
function ciniki_core_backupTenant(&$ciniki, $tenant) {

    //
    // Check the backup directory exists
    //
    if( isset($ciniki['config']['ciniki.core']['zip_backup_dir']) ) {
        $zip_backup_dir = $ciniki['config']['ciniki.core']['zip_backup_dir'] . '/'
            . $tenant['uuid'][0] . '/' . $tenant['uuid'];
    } else {
        $zip_backup_dir = $ciniki['config']['ciniki.core']['backup_dir'] . '/'
            . $tenant['uuid'][0] . '/' . $tenant['uuid'];
    }
    if( isset($ciniki['config']['ciniki.core']['final_backup_dir']) ) {
        $final_backup_dir = $ciniki['config']['ciniki.core']['final_backup_dir'] . '/'
            . $tenant['uuid'][0] . '/' . $tenant['uuid'];
    }
    $tenant['backup_dir'] = $ciniki['config']['ciniki.core']['backup_dir'] . '/'
        . $tenant['uuid'][0] . '/' . $tenant['uuid'] . '/data';
    if( !file_exists($tenant['backup_dir']) ) {
        if( mkdir($tenant['backup_dir'], 0755, true) === false ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.9', 'msg'=>'Unable to create backup directory'));
        }
    }
    if( !file_exists($zip_backup_dir) ) {
        if( mkdir($zip_backup_dir, 0755, true) === false ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.10', 'msg'=>'Unable to create backup directory'));
        }
    }
    
    //
    // Get the list of modules for the tenant
    //
    $strsql = "SELECT package, module, flags "
        . "FROM ciniki_tenant_modules "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "AND status > 0 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'module');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['rows'];

    $skip_modules = array('ciniki.tenants', 
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
        $rc = ciniki_core_loadMethod($ciniki, $mod['package'], $mod['module'], 'private', 'backupTenantModuleObjects');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tenant);
            if( $rc['stat'] != 'ok' ) {
                error_log('BACKUP-ERR[' . $tenant['name'] . ']: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
            }
        } else {
            //
            // Backup the module
            //
            $rc = ciniki_core_backupTenantModuleObjects($ciniki, $tenant, $mod['package'], $mod['module']);
            if( $rc['stat'] != 'ok' ) {
                error_log('BACKUP-ERR[' . $tenant['name'] . ']: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
            }
        }
*/
        //
        // Check if there is another backup script for the module to create a human readable backup
        //
        $rc = ciniki_core_loadMethod($ciniki, $mod['package'], $mod['module'], 'private', 'backupModule');
        if( $rc['stat'] == 'ok' ) {
            $fn = $mod['package'] . '_' . $mod['module'] . '_backupModule';
            $rc = $fn($ciniki, $tenant);
            if( $rc['stat'] != 'ok' ) {
                error_log('BACKUP-ERR[' . $tenant['name'] . ']: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
            }
        }   
    }

    //
    // Close database connections
    //
    foreach($ciniki['databases'] as $db_name => $db) {
        if( isset($db['connection']) ) {
            mysqli_close($db['connection']);
            unset($ciniki['databases'][$db_name]['connection']);
        }
    }


    //
    // Create the zip file
    //
    date_default_timezone_set('UTC');
    $date = date('Ymd-Hi');
    $zip = new ZipArchive;
    if( $zip->open($zip_backup_dir . "/backup-$date.zip", ZipArchive::CREATE) === TRUE ) {
        $rc = ciniki_core_backupZipAddDir($ciniki, $zip, $tenant['backup_dir'], "/backup-$date");
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $zip->close();
    } else {
        error_log($zip->getStatusString());
        error_log('BACKUP-ERR[' . $tenant['name'] . ']: Unable to create zip file: ' . $zip_backup_dir . '/backup.zip');
    }
    
    //
    // If there is a final spot for backups, then move the zip file
    // Note: Due to sshfs mount problems at rackspace, the following code was added to create backups on local storage and then copy via sftp.
    //       In ciniki-api.ini setup final_backup_dir = ssh2.sftp://user:pass@ftpserver:22/fullpathtobackups
    //       Must have libssh2-php installed as a package on server
    //
    if( isset($final_backup_dir) ) {
        if( !file_exists($final_backup_dir) ) {
            if( mkdir($final_backup_dir, 0755, true) === false ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.11', 'msg'=>'Unable to create backup directory: ' . $final_backup_dir));
            }
        }
        copy("$zip_backup_dir/backup-$date.zip", "$final_backup_dir/backup-$date.zip");
        unlink("$zip_backup_dir/backup-$date.zip");
    }


    $cur_date = $date;

    //
    // Clear old zip files
    //
    if( isset($final_backup_dir) ) {
        $zip_backup_dir = $final_backup_dir;
    }
    $dh = opendir($zip_backup_dir);
    $today = date('Ymd');
    $today_datetime = strtotime($today);
    while( ($file = readdir($dh)) !== false ) {
        if( $file == "backup-$cur_date.zip" ) {
            continue;
        }
        //
        // Clear other backups from the same day.
        //
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
