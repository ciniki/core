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
// business_id:		The ID of the business on the local side to check sync.
//
function ciniki_core_emailQueueProcess(&$ciniki) {

	foreach($ciniki['emailqueue'] as $email) {
		if( isset($email['mail_id']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'sendMail');
			$rc = ciniki_mail_sendMail($ciniki, $email['business_id'], $ciniki['ciniki.mail.settings'], $email['mail_id']);
			if( $rc['stat'] != 'ok' ) {
				error_log("MAIL-ERR: Error sending to: " . $email['email'] . " (" . serialize($rc) . ")");
			}
		} 
		elseif( isset($email['user_id']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'emailUser');
			if( isset($email['htmlmsg']) ) {
				ciniki_users_emailUser($ciniki, $email['user_id'], $email['subject'], $email['textmsg'], $email['htmlmsg']);
			} else {
				ciniki_users_emailUser($ciniki, $email['user_id'], $email['subject'], $email['textmsg'], '');
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
			// If not enough informatio, or none provided, default back to system email
			//
			if( $use_config == 'yes' ) {
				$mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
				$mail->SMTPAuth = true;
				$mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
				$mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
				$mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
				$mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];

				$mail->From = $ciniki['config']['ciniki.core']['system.email'];
				$mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
			}

			$mail->AddAddress($email['to']);

			$mail->Subject = $email['subject'];
			if( isset($email['htmlmsg']) && $email['htmlmsg'] != '' ) {
				$mail->IsHTML(true);
				$mail->Body = $email['htmlmsg'];
				$mail->AltBody = $email['textmsg'];
			} else {
				$mail->IsHTML(false);
				$mail->Body = $email['textmsg'];
			}

			if( !$mail->Send() ) {
				error_log("MAIL-ERR: [" . $email['to'] . "] " . $mail->ErrorInfo);
			}
		}
	}

	return array('stat'=>'ok');
}
?>
