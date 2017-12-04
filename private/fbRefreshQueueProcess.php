<?php
//
// Description
// -----------
// This function updates urls on facebook graph so images are precached and available to share.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant on the local side to check sync.
//
function ciniki_core_fbRefreshQueueProcess(&$ciniki) {

    if( !isset($ciniki['config']['ciniki.core']['fb.refresh.queue'])
        || ($ciniki['config']['ciniki.core']['fb.refresh.queue'] != 'yes' 
            && $ciniki['config']['ciniki.core']['fb.refresh.queue'] != 'debug')
        ) {
        return array('stat'=>'ok');
    }

    foreach($ciniki['fbrefreshqueue'] as $fb) {
        //
        // Get the tenant web information
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'lookupTenantURL');
        $rc = ciniki_web_lookupTenantURL($ciniki, $fb['tnid']);
        if( $rc['stat'] != 'ok' ) {
            error_log("FB: Invalid tenant: " . print_r($fb, true));
        }
        $base_url = $rc['url'];

        $url = 'https://graph.facebook.com/?id=' . urlencode($base_url . ($fb['url'][0]!='/'?'/':'') . $fb['url']) . '&scrape=true';

        if( $ciniki['config']['ciniki.core']['fb.refresh.queue'] == 'debug' ) {
            error_log("FB: refreshing: $url");
        } else {
            $file = file_get_contents($url);
            if( $file === FALSE ) {
                error_log("FB: Error opening url: $url");
            } else {
                error_log($file);
            }
        }
    }

    return array('stat'=>'ok');
}
?>
