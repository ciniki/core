<?php
//
// Description
// -----------
// This function will dynamically load a method into PHP.
//
// Arguments
// ---------
// ciniki:      The ciniki internal variable.
// package:     The package the method belongs to
// module:      The package module the method is part of.
// type:        The type of method (public, private).
// name:        The name of the function.
//
function ciniki_core_hookExec($ciniki, $tnid, $package, $module, $name, $args) {
    $type = 'hooks';
    if( !file_exists($ciniki['config']['ciniki.core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $name . '.php') ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.core.93', 'msg'=>'Internal Error', 'pmsg'=>'Requested method does not exist'));
    }

    require_once($ciniki['config']['ciniki.core']['root_dir'] . '/' . $package . '-mods/' . $module . '/' . $type . '/' . $name . '.php');

    $fn = $package . '_' . $module . '_hooks_' . $name;

    return $fn($ciniki, $tnid, $args);
}
?>
