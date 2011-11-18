<?php
//
// Description
// -----------
// This function will dynamically load a method into PHP.
//
// Arguments
// ---------
// ciniki:		The ciniki internal variable.
// package:		The package the method belongs to
// module:		The package module the method is part of.
// type:		The type of method (public, private).
// name:		The name of the function.
//
function ciniki_core_loadMethod($ciniki, $package, $module, $type, $name) {
	require_once($ciniki['config']['core']['root_dir'] . '/' . $package . '-api/' . $module . '/' . $type . '/' . $name . '.php');
}
?>
