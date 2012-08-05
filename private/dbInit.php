<?php
//
// Description
// -----------
// This function will initialize the database structure for the $ciniki variable.
//
// Info
// ----
// Status: 		beta
//
// Arguments
// ---------
// ciniki:
//
//
function ciniki_core_dbInit(&$ciniki) {

	$ciniki['databases'] = array();

	if( !isset($ciniki['config']['core']['database.names']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'10', 'msg'=>'Internal configuration error', 'pmsg'=>'missing core.database.name from config.'));
	}

	$databases = preg_split('/\s*\,\s*/', $ciniki['config']['core']['database.names']);

	foreach($databases as $db) {
		$ciniki['databases'][$db] = array();
	}

	//
	// Check if core database has been defined
	//
	if( !isset($ciniki['databases']['core']) || !is_array($ciniki['databases']['core']) ) {
		$ciniki['databases']['core'] = array();
	}

	require_once($ciniki['config']['core']['modules_dir'] . "/core/private/dbConnect.php");

	//
	// Connect to the core, we ALWAYS need this connection, might as well open it now
	// and verify it's working before going further in code
	//
	$rc = ciniki_core_dbConnect($ciniki, 'ciniki.core');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
