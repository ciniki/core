<?php
//
// Description
// -----------
// This function will fish for 'hooks' in other modules that should be called when
// the calling module function is called.  This allows other modules to update themselves
// based on actions from another modules. 
//
// The method is referred as fishing for hooks because the hooks are not defined ahead of time
// and must be found when the calling function runs.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business
// obj_name:        The calling object name.
// args:            The arguments array to be passed to the hook function.
//
// Returns
// -------
//
function ciniki_core_methodFishHooks(&$ciniki, $business_id, $obj, $args) {
    //
    // Check to make sure the business modules were setup in the checkModuleAccess function
    //
    if( !isset($ciniki['business']['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1537', 'msg'=>'Internal Error', 'pmsg'=>'Missing the modules definition in settings'));
    }

    //
    // Break apart object name
    //
    list($c_pkg, $c_mod, $c_obj) = explode('.', $obj);

    //
    // Check for modules hooks
    //
    foreach($ciniki['business']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        if( $c_pkg != $pkg ) { 
            // Skip any modules that aren't in the same package
            continue; 
        }   
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, $c_mod, $c_obj);
        if( $rc['stat'] != 'noexist' && $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1539', 'msg'=>'Internal error', 'pmsg'=>'Unable to load fish hook'));
        }
        if( $rc['stat'] == 'noexist' ) {
            continue;
        }
        $fn = $rc['function_call'];
        if( !is_callable($fn) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1538', 'msg'=>'Internal Error', 'pmsg'=>'Unable to call fish hook, function does not exist'));;
        }
        $rc = $fn($ciniki, $args['business_id'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
