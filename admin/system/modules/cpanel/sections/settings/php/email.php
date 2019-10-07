<?php
namespace FileRun;

if (!Perms::isSuperUser()) {
	jsonFeedback(false, "You are not allowed to access this page.");
}

if ($_GET['action'] == 'test') {
	if ($config['misc']['demoMode']) {
		echo "Action unavailable in this demo version of the software!";
	} else {
		$data = [
			"default_notification_address" => \S::fromHTML($_POST['settings']['default_notification_address']),
			"default_notification_name" => \S::fromHTML($_POST['settings']['default_notification_name']),
			"smtp_host" => \S::fromHTML($_POST['settings']['smtp_host']),
			"smtp_port" => \S::fromHTML($_POST['settings']['smtp_port']),
			"smtp_security" => \S::fromHTML($_POST['settings']['smtp_security']),
			"smtp_auth" => (\S::fromHTML($_POST['settings']['smtp_auth']) ? 1 : 0),
			"smtp_username" => \S::fromHTML($_POST['settings']['smtp_username']),
			"smtp_password" => \S::fromHTML($_POST['settings']['smtp_password'])
		];

		if (strlen($data['smtp_host']) < 1) {
			echo '<div class="error">'.Lang::t('Please type a hostname').'</div>';
		} else if (strlen($data['smtp_port']) < 1) {
			echo '<div class="error">'.Lang::t('Please type a port number').'</div>';
		} else if ($data['smtp_auth'] && strlen($data['smtp_username']) < 1) {
			echo '<div class="error">'.Lang::t('Please type a username').'</div>';
		} else {
			$mail = new Utils\Email();
			$fromAddress = $data['default_notification_address'] ?? $data['smtp_username'];
			$fromName = $data['default_notification_name'] ?? $mail->FromName;
			$mail->setFrom($fromAddress, $fromName);

			$mail->addAddress($fromAddress);


			$mail->Subject = 'SMTP server test';
			$mail->isHTML(true);
			$mail->msgHTML('This is just an <em>SMTP server</em> test message in <b>HTML</b> format.');
			$mail->isSMTP();
			$mail->Host = $data['smtp_host'];
			$mail->Port = $data['smtp_port'];
			if ($data['smtp_security'] == 'tls') {
				$mail->SMTPSecure = 'tls';
			} else if ($data['smtp_security'] == 'ssl') {
				$mail->SMTPSecure = 'ssl';
			}
			if ($data['smtp_auth']) {
				$mail->SMTPAuth = true;
				$mail->Username = $data['smtp_username'];
				$mail->Password = $data['smtp_password'];
			}
			$mail->SMTPDebug = 2;
			$mail->Debugoutput = 'html';
			if ($mail->send()) {
				echo '<div class="ok">'.Lang::t('The SMTP settings have been successfully tested').'</div>';
			}
		}
		exit();
	}
}


$t = array();
foreach ($settings->data as $key => $val) {
	if (substr($key, 0, 5) == "smtp_" || in_array($key, [
			"default_notification_address",
			'default_notification_name',
			"instant_email_notifications",
			"allow_folder_notifications",
			'send_from_custom_email',
			'notifications_template',
			'notifications_subject_template',
			'notifications_bcc']
		)) {
		$t[$key] = $val;
	}
}
$app['AllSettings'] = json_encode($t);
\FileRun::displaySmartyPage();