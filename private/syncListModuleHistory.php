<?php
//
// Description
// -----------
// This function will update the history elements.
//
// Arguments
// ---------
//
function ciniki_core_syncListModuleHistory(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['type']) ||
		($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'266', 'msg'=>'No type specified'));
	}
	if( $args['type'] == 'incremental' 
		&& (!isset($args['since_uts']) || $args['since_uts'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'267', 'msg'=>'No timestamp specified'));
	}
	if( !isset($args['history_table']) || $args['history_table'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1085', 'msg'=>'No history table specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');

	//
	// Prepare the query to fetch the list
	//
	$history_table = $args['history_table'];
	$strsql = "SELECT uuid, UNIX_TIMESTAMP(log_date) AS log_date "	
		. "FROM $history_table "
		. "WHERE $history_table.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( $args['type'] == 'incremental' ) {
		$strsql .= "AND UNIX_TIMESTAMP($history_table.log_date) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
	}
	$strsql .= "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.customers', 'history');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'268', 'msg'=>'Unable to get history list', 'err'=>$rc['err']));
	}

	if( !isset($rc['history']) ) {
		return array('stat'=>'ok', 'list'=>array());
	}

	return array('stat'=>'ok', 'list'=>$rc['history']);
}
?>
