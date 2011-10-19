<?php
//
// Description
// -----------
// The alertGenerate function will add an alert to the database, and 
// send an email to alerts.notify from the config file.  These
// alerts are for system administrators of the MOSS system, not
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
function moss_core_alertGenerate($moss, $alert, $rc) {

	if( !isset($moss['config']['core']['alerts.notify'])
		|| $moss['config']['core']['alerts.notify'] == '' 
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
	$var_moss = print_r($moss, true);

	// 
	// Strip passwords from the moss variable
	//
	$var_moss = preg_replace('/password\] =\> (.*)/', 'password] => scrambled', $var_moss);
	
	//
	// First send the email messages
	//
	mail($moss['config']['core']['alerts.notify'], $subject, "alert:\n$var_alert\n\nrc:\n$var_rc\n\nmoss:\n$var_moss\n");

	//
	// Insert the alert details into the database
	//
	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$strsql = "INSERT INTO core_alerts (code, msg, var_alert, var_moss, var_rc, date_added) "
		. "VALUES ('" . moss_core_dbQuote($moss, $alert['alert']) . "', "
		. "'" . moss_core_dbQuote($moss, $alert['msg']) . "', "
		. "'" . moss_core_dbQuote($moss, $var_alert) . "', "
		. "'" . moss_core_dbQuote($moss, $var_rc) . "', "
		. "'" . moss_core_dbQuote($moss, $var_moss) . "', "
		. "UTC_TIMESTAMP()); ";

	require_once($moss['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	return moss_core_dbInsert($moss, $strsql, 'core');
}
?>
