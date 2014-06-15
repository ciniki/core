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
function ciniki_core_objectGetSubs(&$ciniki, $business_id, $obj_name, $oid, $sub_obj_name) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
	//
	// Break apart object name
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);
	list($s_pkg, $s_mod, $s_obj) = explode('.', $sub_obj_name);

	//
	// Load the object file
	//
	$rc = ciniki_core_objectLoad($ciniki, $obj_name);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$o = $rc['object'];
	$m = "$pkg.$mod";

	$rc = ciniki_core_objectLoad($ciniki, $sub_obj_name);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$s_o = $rc['object'];
	$s_m = "$pkg.$mod";


	// 
	// Build the query to get the object
	//
	$strsql = "SELECT id ";
	$fields = array();
	$obj_strsql = '';
	foreach($s_o['fields'] as $field => $options) {
		$strsql .= ", " . $field . " ";
		if( isset($options['ref']) && $options['ref'] == $obj_name ) {
			$obj_strsql = "AND $field = '" . ciniki_core_dbQuote($ciniki, $oid) . "' ";	
		}
	}
	$strsql .= "FROM " . $s_o['table'] . " "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. $obj_strsql
		. "";
	if( isset($s_o['listsort']) && $s_o['listsort'] != '' ) {
		$strsql .= "ORDER BY " . $s_o['listsort'] . " ";
	}
	$container = isset($s_o['o_container'])?$s_o['o_container']:'objects';
	$name = isset($s_o['o_name'])?$s_o['o_name']:'object';
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, $s_m, array(
		array('container'=>$container, 'fname'=>'id', 'name'=>$name,
			'fields'=>array_merge(array('id'), array_keys($s_o['fields']))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp = array('stat'=>'ok');
	if( isset($rc[$container]) ) {
		$rsp[$container] = $rc[$container];
	} else {
		$rsp[$container] = array();
	}

	return $rsp;
}
?>
