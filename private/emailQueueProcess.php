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
		if( isset($email['user_id']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'emailUser');
			ciniki_users_emailUser($ciniki, $email['user_id'], $email['subject'], $email['textmsg']);
		}
		elseif( isset($email['to']) ) {
			require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
			require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');

			$mail = new PHPMailer;

			$mail->IsSMTP();
			$mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
			$mail->SMTPAuth = true;
			$mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
			$mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
			$mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
			$mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];

			$mail->From = $ciniki['config']['ciniki.core']['system.email'];
			$mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
			$mail->AddAddress($email['to']);

			$mail->IsHTML(false);
			$mail->Subject = $email['subject'];
			$mail->Body = $email['textmsg'];

			error_log("Sending email to: " . $email['to'] . " from: " . $mail->From);
			if( !$mail->Send() ) {
				error_log("MAIL-ERR: " . $mail->ErrorInfo);
			}
		}
	}

	return array('stat'=>'ok');
}
?>
