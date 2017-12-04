<?php
//
// Description
// -----------
// This function will load and return the objects for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
// module:          The module to sync.
//
function ciniki_core_syncModuleObjects(&$ciniki, $tnid, $module, $type) {
    //
    // Load the objects for this module
    //
    $method_filename = $ciniki['config']['ciniki.core']['root_dir'] . preg_replace('/^(.*)\.(.*)$/', '/\1-mods/\2/sync/objects.php', $module);
    $method_function = preg_replace('/^(.*)\.(.*)$/', '\1_\2_sync_objects', $module);
    if( !file_exists($method_filename) ) {
        // 
        // No sync or objects defined for this module, skip
        //
        return array('stat'=>'ok');
    }

    require_once($method_filename);
    if( !is_callable($method_function) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.222', 'msg'=>'Unable to sync module: ' . $module));
    }

    $rc = $method_function($ciniki, $sync, $tnid, array('type'=>$type));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.223', 'msg'=>'Unable to sync module: ' . $module, 'err'=>$rc['err']));
    }
    if( !isset($rc['objects']) ) {
        //
        // If no objects specified, then nothing to sync from this module
        //
        return array('stat'=>'ok');
    }
    $objects = $rc['objects'];
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    } else {
        $settings = array();
    }
    
    return array('stat'=>'ok', 'objects'=>$objects, 'settings'=>$settings);
}
?>
