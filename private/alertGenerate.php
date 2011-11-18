<?php
//
// Description
// -----------
// The alertGenerate function will add an alert to the database, and 
// send an email to alerts.notify from the config file.  These
// alerts are for system administrators of the Ciniki system, not
// business owners.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// alert:			The array of alert information.
// rc:				The return code from the last function call.
//
function ciniki_core_alertGenerate($ciniki, $alert, $rc) {

	if( !isset($ciniki['config']['core']['alerts.notify'])
		|| $ciniki['config']['core']['alerts.notify'] == '' 
		|| !is_array($alert) 
		|| !isset($alert['alert']) || !isset($alert['msg'])) {
		return;
	}

	$subject = sprintf("Alert %02d: %s", $alert['alert'], $alert['msg']);
	$var_alert = print_r($alert, true);
	$var_rc = '';
	if( $rc != null ) {
		$var_rc = print_r($rc, true);
	}
	$var_ciniki = print_r($ciniki, true);

	// 
	// Strip passwords from the ciniki variable
	//
	$var_ciniki = preg_replace('/password\] =\> (.*)/', 'password] => scrambled', $var_ciniki);
	
	//
	// First send the email messages
	//
	mail($ciniki['config']['core']['alerts.notify'], $subject, "alert:\n$var_alert\n\nrc:\n$var_rc\n\nciniki:\n$var_ciniki\n");

	//
	// Insert the alert details into the database
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$strsql = "INSERT INTO ciniki_core_alerts (code, msg, var_alert, var_ciniki, var_rc, date_added) "
		. "VALUES ('" . ciniki_core_dbQuote($ciniki, $alert['alert']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $alert['msg']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $var_alert) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $var_rc) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $var_ciniki) . "', "
		. "UTC_TIMESTAMP()); ";

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	return ciniki_core_dbInsert($ciniki, $strsql, 'core');
}
?>
