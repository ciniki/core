<?php
//
// Description
// -----------
// Lookup the mailing address to see if it exists
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_core__mailingAddressLookup(&$ciniki, $tnid, $str, $limit=10) {

    //
    // Prepare the search string
    //
    $str = preg_replace('/ /', '%', $str);

    //
    // Run the query
    //
    $strsql = "SELECT id, address1, address2, city, province, postal, country "
//        . "FROM ciniki_core_mailingaddresses "
        . "FROM ciniki_core_mailingaddresses "
        . "WHERE address1 LIKE '" . ciniki_core_dbQuote($ciniki, $str) . "%' "
        . "LIMIT " . intval($limit) . " "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.core', array(
        array('container'=>'addresses', 'fname'=>'id', 
            'fields'=>array('id', 'address1', 'address2', 'city', 'province', 'postal', 'country'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.408', 'msg'=>'Unable to load addresses', 'err'=>$rc['err']));
    }
    $addresses = isset($rc['addresses']) ? $rc['addresses'] : array();
    foreach($addresses AS $aid => $addr) {
        $addresses[$aid]['address'] = $addr['address1'];
        if( $addr['address2'] != '' ) {
            $addresses[$aid]['address'] .= ($addresses[$aid]['address'] != '' ? ', ' : '') . $addr['address2'];
        }
        if( $addr['city'] != '' ) {
            $addresses[$aid]['address'] .= ($addresses[$aid]['address'] != '' ? ', ' : '') . $addr['city'];
        }
        if( $addr['province'] != '' ) {
            $addresses[$aid]['address'] .= ($addresses[$aid]['address'] != '' ? ', ' : '') . $addr['province'];
        }
        if( $addr['postal'] != '' ) {
            $addresses[$aid]['address'] .= ($addresses[$aid]['address'] != '' ? '  ' : '') . $addr['postal'];
        }
    }

    return array('stat'=>'ok', 'addresses'=>$addresses);
}
?>
