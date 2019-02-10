<?php
//
// Description
// -----------
// Convert a GPS Coordinate from Decimal Degrees to Decimal Degrees Minutes.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_core_gpsDDtoDDM(&$ciniki, $tnid, $args) {

    $rsp = array('stat'=>'ok');

    if( isset($args['latitude']) ) {
        $dir = $args['latitude'] < 0 ? 'S' : 'N';
        $deg = (int)(abs($args['latitude']));
        $min = ((abs($args['latitude']) - $deg) * 60);
        $rsp['latitude'] = sprintf("%02d%05.02f%s", $deg, $min, $dir);
    }
    if( isset($args['longitude']) ) {
        $dir = $args['longitude'] < 0 ? 'W' : 'E';
        $deg = (int)(abs($args['longitude']));
        $min = ((abs($args['longitude']) - $deg) * 60);
        $rsp['longitude'] = sprintf("%03d%05.02f%s", $deg, $min, $dir);
    }

    return $rsp;
}
?>
