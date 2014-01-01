<?php
//
// Description
// -----------
// This function will load the object definition.
//
// Arguments
// ---------
// ciniki:
// pkg:			The package the object is a part of
// mod:			The module in the package
// obj:			The object in the module.
//
// Returns
// -------
//
function ciniki_core_objectLoad(&$ciniki, $obj_name) {
	//
	// Break apart object name
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);

	if( isset($ciniki['objects'][$pkg][$mod][$obj]) ) {
		return array('stat'=>'ok', 'object'=>$ciniki['objects'][$pkg][$mod][$obj]);
	}

	//
	// Load the objects for this module
	//
	$method_filename = $ciniki['config']['ciniki.core']['root_dir'] . "/$pkg-mods/$mod/private/objects.php";
	$method_function = "{$pkg}_{$mod}_objects";
	if( !file_exists($method_filename) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1372', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
	}

	require_once($method_filename);
	if( !is_callable($method_function) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1373', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
	}

	$rc = $method_function($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1374', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
	}
	if( !isset($rc['objects']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1375', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
	}
	$objects = $rc['objects'];

	if( !isset($objects[$obj]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1376', 'msg'=>'Unable to load object definition: ' . $pkg . '.' . $mod . '.' . $obj));
	}

	//
	// Store the loaded object, so it only needs to be loaded once
	//
	if( !isset($ciniki['objects']) ) {
		$ciniki['objects'] = array($pkg=>array($mod=>$objects));
	} elseif( !isset($ciniki['objects'][$pkg]) ) {
		$ciniki['objects'][$pkg] = array($mod=>$objects);
	} elseif( !isset($ciniki['objects'][$pkg][$mod]) ) {
		$ciniki['objects'][$pkg][$mod] = $objects;
	}
	
	return array('stat'=>'ok', 'object'=>$objects[$obj]);
}
?>
