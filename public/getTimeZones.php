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
// <timezones>
//		<timezone name="America/Toronto" />
// </timezones>
//
function ciniki_core_getTimeZones($ciniki) {

	$zones = timezone_identifiers_list();

	$timezones = array();
	foreach($zones as $zone) {
		$e_zone = explode('/', $zone); // 0 => Continent, 1 => City
		
		if( $e_zone[0] == 'America' ) {
			$timezones[] = array('timezone'=>array('id'=>"$zone"));
		}
	}

	return array('stat'=>'ok', 'timezones'=>$timezones);
}
?>
