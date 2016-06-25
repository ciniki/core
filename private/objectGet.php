<?php
//
// Description
// -----------
// This function will retrieve an object to the database.
//
// Arguments
// ---------
// ciniki:
// pkg:         The package the object is a part of.
// mod:         The module the object is a part of.
// obj:         The name of the object in the module.
// args:        The arguments passed to the API.
//
// Returns
// -------
//
function ciniki_core_objectGet(&$ciniki, $business_id, $obj_name, $oid) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectLoad');

    //
    // Break apart object name
    //
    list($pkg, $mod, $obj) = explode('.', $obj_name);

    //
    // Load the object file
    //
    $rc = ciniki_core_objectLoad($ciniki, $obj_name);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $o = $rc['object'];
    $m = "$pkg.$mod";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    // 
    // Build the query to get the object
    //
    $strsql = "SELECT id ";
    $fields = array();
    $utctotz = array();
    foreach($o['fields'] as $field => $options) {
        $strsql .= ", " . $field . " ";
        if( isset($options['type']) && $options['type'] == 'utcdatetime' ) {
            $utctotz[$field] = array('timezone'=>$intl_timezone, 'format'=>$datetime_format);
        }
    }
    $strsql .= "FROM " . $o['table'] . " "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $oid) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $container = isset($o['o_container'])?$o['o_container']:'objects';
    $name = isset($o['o_name'])?$o['o_name']:'object';
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, $pkg . '.' . $mod, array(
        array('container'=>$container, 'fname'=>'id',
            'fields'=>array_keys($o['fields']),
            'utctotz'=>$utctotz,
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc[$container][$oid]) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1757', 'msg'=>"Unable to load the " . lowercase($o['name']) . " you requested."));
    }
    $object = $rc[$container][$oid];

    $rsp = array('stat'=>'ok');
    $rsp[$name] = $object;

    return $rsp;
}
?>
