<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_core_objectRefAdd(&$ciniki, $business_id, $obj_name, $args, $options=0x07) {
	//
	// Break apart object name
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);

	//
	// Check for self referencing
	//
	if( "$pkg.$mod.ref" == $args['object']) {
		return array('stat'=>'ok');
	}

	//
	// Check if there is a reference table for module being referred to
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
	$rc = ciniki_core_objectLoad($ciniki, $pkg . '.' . $mod . '.ref');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'ok');
	}

	$o = $rc['object'];
	$m = "$pkg.$mod";

	//
	// Check to make sure all variable required were passed
	//
	if( !isset($args['ref_id']) || $args['ref_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1362', 'msg'=>'No ref specified'));
	}
	if( !isset($args['object']) || $args['object'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1361', 'msg'=>'No object specified'));
	}
	if( !isset($args['object_id']) || $args['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1359', 'msg'=>'No object ID specified'));
	}
	if( !isset($args['object_field']) || $args['object_field'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1358', 'msg'=>'No object field specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $business_id, "$pkg.$mod.ref", $args, $options);
}
?>
