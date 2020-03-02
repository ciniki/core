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
//
function ciniki_core_getWindSpeedUnits($ciniki) {

    $units = array(
        array('id'=>'kph', 'name'=>'Kilometers/Hour'),
        array('id'=>'mph', 'name'=>'Miles/Hour'),
        );

    return array('stat'=>'ok', 'units'=>$units);
}
?>
