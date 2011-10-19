<?php
//
// Description
// -----------
// This function will check for an open connection to the database, 
// and if not return a new connection.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
//
function moss_core_dbConnect(&$moss, $module) {
	
	// 
	// Check for required $moss variables
	//
	if( !is_array($moss['config']['core']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'13', 'msg'=>'Internal Error', 'pmsg'=>'$moss variable not defined'));
	}

	//
	// Get the database name for the module specified.  If the
	// module does not have a database specified, then open
	// the default core database
	//
	$database_name = '';
	if( isset($moss['config'][$module]['database']) ) {
		$database_name = $moss['config']['core']['database'];
	} elseif( isset($moss['config']['core']['database']) ) {
		$database_name = $moss['config']['core']['database'];
	} else {
		return array('stat'=>'fail', 'err'=>array('code'=>'14', 'msg'=>'Internal Error', 'pmsg'=>'database name not default for requested module'));
	}

	//
	// Check if database connection is already open
	//
	if( isset($moss['databases'][$database_name]['connection']) && is_resource($moss['databases'][$database_name]['connection']) ) {
		return array('stat'=>'ok', 'dh'=>$moss['databases'][$database_name]['connection']);
	}

	//
	// Check if database has been specified in config file, and setup in the databases array.
	//
	if( !is_array($moss['databases'][$database_name]) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'15', 'msg'=>'Internal Error', 'pmsg'=>'database name not specified in config.ini'));
	}

	//
	// Get connection information
	//
	if( !isset($moss['config']['core']['database.' . $database_name . '.hostname'])
		|| !isset($moss['config']['core']['database.' . $database_name . '.username'])
		|| !isset($moss['config']['core']['database.' . $database_name . '.password'])
		|| !isset($moss['config']['core']['database.' . $database_name . '.database'])
		) {
		return array('stat'=>'fail', 'err'=>array('code'=>'16', 'msg'=>'Internal configuration error', 'pmsg'=>"database credentials not specified for the module '$module'"));
		
	}

	//
	// Open connection to the database requested
	//
	$moss['databases'][$database_name]['connection'] = mysql_connect( 
		$moss['config']['core']['database.' . $database_name . '.hostname'],
		$moss['config']['core']['database.' . $database_name . '.username'],
		$moss['config']['core']['database.' . $database_name . '.password']);

	if( $moss['databases'][$database_name]['connection'] == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'18', 'msg'=>'Database error', 'pmsg'=>"Unable to connect to database '$database_name' for '$module'"));
	}

	if( mysql_select_db($moss['config']['core']['database.' . $database_name . '.database'], $moss['databases'][$database_name]['connection']) == false ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'20', 'msg'=>'Database error', 'pmsg'=>"Unable to connect to database '$database_name' for '$module'"));
	}

	return array('stat'=>'ok', 'dh'=>$moss['databases'][$database_name]['connection']);
}
?>
