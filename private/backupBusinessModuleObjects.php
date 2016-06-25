<?php
//
// Description
// -----------
// This method will backup a business to the ciniki-backups folder
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business on the local side to check sync.
//
//
function ciniki_core_backupBusinessModuleObjects(&$ciniki, $business, $pkg, $mod) {
    $module = $pkg . '.' . $mod;

    //
    // Setup the backup directory for this module
    //
    $backup_dir = $business['backup_dir'] . '/' . $module . '/_objects';
    if( !file_exists($backup_dir) ) {
        if( mkdir($backup_dir, 0755, true) === false ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1729', 'msg'=>'Unable to create backup directory for ' . $module));
        }
    }

    //
    // Load the object definitions for the module
    //
    if( isset($ciniki['objects'][$pkg][$mod]) ) {
        $objects = $ciniki['objects'][$pkg][$mod];
    } else {
        $method_filename = $ciniki['config']['ciniki.core']['root_dir'] . "/$pkg-mods/$mod/private/objects.php";
        $method_function = "{$pkg}_{$mod}_objects";
        if( !file_exists($method_filename) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1730', 'msg'=>'Unable to load object definitions for: ' . $pkg . '.' . $mod));
        }

        require_once($method_filename);
        if( !is_callable($method_function) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1731', 'msg'=>'Unable to load object definitions for: ' . $pkg . '.' . $mod));
        }

        $rc = $method_function($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1732', 'msg'=>'Unable to load object definitions for: ' . $pkg . '.' . $mod));
        }
        if( !isset($rc['objects']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1733', 'msg'=>'Unable to load object definitions for: ' . $pkg . '.' . $mod));
        }
        $objects = $rc['objects'];
    
        // Update the cache of object definitions
        $ciniki['objects'][$pkg][$mod] = $objects;
    }

    //
    // Get the list of object identifiers
    //
    foreach($objects as $oid => $object) {
        if( isset($object['backup']) && $object['backup'] == 'yes' ) {
            //
            // FIXME: Backup the object
            //
            $rc = ciniki_core_backupBusinessModuleObject($ciniki, $business, $pkg, $mod, $obj);
            if( $rc['stat'] != 'ok' ) {
                error_log('BACKUP-ERR[' . $business['name'] . ']: ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
            }
        }
    }

    return array('stat'=>'ok');
}
?>
