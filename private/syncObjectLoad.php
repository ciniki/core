<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_core_syncObjectLoad(&$ciniki, &$sync, $business_id, $object_ref, $args) {

	//
	// Load the objects file
	//
	$obj = preg_split('/\./', $object_ref);
	$objects_filename = $ciniki['config']['ciniki.core']['root_dir'] . '/' . $obj[0] . '-mods/' . $obj[1] . '/sync/objects.php';
	if( file_exists($objects_filename) ) {
		require_once($objects_filename);
		$object_function = $obj[0] . '_' . $obj[1] . '_sync_objects';
		if( is_callable($object_function) ) {
			$rc = $object_function($ciniki, $sync, $business_id, $args);
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1180', 'msg'=>'Object does not exist'));
			}
			if( isset($rc['objects']) && isset($rc['objects'][$obj[2]]) ) {
				// Setup the object array
				$o = $rc['objects'][$obj[2]];
				$o['pmod'] = $obj[0] . '.' . $obj[1];
				$o['package'] = $obj[0];
				$o['module'] = $obj[1];
				$o['oname'] = $obj[2];
				
				return array('stat'=>'ok', 'object'=>$o);
			} else {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1181', 'msg'=>'Object does not exist'));
			}
		} else {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1182', 'msg'=>'Object does not exist'));
		}
	} 

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1183', 'msg'=>'Object does not exist'));
}
?>
