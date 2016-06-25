<?php
//
// This script will build the index page required to load the javascript for ciniki-manage
//

//
// Load the ciniki config
//
global $ciniki_root;
//$ciniki_root = dirname(dirname(__FILE__));
$ciniki_root = dirname(__FILE__);
// Some systems don't follow symlinks like others
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
$manage_root = $ciniki_root . '/ciniki-mods';
$themes_root = $ciniki_root . '/ciniki-mods/core/ui/themes';
$manage_js = "/ciniki-mods/core/ui";
$manage_themes = "/ciniki-mods/core/ui/themes";
$start_container = 'm_login';

require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkSecureConnection.php');
$rc = ciniki_core_init($ciniki_root, 'manage');
if( $rc['stat'] != 'ok' ) {
    print_error(1001, 'There is currently a configuration problem, please try again later.');
    exit;
}
$ciniki = $rc['ciniki'];

//
// Ensure the connection is over SSL
//
$rc = ciniki_core_checkSecureConnection($ciniki);
if( $rc['stat'] != 'ok' ) {
    print_error('1-10', 'There is currently a configuration problem, please try again later.');
    exit;
}

if( !isset($_COOKIE['api_key']) || $_COOKIE['api_key'] == '' ) {
    print_error('1-12', 'There is currently a configuration problem, please try again later.');
    exit;
}
if( !isset($_COOKIE['auth_token']) || $_COOKIE['auth_token'] == '' ) {
    print_error('1-13', 'There is currently a configuration problem, please try again later.');
    exit;
}
if( !isset($_COOKIE['business_id']) || $_COOKIE['business_id'] == '' ) {
    print_error('1-14', 'There is currently a configuration problem, please try again later.');
    exit;
}

//
// Check the API Key
//
$ciniki['request']['api_key'] = $_COOKIE['api_key'];
$ciniki['request']['auth_token'] = $_COOKIE['auth_token'];
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkAPIKey');
$rc = ciniki_core_checkAPIKey($ciniki);
if( $rc['stat'] != 'ok' ) {
    print_error($rc['err']['code'], $rc['err']['msg']);
    exit;
}

if( !isset($ciniki['request']['auth_token']) || $ciniki['request']['auth_token'] == '' ) {
    print_error('1-11', 'There is currently a configuration problem, please try again later.');
    exit;
}

//
// Load the session if an auth_token was passed
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sessionOpen');
$rc = ciniki_core_sessionOpen($ciniki);
if( $rc['stat'] != 'ok' ) {
    print_error($rc['err']['code'], $rc['err']['msg']);
    exit;
}

//
// Check user has access to business
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
$rc = ciniki_businesses_checkAccess($ciniki, $_COOKIE['business_id'], 'ciniki.businesses.settingsAPIsUpdate');
if( $rc['stat'] != 'ok' ) {
    print_error($rc['err']['code'], $rc['err']['msg']);
    exit;
}
$business_id = $_COOKIE['business_id'];

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
//
// Dropbox
//
if( isset($_COOKIE['dropbox']) && $_COOKIE['dropbox'] != '' ) {
    $csrf = $_COOKIE['dropbox'];

    if( $csrf != $_GET['state'] ) {
        print_error('1-13', 'There is currently a configuration problem, please try again later.');
        exit;
    }

    //
    // Get the access token from Dropbox
    //
    $ch = curl_init('https://api.dropbox.com/1/oauth2/token');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    curl_setopt($ch, CURLOPT_POST, true);
    $post_args = 'code=' . rawurlencode($_GET['code']) 
        . '&grant_type=authorization_code'
        . '&client_id=' . rawurlencode($ciniki['config']['ciniki.core']['dropbox.appkey'])
        . '&client_secret=' . rawurlencode($ciniki['config']['ciniki.core']['dropbox.secret'])
        . '&redirect_uri=' . rawurlencode($ciniki['config']['ciniki.core']['dropbox.redirect'])
        . '';
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_args);

    $rsp = curl_exec($ch);
    if( $rsp === false ) {
        print_error('1-18', 'There is currently a configuration problem, please try again later.');
        exit;
    }
    $rc = json_decode($rsp, true);
    
    if( isset($rc['error']) && $rc['error'] != '' ) {
        print_error('1-20', $rc['error'] . ': ' . $rc['error_description']);
        exit;
    }

    //
    // Save the token
    //
    if( isset($rc['access_token']) && $rc['access_token'] != '' ) {
        $strsql = "INSERT INTO ciniki_business_details (business_id, "
            . "detail_key, detail_value, date_added, last_updated) "
            . "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
            . ", 'apis-dropbox-access-token' "
            . ", '" . ciniki_core_dbQuote($ciniki, $rc['access_token']) . "'"
            . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
            . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $rc['access_token']) . "' "
            . ", last_updated = UTC_TIMESTAMP() "
            . "";
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
            return $rc;
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
            $business_id, 2, 'ciniki_business_details', 'apis-dropbox-access-token', 'detail_value', $rc['access_token']);
        $ciniki['syncqueue'][] = array('push'=>'ciniki.businesses.details', 'args'=>array('id'=>'apis-dropbox-access-token'));

        print_success();
        exit;
    }

    print_error('1-15', 'No access granted');
    exit;
} 

//
// Future integrations: Facebook, Twitter, etc
//

//
// Nothing specified
//
else {
    print_error(1010, 'There is currently a configuration problem, please try again later.');
    exit;
}
    
exit;

function print_success() {
?>
<html>
<head>
<title>Success</title>
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/style.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/e-webkit.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/e-trident.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/e-gecko.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/s-normal.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/s-normal-webkit.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/d-generic.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/colors.css" />
<script type="text/javascript">window.onload=close</script>
</head>
<body>
<div id="m_error">
    <div id="me_content">
        <div id="mc_content_wrap" class="medium">
            <p>Your access has been granted, this window will close automatically.</p>
        </div>
    </div>
</div>
</body>
</html>
<?php
}

function print_error($code, $msg) {
?>
<html>
<head>
<title>Error</title>
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/style.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/e-webkit.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/e-trident.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/e-gecko.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/s-normal.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/s-normal-webkit.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/d-generic.css" />
<link rel="stylesheet" type="text/css" href="/ciniki-mods/core/ui/themes/default/colors.css" />
</head>
<body>
<div id="m_error">
    <div id="me_content">
        <div id="mc_content_wrap" class="medium">
            <p>Oops, we seem to have hit a snag.</p>
            <p><br/></p>
            <table class="list noheader border" cellspacing='0' cellpadding='0'>
                <tbody>
                    <tr><td><?php echo $msg; ?> (Error: <?php echo $code; ?>)</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php
}

?>
