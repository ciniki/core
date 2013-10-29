<?php
//
// Description
// -----------
// This method will echo back the arguments sent.  This function is
// for simple testing, similar to ping in network tests.
//
// Info
// ----
// status:			beta
// 
// Arguments
// ---------
// api_key:			
// *args:			Any additional arguments passed to the method will be returned in the response.
//
// Returns
// -------
// <rsp stat='ok' 'date'='2011-06-09 20:26' />
//
function ciniki_core_parseDatetime($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'datetime'=>array('required'=>'no', 'default'=>'', 'blank'=>'no', 'type'=>'datetime', 'name'=>'Date'), 
		));
    $args = $rc['args'];

	//
	// Check access restrictions to checkAPIKey
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
	$rc = ciniki_core_checkAccess($ciniki, 0, 'ciniki.core.parseDatetime');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$rsp = array('stat'=>'ok', 'datetime'=>$args['datetime']);

	if( $args['datetime'] != '' ) {
		$dt = strtotime($args['datetime']);
		$rsp['date'] = date('Y-m-d', $dt);
		$rsp['year'] = date('Y', $dt);
		$rsp['month'] = date('m', $dt);
		$rsp['day'] = date('d', $dt);
		$rsp['hour'] = date('H', $dt);
		$rsp['minute'] = date('i', $dt);
	}

	return $rsp;
}
?>
