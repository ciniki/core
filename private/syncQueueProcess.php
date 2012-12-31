<?php
//
// Description
// -----------
// This function will execute the queue requests for the sync.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business on the local side to check sync.
// sync_id:			The ID of the sync to check compatibility with.
//
function ciniki_core_syncQueueProcess($ciniki) {
	//  
	// Find all the required and optional arguments
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Get the list of push syncs for this business, 
	// and then execute all the queue process on each sync.
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.uuid AS local_uuid, ciniki_business_syncs.flags, local_private_key, "
		. "remote_name, remote_uuid, remote_url, remote_public_key, UNIX_TIMESTAMP(last_sync) AS last_sync "
		. "FROM ciniki_businesses, ciniki_business_syncs "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_businesses.id = ciniki_business_syncs.business_id "
		. "AND (ciniki_business_syncs.flags&0x01) = 0x01 "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'syncs', 'id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( isset($rc['syncs']) ) {
		foreach($rc['syncs'] as $sync_id => $sync) {
			$sync['type'] = 'business';
			foreach($ciniki['syncqueue'] as $queue_item) {
				$method_filename = $ciniki['config']['ciniki.core']['root_dir'] . preg_replace('/^(.*)\.(.*)\.(.*)$/','/\1-api/\2/private/\3.php', $queue_item['method']);
				$method_function = preg_replace('/^(.*)\.(.*)\.(.*)$/','\1_\2_\3', $queue_item['method']);
				if( file_exists($method_filename) ) {
					require_once($method_filename);
					if( is_callable($method_function) ) {
//						error_log("Execute sync: " . print_r($queue_item, true));
						$rc = $method_function($ciniki, $sync, $args['business_id'], $queue_item['args']);
						if( $rc['stat'] != 'ok' ) {
							error_log("ERR: executing syncqueue: " . print_r($queue_item, true) . print_r($rc['err'], true));
							continue;
						}
					} else {
						error_log("ERR: Unable to call syncqueue: $method_function");
						continue;
					}
				} else {
					error_log("ERR: Unable to run syncqueue: " . print_r($queue_item, true));
					continue;
				}
			}
		}
	}

	return array('stat'=>'ok');
}
?>
