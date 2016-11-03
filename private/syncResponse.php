<?php
//
// Description
// -----------
// This function will encrypt and return a response to the remote sync.
//
//
// Arguments
// ---------
//
function ciniki_core_syncResponse($ciniki, $hash) {
    if( !is_array($hash) ) {
        $rsp_hash = array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.327', 'msg'=>'Internal configuration error'));
    } else {
        $rsp_hash = $hash;
    }

    if( !isset($ciniki['sync']['remote_public_key']) 
        || $ciniki['sync']['remote_public_key'] == '' ) {
        $rsp_hash = array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.328', 'msg'=>'Internal configuration error'));
    }

    //
    // Serialize the response
    //
    $unencrypted_response = serialize($rsp_hash);

    //
    // Encrypt the response, using the remote public key
    //
    if( !openssl_seal($unencrypted_response, $encrypted_response, $keys, array($ciniki['sync']['remote_public_key'])) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.329', 'msg'=>'Invalid request'));
    }

    header("Content-Type: text/plain; charset=utf-8");
    print base64_encode($keys[0]);
    print ':::';
    print base64_encode($encrypted_response);

    return array('stat'=>'ok');
}
?>
