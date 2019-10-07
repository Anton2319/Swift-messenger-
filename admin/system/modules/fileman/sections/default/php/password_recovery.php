<?php
namespace FileRun;
\FileRun::checkAndForceHTTPS();

Lang::setSection("Password Reset");

if ($config['system']['custom_auth_file'] || !$settings->password_recovery_enable) {
	siteRedirect("");
}

if ($_GET['action'] == "submit") {
	if ($config['misc']['demoMode']) {
		jsonFeedback(false, 'Action unavailable in this demo version of the software!');
	}

	$email = trim(\S::fromHTML($_POST['email']));
	if (strlen($email) < 1) {
		jsonFeedback(false, Lang::t("Please type the e-mail address."));
	}

	$usd = Users::getTable();
	$userInfo = $usd->selectOne("*", ["email", "=", $usd->q($email)]);
	if (!is_array($userInfo)) {
		jsonFeedback(false, 'The e-mail address was not found in the database.');
	}
	if (!$userInfo['activated']) {
		jsonFeedback(false, Lang::t("Your account has been deactivated!", "Login Page"));
	}
	$perms = Perms::getPerms($userInfo['id']);
	if (!$perms) {
		jsonFeedback(false,"Failed to locate user's permissions!");
	}
	if (UserGuests::isGuest($perms['role'])) {
		jsonFeedback(false,"Guest user accounts sign in using a special link and not a password!");
	}
	//assign data and get output
	$app['password_reset_hash'] = md5($userInfo['username'].$userInfo['password'].$userInfo['username'].$userInfo['registration_date']);
	$app['username'] = $userInfo['username'];
	$templateName = "reset_password";

	$smarty = \FileRun::getSmarty();
	$smarty->assign("info", $userInfo);
	$smarty->assign("app", $app);

	$mainTpl = Notifications\Templates::parse($templateName, ['From', 'FromName', 'BCC', 'Subject', 'Body']);

	$from = $smarty->fetch("string:".$mainTpl['From']);
	$fromName = $smarty->fetch("string:".$tpl['FromName']);
	$bcc = $smarty->fetch("string:".$mainTpl['BCC']);
	$subject = $smarty->fetch("string:".$mainTpl['Subject']);
	$body = $smarty->fetch("string:".$mainTpl['Body']);

	//send email
	$mail = new Utils\Email;
	$mail->setFrom($from, $fromName);
	$mail->Subject = $subject;
	$mail->Body = $body;
	if (strlen($bcc) > 3) {
		$mail->addBCC($bcc);
	}
	$mail->addAddress($userInfo['email']);
	$result = @$mail->send();

	if ($result) {
		Log::add($userInfo['id'], "password_recovery", array(
			"id" => $userInfo['id'],
			"IP" => getIP()
		));
		if ($config['app']['password_recovery']['redirect_url']) {
			header("Location: ".$config['app']['password_recovery']['redirect_url']);
			exit();
		}
		jsonFeedback(true, 'Please check your inbox for information on how to reset your account\'s password.');
	} else {
		Log::add($userInfo['id'], "password_recovery", array(
			"id" => $userInfo['id'],
			"IP" => getIP(),
			"error" => $mail->ErrorInfo
		));
		if ($config['app']['password_recovery']['redirect_url_failure']) {
			header("Location: ".$config['app']['password_recovery']['redirect_url_failure']);
			exit();
		}
		jsonFeedback(false, 'Failed to send e-mail message. Please contact the site\'s administrator.');
	}
	exit();
}

$app = [];
if ($settings->ui_login_bg) {
	if (substr($settings->ui_login_bg, 0, 1) == '#') {
		$app['ui_bg_color'] = $settings->ui_login_bg;
	} else {
		$app['ui_bg'] = $settings->ui_login_bg;
	}
}

\FileRun::displaySmartyPage();
if (\FileRun::isFree()) {
	echo '<div style="font-size:11px;position:fixed;bottom:0px;right:5px;background-color:#F7F7F7;border-radius:2px;padding:5px;">Website powered by <a href="http://www.filerun.com" target="_blank">FileRun</a></div>';
}
echo "\n\n</body></html>";