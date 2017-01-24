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
            //
            // Get the business mail settings
            //
            if( isset($email['business_id']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
                $rc = ciniki_mail_getSettings($ciniki, $email['business_id']);
                if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
                    $settings = $rc['settings'];
                }
            }
            //
            // If mailgun is enabled
            //
            if( isset($settings['mailgun-domain']) && $settings['mailgun-domain'] != ''
                && isset($settings['mailgun-key']) && $settings['mailgun-key'] != ''
                ) {
                //
                // Setup the message
                //
                $msg = array(
                    'from' => $settings['smtp-from-name'] . ' <' . $settings['smtp-from-address'] . '>',
                    'subject' => $email['subject'],
                    'html' => $email['htmlmsg'],
                    'text' => $email['textmsg'],
                    );
                if( isset($ciniki['config']['ciniki.mail']['force.mailto']) ) {
                    if( isset($email['to_name']) && $email['to_name'] != '' ) {
                        $msg['to'] = $email['to_name'] . ' <' . $ciniki['config']['ciniki.mail']['force.mailto'] . '>';
                    } else {
                        $msg['to'] = $ciniki['config']['ciniki.mail']['force.mailto'];
                    }
                    $msg['subject'] .= ' [' . $email['to'] . ']';
                } else {
                    if( isset($email['to_name']) && $email['to_name'] != '' ) {
                        $msg['to'] = $email['to_name'] . ' <' . $email['to'] . '>';
                    } else {
                        $msg['to'] = $email['to'];
                    }
                }

                //
                // Add attachments
                //
                $file_index = 1;
                if( isset($email['attachments']) ) {
                    foreach($email['attachments'] as $attachment) {
                        if( isset($attachment['string']) ) {
                            $tmpname = tempnam(sys_get_temp_dir(), 'mailgun');
                            file_put_contents($tmpname, $attachment['string']);
                            $cfile = curl_file_create($tmpname);
                            $cfile->setPostFilename($attachment['filename']);
                            $msg['file_' . $file_index] = $cfile;
                            $file_index++;
                        }
                    }
                }

                //
                // Send to mailgun api
                //
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $settings['mailgun-key']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
                curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/' . $settings['mailgun-domain'] . '/messages');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);

                $rsp = json_decode(curl_exec($ch));

                $info = curl_getinfo($ch);
                if( $info['http_code'] != 200 ) {
                    error_log("MAIL-ERR: [" . $email['to'] . "] " . $rsp->message);
                }
                curl_close($ch);
            } 
            //
            // Otherwise use SMTP mailer
            //
            else {
                require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
                require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');

                $mail = new PHPMailer;
                $mail->IsSMTP();

                $use_config = 'yes';
                if( isset($email['business_id']) 
                    && isset($settings['smtp-servers']) && $settings['smtp-servers'] != ''
                    && isset($settings['smtp-username']) && $settings['smtp-username'] != ''
                    && isset($settings['smtp-password']) && $settings['smtp-password'] != ''
                    && isset($settings['smtp-secure']) && $settings['smtp-secure'] != ''
                    && isset($settings['smtp-port']) && $settings['smtp-port'] != ''
                    ) {
                    $mail->Host = $settings['smtp-servers'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp-username'];
                    $mail->Password = $settings['smtp-password'];
                    $mail->SMTPSecure = $settings['smtp-secure'];
                    $mail->Port = $settings['smtp-port'];
                    $use_config = 'no';

                    if( isset($settings['smtp-secure']) && $settings['smtp-secure'] != ''
                        && isset($settings['smtp-port']) && $settings['smtp-port'] != '' ) {
                        $mail->From = $settings['smtp-from-address'];
                        $mail->FromName = $settings['smtp-from-name'];
                    } else {
                        $mail->From = $ciniki['config']['ciniki.core']['system.email'];
                        $mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
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
    }

    return array('stat'=>'ok');
}
?>
