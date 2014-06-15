<?php
//
// Description
// -----------
// This function will retrieve an object to the database.
//
// Arguments
// ---------
// ciniki:
// pkg:			The package the object is a part of.
// mod:			The module the object is a part of.
// obj:			The name of the object in the module.
// args:		The arguments passed to the API.
//
// Returns
// -------
//
function ciniki_core_objectGet(&$ciniki, $business_id, $obj_name, $oid) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');

	//
	// Break apart object name
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);

	//
	// Load the object file
	//
	$rc = ciniki_core_objectLoad($ciniki, $obj_name);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$o = $rc['object'];
	$m = "$pkg.$mod";

	// 
	// Build the query to get the object
	//
	$strsql = "SELECT id ";
	$fields = array();
	foreach($o['fields'] as $field => $options) {
		$strsql .= ", " . $field . " ";
//		if( isset($field['ref']) && $field['ref'] == $obj_name ) {
//			$obj_strsql = "AND $field = '" . ciniki_core_dbQuote($ciniki, $oid) . "' ";	
//		}
	}
	$strsql .= "FROM " . $o['table'] . " "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$container = isset($o['o_container'])?$o['o_container']:'objects';
	$name = isset($o['o_name'])?$o['o_name']:'object';
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, $pkg . '.' . $mod, array(
		array('container'=>$container, 'fname'=>'id',
			'fields'=>array_keys($o['fields'])),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc[$container][$oid]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1757', 'msg'=>"Unable to load the " . lowercase($o['name']) . " you requested."));
	}
	$object = $rc[$container][$oid];

	$rsp = array('stat'=>'ok');
	$rsp[$name] = $object;

	return $rsp;
}
?>
