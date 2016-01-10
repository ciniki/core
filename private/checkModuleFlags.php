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
function ciniki_core_checkModuleFlags($ciniki, $module, $flags) {
    if( isset($ciniki['business']['modules'][$module]['flags']) && ($ciniki['business']['modules'][$module]['flags']&$flags) > 0 ) {
        return true;
    }
	return false;
}
?>
