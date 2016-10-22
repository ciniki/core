<?php
//
// Description
// -----------
// This function will check if an object is used currently by any other modules.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business.
// object:          The name of the object to check.
// object_id:       The ID of the object to check for existence.
//
// Returns
// -------
//
function ciniki_core_objectCheckUsed(&$ciniki, $business_id, $object, $object_id) {
    
    //
    // Check to make sure the business modules were setup in the checkModuleAccess function
    //
    if( !isset($ciniki['business']['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1537', 'msg'=>'Internal Error', 'pmsg'=>'Missing the modules definition in settings'));
    }

    //
    // Request each module enabled by the business to see if the object is in use
    //
    $rsp = array('stat'=>'ok', 'used'=>'no', 'count'=>0, 'msg'=>'');

    foreach($ciniki['business']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'checkObjectUsed');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $business_id, array('object'=>$object, 'object_id'=>$object_id));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['used'] != 'no' ) {
                $rsp['used'] = $rc['used'];
                $rsp['count'] += $rc['count'];
                $rsp['msg'] .= ($rsp['msg'] != '' ? ' ' : '') . $rc['msg'];
            }
        }
    }

    return $rsp;
}
?>
