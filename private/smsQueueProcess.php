<?php
//
// Description
// -----------
// This function will send the sms after the API has returned success to the client.  This
// assures a faster response on the API.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business on the local side to check sync.
//
function ciniki_core_smsQueueProcess(&$ciniki) {

    foreach($ciniki['smsqueue'] as $sms) {
        if( isset($sms['sms_id']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'sendMessage');
            $rc = ciniki_sms_sendMessage($ciniki, $sms['business_id'], $sms['sms_id'], null);
            if( $rc['stat'] != 'ok' ) {
                error_log("MAIL-ERR: Error sending sms: " . $sms['sms_id'] . " (" . serialize($rc) . ")");
            }
        } 
    }

    return array('stat'=>'ok');
}
?>
