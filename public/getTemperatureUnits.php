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
function ciniki_core_getTemperatureUnits($ciniki) {

    $units = array(
        array('id'=>'C', 'name'=>'Celcius'),
        array('id'=>'F', 'name'=>'Farhenheit'),
        );

    return array('stat'=>'ok', 'units'=>$units);
}
?>
