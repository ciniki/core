<?php
//
// Description
// -----------
// This method will return the list of currencies supported by Ciniki.
//
// Arguments
// ---------
//
// Returns
// -------
// <currencies>
//     <currency id="USD" name="United States - US Dollar" number="840" units="2"/>
// </currencies>
//
function ciniki_core_getCurrencies($ciniki) {
	$currencies = array(
		array('currency'=>array('id'=>'USD', 'name'=>'United States - US Dollar', 'number'=>'840', 'units'=>'2')),
		array('currency'=>array('id'=>'CAD', 'name'=>'Canada - Canadian Dollar', 'number'=>'124', 'units'=>'2')),
		);

	return array('stat'=>'ok', 'currencies'=>$currencies);
}
?>
