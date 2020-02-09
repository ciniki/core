<?php
//
// Description
// -----------
// This method will return the list of locales supported by Ciniki.  This list
// should be expanded in the future to include other countries.
//
// Arguments
// ---------
//
function ciniki_core_getTimeZones($ciniki) {

    $zones = timezone_identifiers_list();

    $timezones = array();
    foreach($zones as $zone) {
        $e_zone = explode('/', $zone); // 0 => Continent, 1 => City
        
        $timezones[] = array('id'=>"$zone");
    }

    return array('stat'=>'ok', 'timezones'=>$timezones);
}
?>
