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
function moss_core_parseDatetime($moss) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($moss['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = moss_core_prepareArgs($moss, 'no', array(
        'datetime'=>array('required'=>'no', 'default'=>'', 'blank'=>'no', 'type'=>'datetime', 'errmsg'=>'No date specified'), 
		));
    $args = $rc['args'];

	//
	// Check access restrictions to checkAPIKey
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/checkAccess.php');
	$rc = moss_core_checkAccess($moss, 0, 'moss.core.parseDatetime');
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
