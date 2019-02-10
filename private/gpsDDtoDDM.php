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
        $rsp['latitude'] = sprintf("%02d%02.02f%s", $deg, $min, $dir);
//        $min = (int)((abs($args['latitude']) - $deg) * 60);
//        $sec = (int)((abs($args['latitude']) - $deg - ($min/60)) * 3600);
//        $rsp['latitude'] = sprintf("%02d%02d.%02d%s", $deg, $min, $sec, $dir);
    }
    if( isset($args['longitude']) ) {
        $dir = $args['longitude'] < 0 ? 'E' : 'W';
        $deg = (int)(abs($args['longitude']));
        $min = ((abs($args['longitude']) - $deg) * 60);
        $rsp['longitude'] = sprintf("%03d%02.02f%s", $deg, $min, $dir);
//        $min = (int)((abs($args['longitude']) - $deg) * 60);
//        $sec = (int)((abs($args['longitude']) - $deg - ($min/60)) * 3600);
//        $rsp['longitude'] = sprintf("%03d%02d.%02d%s", $deg, $min, $sec, $dir);
    }

    return $rsp;
}
?>
