<?php
//
// Description
// -----------
// This function will send the emails after the API has returned success to the client.  This
// assures a faster response on the API.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business on the local side to check sync.
//
function ciniki_core_emailQueueProcess(&$ciniki) {

    foreach($ciniki['emailqueue'] as $email) {
        if( isset($email['mail_id']) ) {
            //
            // Load the settings for the business
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
            $rc = ciniki_mail_getSettings($ciniki, $email['business_id']);
            if( $rc['stat'] != 'ok' ) {
                error_log("MAIL-ERR: Unable to load business mail settings for $business_id (" . serialize($rc) . ")");
                continue;
            }
            $settings = $rc['settings'];    

            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'sendMail');
            $rc = ciniki_mail_sendMail($ciniki, $email['business_id'], $settings, $email['mail_id']);
            if( $rc['stat'] != 'ok' ) {
                error_log("MAIL-ERR: Error sending mail: " . $email['mail_id'] . " (" . serialize($rc) . ")");
            }
        } 
        elseif( isset($email['user_id']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'hooks', 'emailUser');
            if( isset($email['htmlmsg']) ) {
                ciniki_users_hooks_emailUser($ciniki, 0, $email);
            } else {
                ciniki_users_hooks_emailUser($ciniki, 0, $email);
            }
        }
        elseif( isset($email['to']) ) {
            require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
            require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');

            $mail = new PHPMailer;
            $mail->IsSMTP();

            $use_config = 'yes';
            if( isset($email['business_id']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
                $rc = ciniki_mail_getSettings($ciniki, $email['business_id']);
                if( $rc['stat'] == 'ok' && isset($rc['settings'])
                    && isset($rc['settings']['smtp-servers']) && $rc['settings']['smtp-servers'] != ''
                    && isset($rc['settings']['smtp-username']) && $rc['settings']['smtp-username'] != ''
                    && isset($rc['settings']['smtp-password']) && $rc['settings']['smtp-password'] != ''
                    && isset($rc['settings']['smtp-secure']) && $rc['settings']['smtp-secure'] != ''
                    && isset($rc['settings']['smtp-port']) && $rc['settings']['smtp-port'] != ''
                    ) {
                    $mail->Host = $rc['settings']['smtp-servers'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $rc['settings']['smtp-username'];
                    $mail->Password = $rc['settings']['smtp-password'];
                    $mail->SMTPSecure = $rc['settings']['smtp-secure'];
                    $mail->Port = $rc['settings']['smtp-port'];
                    $use_config = 'no';

                    if( isset($rc['settings']['smtp-secure']) && $rc['settings']['smtp-secure'] != ''
                        && isset($rc['settings']['smtp-port']) && $rc['settings']['smtp-port'] != '' ) {
                        $mail->From = $rc['settings']['smtp-from-address'];
                        $mail->FromName = $rc['settings']['smtp-from-name'];
                    } else {
                        $mail->From = $ciniki['config']['ciniki.core']['system.email'];
                        $mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
                    }
                }
            } 
            
            //
            // If not enough information, or none provided, default back to system email
            //
            if( $use_config == 'yes' ) {
                if( !isset($ciniki['config']['ciniki.core']['system.email']) ) {
                    // If the system.email is not set, don't send any emails, dev system.
                    return array('stat'=>'ok');
                }
                $mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
                $mail->SMTPAuth = true;
                $mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
                $mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
                $mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
                $mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];

                $mail->From = $ciniki['config']['ciniki.core']['system.email'];
                $mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
            }

            if( isset($email['to_name']) && $email['to_name'] != '' ) {
                $mail->AddAddress($email['to'], $email['to_name']);
            } else {
                $mail->AddAddress($email['to']);
            }
            
            // Add reply to if specified
            if( isset($email['replyto_email']) && $email['replyto_email'] != '' ) {
                if( isset($email['replyto_name']) && $email['replyto_name'] != '' ) {
                    $mail->addReplyTo($email['replyto_email'], $email['replyto_name']);
                } else {
                    $mail->addReplyTo($email['replyto_email']);
                }
            }

            $mail->Subject = $email['subject'];
            if( isset($email['htmlmsg']) && $email['htmlmsg'] != '' ) {
                $mail->IsHTML(true);
                $mail->Body = $email['htmlmsg'];
                $mail->AltBody = $email['textmsg'];
            } else {
                $mail->IsHTML(false);
                $mail->Body = $email['textmsg'];
            }

            //
            // Check for attachments
            //
            if( isset($email['attachments']) ) {
                foreach($email['attachments'] as $attachment) {
                    if( isset($attachment['string']) ) {
                        $mail->addStringAttachment($attachment['string'], $attachment['filename']);
                    }
                }
            }

            if( !$mail->Send() ) {
                error_log("MAIL-ERR: [" . $email['to'] . "] " . $mail->ErrorInfo . " (trying again)");
                if( !$mail->Send() ) {
                    error_log("MAIL-ERR: [" . $email['to'] . "] " . $mail->ErrorInfo);
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
