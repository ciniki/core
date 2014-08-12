<?php
//
// Description
// -----------
// This function will remove a file from the ciniki-storage system for a business.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_core_storageFileDelete(&$ciniki, $business_id, $obj_name, $args) {
	//
	// Break apart object name
	//
	list($pkg, $mod, $obj) = explode('.', $obj_name);

	if( !isset($args['uuid']) || $args['uuid'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1915', 'msg'=>'No uuid specified to remove from storage.'));
	}

	//
	// Get the business UUID
	//
	if( !isset($args['business_uuid']) ) {
		$strsql = "SELECT uuid FROM ciniki_businesses "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['business']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1923', 'msg'=>'Unable to get business details'));
		}
		$business_uuid = $rc['business']['uuid'];
	} else {
		$business_uuid = $args['business_uuid'];
	}

	//
	// remove the file from ciniki-storage
	//
	$storage_filename = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
		. $business_uuid[0] . '/' . $business_uuid
		. "/$pkg.$mod/"
		. $args['uuid'][0] . '/' . $args['uuid'];
	if( file_exists($storage_filename) ) {
		unlink($storage_filename);
	}

	return array('stat'=>'ok');
}
?>
