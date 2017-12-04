<?php
//
// Description
// -----------
// This function will initialize the sync, and pull the request from the POST content
//
// Arguments
// ---------
// ciniki_root:         The root of the ciniki install, which must contain a ciniki-api.ini file.
//
function ciniki_core_syncInit($ciniki_root) {

    //
    // Initialize the ciniki structure, and setup the return value
    // to include the stat.
    //
    $ciniki = array();

    //
    // Load the config
    //
    require_once($ciniki_root . '/ciniki-mods/core/private/loadCinikiConfig.php');
    if( ciniki_core_loadCinikiConfig($ciniki, $ciniki_root) == false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.208', 'msg'=>'Internal configuration error'));
    }

    //
    // Initialize Database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInit');
    $rc = ciniki_core_dbInit($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // The synctype (type), tenant UUID (uuid) must be specifed in the URL
    //
    if( !isset($_GET) || !is_array($_GET)  ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.209', 'msg'=>'Internal configuration error'));
    }

    //
    // Check the request, make sure it's valid
    // We only allow the sync type of tenant right now.
    // The remote end must pass their tenant uuid, so we know which sync connection to use.
    //
    if( !isset($_GET['type']) || $_GET['type'] != 'tenant' 
        || !isset($_GET['uuid']) || $_GET['uuid'] == '' 
        || !isset($_GET['from']) || $_GET['from'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.210', 'msg'=>'Internal configuration error'));
    }
    $ciniki['sync'] = array('type'=>$_GET['type'], 'local_uuid'=>$_GET['uuid'], 'remote_uuid'=>$_GET['from']);

    //
    // Get the local_private_key to decode the request
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT ciniki_tenant_syncs.id AS sync_id, "
        . "ciniki_tenants.id AS tnid, ciniki_tenants.uuid, "
        . "ciniki_tenants.sitename, "
        . "ciniki_tenant_syncs.status, "
        . "ciniki_tenant_syncs.flags, "
        . "local_private_key, "
        . "ciniki_tenant_syncs.remote_name, ciniki_tenant_syncs.remote_uuid, "
        . "ciniki_tenant_syncs.remote_url, ciniki_tenant_syncs.remote_public_key "
        . "FROM ciniki_tenants, ciniki_tenant_syncs "
        . "WHERE ciniki_tenants.uuid = '" . ciniki_core_dbQuote($ciniki, $ciniki['sync']['local_uuid']) . "' "
        . "AND ciniki_tenants.id = ciniki_tenant_syncs.tnid "
        . "AND ciniki_tenant_syncs.remote_uuid = '" . ciniki_core_dbQuote($ciniki, $ciniki['sync']['remote_uuid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'sync');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sync']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.211', 'msg'=>'Internal configuration error'));
    }
    if( $rc['sync']['status'] != '10' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.212', 'msg'=>'Suspended sync'));
    }

    $local_private_key = $rc['sync']['local_private_key'];
    $ciniki['sync']['local_private_key'] = $rc['sync']['local_private_key'];
    $ciniki['sync']['remote_name'] = $rc['sync']['remote_name'];
    $ciniki['sync']['remote_uuid'] = $rc['sync']['remote_uuid'];
    $ciniki['sync']['remote_url'] = $rc['sync']['remote_url'];
    $ciniki['sync']['remote_public_key'] = $rc['sync']['remote_public_key'];
    $ciniki['sync']['tnid'] = $rc['sync']['tnid'];
    $ciniki['sync']['sitename'] = $rc['sync']['sitename'];
    $ciniki['sync']['id'] = $rc['sync']['sync_id'];
    // uuidmaps stores the mappings from remote to local uuid
    $ciniki['sync']['uuidmaps'] = array();
    // uuids is a cache for looked up uuids in different modules
    $ciniki['sync']['uuids'] = array();
    $ciniki['syncqueue'] = array();
    if( isset($ciniki['config']['ciniki.core']['sync.log_lvl']) ) {
        $ciniki['syncloglvl'] = $ciniki['config']['ciniki.core']['sync.log_lvl'];
    } else {
        $ciniki['syncloglvl'] = 0;
    }
    $ciniki['synclogfile'] = '';

    //
    // unserialize the POST content
    //
    if( isset($_POST) && is_array($_POST) ) {
        $encrypted_content = file_get_contents("php://input");

        // unencrypt
        // private_decrypt and encrypt can only be used for short strings
//      if( !openssl_private_decrypt($encrypted_content, $decrypted_content, $local_private_key) ) {
        $arsp = preg_split('/:::/', $encrypted_content);
        if( count($arsp) != 2 || !isset($arsp[1]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.213', 'msg'=>'Invalid request'));
        }
        if( !openssl_open(base64_decode($arsp[1]), $decrypted_content, base64_decode($arsp[0]), $local_private_key) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.214', 'msg'=>'Internal configuration error'));
        }
        
        // unserialize
        $request = unserialize($decrypted_content);
        if( !is_array($request) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.215', 'msg'=>'Internal configuration error'));
        }
        $ciniki['request'] = $request;

        //
        // Check the ts to make sure it's within 1 minute of UTC
        // This makes sure that the request is current, and not a cut and paste, listening in the middle.  If
        // the timestamp decrypts and is accurate, it is assumed the request is valid.
        //
        date_default_timezone_set('UTC');
        if( !isset($ciniki['request']['ts']) 
            || $ciniki['request']['ts'] <= 0 
            || abs(time() - $ciniki['request']['ts']) > 60 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.216', 'msg'=>'System Clocks out of sync'));
        }
        if( !isset($ciniki['request']['method']) 
            || $ciniki['request']['method'] == ''
            ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.217', 'msg'=>'No action specified'));
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.218', 'msg'=>'Invalid request'));
    }

    return array('stat'=>'ok', 'ciniki'=>$ciniki);
}
?>
