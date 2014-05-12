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
// Returns
// -------
// <units>
//		<km name="Kilometers" />
//		<mi name="Miles" />
// </units>
//
function ciniki_core_getDistanceUnits($ciniki) {

	$units = array(
		array('unit'=>array('id'=>'km', 'name'=>'Kilometers')),
		array('unit'=>array('id'=>'mi', 'name'=>'Miles')),
		);

	return array('stat'=>'ok', 'units'=>$units);
}
?>
