<?php
//
// Description
// -----------
// This function will search the list of available canadian addresses
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_core_mailingAddressLookup(&$ciniki) {

    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'search'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAccess');
    $rc = ciniki_core_checkAccess($ciniki, $args['tnid'], 'ciniki.core.mailingAddressLookup');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Run the lookup
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'mailingAddressLookup');
    return ciniki_core__mailingAddressLookup($ciniki, $args['tnid'], $args['search']);
}
?>
