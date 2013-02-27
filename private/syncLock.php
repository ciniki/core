<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
//
function ciniki_core_syncLock($ciniki, $business_id, $sync_id) {

	if( !isset($ciniki['config']['ciniki.core']['sync.lock_dir']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'281', 'msg'=>'No sync lock dir specified'));
	}

	$lockfile = $ciniki['config']['ciniki.core']['sync.lock_dir'] . '/sync-' . $sync_id . '.lck';

	if( file_exists($lockfile) ) {
		return array('stat'=>'lockexists');
	}

	if( touch($lockfile) == false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'282', 'msg'=>'Unable to set sync lock'));
	}

	return array('stat'=>'ok');
}
?>
