<?php
//
// Description
// -----------
// This function will return true or false based on the flags passed.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_core_checkModuleActive($ciniki, $module) {
    if( isset($ciniki['tenant']['modules'][$module]['module_status']) 
        && $ciniki['tenant']['modules'][$module]['module_status'] ==  1 
        ) {
        return true;
    }
    return false;
}
?>
