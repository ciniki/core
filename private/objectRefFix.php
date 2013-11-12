<?php
//
// Description
// -----------
// This function will go through all the references for an object, and make sure they are correct.
// It will remove any references in images that no long reference to an existing object in artcatalog,
// and add any missing references.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business the reference is for.
//
// args:			The arguments for adding the reference.
//
// 					object - The object that is referring to the object.
// 					object_id - The ID of the object that is referrign to the object.
//
// Returns
// -------
// <rsp stat="ok" id="45" />
//
function ciniki_core_objectRefFix(&$ciniki, $business_id, $obj_name, $options=0) {
	//
	// Break apart object name
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);

	//
	// Check if there is a reference table for module being referred to
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefClear');
	$rc = ciniki_core_objectLoad($ciniki, $obj_name);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'ok');
	}

	$o = $rc['object'];
	$m = "$pkg.$mod";

	//
	// Check any fields that have references
	//

	if( isset($o['fields']) ) {
		foreach($o['fields'] as $fname => $field) {
			if( isset($field['ref']) && $field['ref'] != '' ) {
				list($ref_pkg, $ref_mod, $ref_obj) = explode('.', $field['ref']);

				$rc = ciniki_core_objectLoad($ciniki, $ref_pkg . '.' . $ref_mod . '.ref');
				if( $rc['stat'] != 'ok' ) {
					continue;
				}
				$ref_o = $rc['object'];	
				$ref_m = "$ref_pkg.$ref_mod";

				//
				// Load the references to local object
				//
				$strsql = "SELECT id, uuid, ref_id, object_id "
					. "FROM " . $ref_o['table'] . " "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND object = '" . ciniki_core_dbQuote($ciniki, $obj_name) . "' "
					. "AND object_field = '" . ciniki_core_dbQuote($ciniki, $fname) . "' "
					. "";
				$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, $m, 'refs', 'object_id');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$refs = $rc['refs'];

				//
				// Load the local objects
				//
				$strsql = "SELECT id, $fname "
					. "FROM " . $o['table'] . " "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND $fname > 0 "
					. "";
				$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, $m, 'objects', 'id');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$objs = $rc['objects'];
				
				//
				// Check to make sure the references still exist in the 
				// local table and haven't been deleted
				//
				foreach($refs as $oid => $ref) {
					if( !isset($objs[$oid]) ) {
//						error_log("Removing reference " . $ref['id'] . ' from ' . $ref_o['table']);
						$rc = ciniki_core_objectRefClear($ciniki, $business_id, $field['ref'], array(
							'object'=>$obj_name,
							'object_id'=>$oid
							), $options);
						if( $rc['stat'] != 'ok' ) {
							return $rc;
						}
					}
				}

				//
				// Check to make sure the reference exists for the local object
				//
				foreach($objs as $oid => $object) {
					if( !isset($refs[$oid]) ) {
//						error_log('Add reference ' . $object[$fname] . ',' . $obj_name . ',' . $oid . ',' . $fname);
						$rc = ciniki_core_objectRefAdd($ciniki, $business_id, $field['ref'], array(
							'ref_id'=>$object[$fname],
							'object'=>$obj_name,
							'object_id'=>$oid,
							'object_field'=>$fname,
							), $options);
						if( $rc['stat'] != 'ok' ) {
							return $rc;
						}
					}
				}
			}
		}
	}

	if( isset($o['refs']) ) {
		foreach($o['refs'] as $fname => $field) {
			if( isset($field['ref']) && $field['ref'] != '' ) {
				list($ref_pkg, $ref_mod, $ref_obj) = explode('.', $field['ref']);

				$rc = ciniki_core_objectLoad($ciniki, $ref_pkg . '.' . $ref_mod . '.ref');
				if( $rc['stat'] != 'ok' ) {
					continue;
				}
				$ref_o = $rc['object'];	
				$ref_m = "$ref_pkg.$ref_mod";

				//
				// Load the references to local object
				//
				$strsql = "SELECT id, uuid, ref_id, object_id "
					. "FROM " . $ref_o['table'] . " "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND object = '" . ciniki_core_dbQuote($ciniki, $obj_name) . "' "
					. "AND object_id = '" . ciniki_core_dbQuote($ciniki, $fname) . "' "
					. "";
				$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, $m, 'refs', 'object_id');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$refs = $rc['refs'];

				//
				// Load the objects
				//
				$strsql = "SELECT detail_key, detail_value "
					. "FROM " . $o['table'] . " "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $fname) . "' "
					. "AND detail_value <> '' "
					. "AND detail_value > 0 "
					. "";
				$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, $m, 'objects', 'detail_key');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$objs = $rc['objects'];

				//
				// Check to make sure the references still exist in the 
				// local table and haven't been deleted
				//
				foreach($refs as $oid => $ref) {
					if( !isset($objs[$oid]) ) {
//						error_log("Removing reference " . $ref['id'] . ' from ' . $ref_o['table']);
						$rc = ciniki_core_objectRefClear($ciniki, $business_id, $field['ref'], array(
							'object'=>$obj_name,
							'object_id'=>$oid
							), $options);
						if( $rc['stat'] != 'ok' ) {
							return $rc;
						}
					}
				}

				//
				// Check to make sure the reference exists for the local object
				//
				foreach($objs as $oid => $object) {
					if( !isset($refs[$oid]) ) {
//						error_log('Add reference ' . $object['detail_value'] . ',' . $obj_name . ',' . $oid . ',' . $fname);
						$rc = ciniki_core_objectRefAdd($ciniki, $business_id, $field['ref'], array(
							'ref_id'=>$object['detail_value'],
							'object'=>$obj_name,
							'object_id'=>$oid,
							'object_field'=>'detail_value',
							), $options);
						if( $rc['stat'] != 'ok' ) {
							return $rc;
						}
					}
				}
			}
		}
	}

	return array('stat'=>'ok');
}
?>
