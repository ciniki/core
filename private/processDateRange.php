<?php
//
// Description
// -----------
// This function will process a start and end date into a date range.
//
// Required Methods
// ----------------
//
// Arguments
// ---------
//
function ciniki_core_processDateRange($ciniki, $args) {

	$date_range = '';

	if( isset($args['start_month']) ) {
		$date_range = $args['start_month'];
	}
	if( isset($args['start_day']) && $args['start_day'] != '' ) {
		$date_range .= " " . $args['start_day'];
	}

	if( isset($args['end_day']) && $args['end_day'] != '' && $args['start_day'] != $args['end_day'] ) {
		if( isset($args['end_month']) && $args['end_month'] != '' 
			&& $args['end_month'] == $args['start_month'] ) {
			$date_range .= " - " . $args['end_day'];
		} elseif( isset($args['end_month']) && $args['end_month'] != '' ) {
			$date_range .= " - " . $args['end_month'] . " " . $args['end_day'];
		}
	}
	if( isset($args['start_year']) && $args['start_year'] != '' ) {
		$date_range .= ", " . $args['start_year'];
		if( isset($args['end_year']) && $args['end_year'] != '' && $args['start_year'] != $args['end_year'] ) {
			$date_range .= "/" . $args['end_year'];
		}
	}

	return array('stat'=>'ok', 'dates'=>$date_range);
}
?>
