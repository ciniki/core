<?php
//
// Description
// -----------
// This function will return the current database versions
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
//
function ciniki_core_dbGetCinikiTables($ciniki) {
	//
	// The following array is used but the upgrade process to tell what
	// tables are required, and their current versions
	//

	//
	// *note* The default should be setup to:
	//	'users'=>array('module'=>'users', 'database_version'=>'-', 'schema_version'=>'-'),A
	//
	// The '-' means there is no current version installed.  When the upgrade process
	// checks, it will see a dash, and know to do a create of the table instead of look for upgrades.
	//
	return array(
		'businesses'=>array('module'=>'businesses', 'database_version'=>'-', 'schema_version'=>'-'),
		'business_details'=>array('module'=>'businesses', 'database_version'=>'-', 'schema_version'=>'-'),
		'business_users'=>array('module'=>'businesses', 'database_version'=>'-', 'schema_version'=>'-'),
		'business_permissions'=>array('module'=>'businesses', 'database_version'=>'-', 'schema_version'=>'-'),

		'customers'=>array('module'=>'customers', 'database_version'=>'-', 'schema_version'=>'-'),
		'customer_addresses'=>array('module'=>'customers', 'database_version'=>'-', 'schema_version'=>'-'),

		'subscriptions'=>array('module'=>'subscriptions', 'database_version'=>'-', 'schema_version'=>'-'),
		'subscription_customers'=>array('module'=>'subscriptions', 'database_version'=>'-', 'schema_version'=>'-'),

		// Core tables
		'core_alerts'=>array('module'=>'core', 'database_version'=>'-', 'schema_version'=>'-'),
		'core_api_keys'=>array('module'=>'core', 'database_version'=>'-', 'schema_version'=>'-'),
		'core_change_logs'=>array('module'=>'core', 'database_version'=>'-', 'schema_version'=>'-'),
		'core_session_data'=>array('module'=>'core', 'database_version'=>'-', 'schema_version'=>'-'),
		'core_user_agents'=>array('module'=>'core', 'database_version'=>'-', 'schema_version'=>'-'),

		'users'=>array('module'=>'users', 'database_version'=>'-', 'schema_version'=>'-'),
		'user_details'=>array('module'=>'users', 'database_version'=>'-', 'schema_version'=>'-'),
		'user_auth_log'=>array('module'=>'users', 'database_version'=>'-', 'schema_version'=>'-'),
		'user_auth_failures'=>array('module'=>'users', 'database_version'=>'-', 'schema_version'=>'-'),
		// 'user_addresses'=>array('module'=>'users', 'database_version'=>'-', 'schema_version'=>'-'),

		'bugs'=>array('module'=>'bugs', 'database_version'=>'-', 'schema_version'=>'-'),
		'bug_settings'=>array('module'=>'bugs', 'database_version'=>'-', 'schema_version'=>'-'),
		'bug_followups'=>array('module'=>'bugs', 'database_version'=>'-', 'schema_version'=>'-'),
		'bug_tags'=>array('module'=>'bugs', 'database_version'=>'-', 'schema_version'=>'-'),
		'bug_users'=>array('module'=>'bugs', 'database_version'=>'-', 'schema_version'=>'-'),

		'features'=>array('module'=>'features', 'database_version'=>'-', 'schema_version'=>'-'),
		'feature_followups'=>array('module'=>'features', 'database_version'=>'-', 'schema_version'=>'-'),
		'feature_users'=>array('module'=>'features', 'database_version'=>'-', 'schema_version'=>'-'),
		'feature_settings'=>array('module'=>'features', 'database_version'=>'-', 'schema_version'=>'-'),

		'questions'=>array('module'=>'questions', 'database_version'=>'-', 'schema_version'=>'-'),
		'question_followups'=>array('module'=>'questions', 'database_version'=>'-', 'schema_version'=>'-'),
		'question_users'=>array('module'=>'questions', 'database_version'=>'-', 'schema_version'=>'-'),
		'question_settings'=>array('module'=>'questions', 'database_version'=>'-', 'schema_version'=>'-'),

		// website tables
		'web_sites'=>array('module'=>'websites', 'database_version'=>'-', 'schema_version'=>'-'),
		'web_domains'=>array('module'=>'websites', 'database_version'=>'-', 'schema_version'=>'-'),
		'web_site_details'=>array('module'=>'websites', 'database_version'=>'-', 'schema_version'=>'-'),

		// friends tables
		'friends'=>array('module'=>'friends', 'database_version'=>'-', 'schema_version'=>'-'),
		'friend_details'=>array('module'=>'friends', 'database_version'=>'-', 'schema_version'=>'-'),

		// images tables
		'images'=>array('module'=>'images', 'database_version'=>'-', 'schema_version'=>'-'),
		'image_details'=>array('module'=>'images', 'database_version'=>'-', 'schema_version'=>'-'),
		'image_tags'=>array('module'=>'images', 'database_version'=>'-', 'schema_version'=>'-'),
		'image_versions'=>array('module'=>'images', 'database_version'=>'-', 'schema_version'=>'-'),
		'image_actions'=>array('module'=>'images', 'database_version'=>'-', 'schema_version'=>'-'),

		// media tables
		'media'=>array('module'=>'media', 'database_version'=>'-', 'schema_version'=>'-'),
		'media_details'=>array('module'=>'media', 'database_version'=>'-', 'schema_version'=>'-'),

		// toolbox tables
		'toolbox_excel'=>array('module'=>'toolbox', 'database_version'=>'-', 'schema_version'=>'-'),
		'toolbox_excel_data'=>array('module'=>'toolbox', 'database_version'=>'-', 'schema_version'=>'-'),
		'toolbox_excel_matches'=>array('module'=>'toolbox', 'database_version'=>'-', 'schema_version'=>'-'),

		// wineproduction tables
		'wineproductions'=>array('module'=>'wineproduction', 'database_version'=>'-', 'schema_version'=>'-'),
		'wineproduction_settings'=>array('module'=>'wineproduction', 'database_version'=>'-', 'schema_version'=>'-'),

		// Products
		'products'=>array('module'=>'products', 'database_version'=>'-', 'schema_version'=>'-'),
		'product_details'=>array('module'=>'products', 'database_version'=>'-', 'schema_version'=>'-'),
		'product_categories'=>array('module'=>'products', 'database_version'=>'-', 'schema_version'=>'-'),

		// Cron
		'cron'=>array('module'=>'cron', 'database_version'=>'-', 'schema_version'=>'-'),
		'cron_logs'=>array('module'=>'cron', 'database_version'=>'-', 'schema_version'=>'-'),
	);
}
?>
