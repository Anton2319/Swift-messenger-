<?php
namespace FileRun;
$lx = \FileRun::getL();
\FileRun::checkAndForceHTTPS();
Lang::setSection("Login Page");

require($config['path']['includes'].'/auth_regular.php');
if ($authenticated) {
	$auth->initPHPSession();
	if (isset($_SESSION['FileRun']['AuthRedirect'])) {
		$redirectURL = $_SESSION['FileRun']['AuthRedirect'];
		unset($_SESSION['FileRun']['AuthRedirect']);
		header("Location: " . $redirectURL);
		exit();
	} else {
		siteRedirect('');
	}
}

$uname = trim(\S::fromHTML($_REQUEST['username']));
$pwd = trim(\S::fromHTML($_REQUEST['password']));
$two_step_secret = trim(\S::fromHTML($_REQUEST['two_step_secret']));
$otp = trim(\S::fromHTML($_REQUEST['otp']));

if ($_GET['action'] == "reset_password") {
	$usd = Users::getTable();
	$userInfo = $usd->selectOne("*", ["username", "=", $usd->q($uname)]);
	$hash = md5($userInfo['username'].$userInfo['password'].$userInfo['username'].$userInfo['registration_date']);
	if (\S::fromHTML($_GET['h']) == $hash) {
		$pp = new \PassPolicy();
		$randomPass = $pp->generate();
		$dbPass = Auth::hashPassword($randomPass);

		$rs = $usd->update([
			"password" => $usd->q($dbPass),
			'last_pass_change' => 'NOW()',
			"require_password_change" => ($settings->password_recovery_force_change ? 1 : 0),
			'two_step_secret' => 'NULL',
			'last_otp' => $usd->q('')
		], ["id", "=", $userInfo['id']], false);
		if ($rs) {
			$app['password_reset'] = true;
			$userInfo['password'] = $randomPass;
			$smarty = \FileRun::getSmarty();
			$smarty->assign("info", $userInfo);
			$smarty->assign("app", $app);
			$mainTpl = Notifications\Templates::parse("forgot_password", ['From', 'FromName', 'BCC', 'Subject', 'Body']);

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
				$app['message'] = Lang::t("The login information has been sent to your email address.", "Password Reset");
				Log::add($userInfo['id'], "password_recovery", [
					"id" => $userInfo['id'],
					"IP" => getIP()
				]);
			} else {
				$app['message'] = Lang::t("Failed to send e-mail message. Please contact the site's administrator.", "Password Reset");
				Log::add($userInfo['id'], "password_recovery", [
					"id" => $userInfo['id'],
					"IP" => getIP(),
					"error" => $mail->ErrorInfo
				]);
			}
		}
	} else {
		$app['message'] = Lang::t('The password reset link you have followed is no longer valid.', "Password Reset");
	}
} else if ($_GET['action'] == "login" || $_GET['action'] == "ajax_login") {
	$lx->chkU();
	$auth = new Auth(true);
	$persistent = ($settings->logout_inactivity == 0);
	$authResult = $auth->authenticate($uname, $pwd, $persistent, $otp, $two_step_secret);
	if ($authResult > 0) {
		Log::add(false, "login", ["IP" => getIP()]);
	} else {
		if ($auth->errorCode == 'FAIL_LIMIT') {
			Log::add($auth->currentUserInfo['id'], "login_failed_account_deactivated", ["IP" => getIP()]);
		} else {
			if ($auth->currentUserInfo['id']) {
				Log::add($auth->currentUserInfo['id'], "login_failed", [
					"IP" => getIP(),
					"error" => $auth->error,
					"errorCode" => $auth->errorCode
				]);
			}
		}
	}

	$returnData = ["success" => $authResult];
	if ($auth->error) {
		$returnData["error"] = Lang::t($auth->error);
	}
	if ($auth->errorCode == '2FA_ASK_OTP') {
		$returnData['ask_otp'] = true;
	} else if ($auth->errorCode == '2FA_INIT') {
		$returnData['twoStepSecret'] = $auth->TwoStep['secret'];
		$returnData['keyURI'] = base64_encode($auth->TwoStep['keyURI']);
		$returnData['keyURIPlain'] = $auth->TwoStep['keyURI'];
	}

	$redirectURL = false;
	if ($authResult) {
		if (isset($_SESSION['FileRun']['AuthRedirect'])) {
			$redirectURL = $_SESSION['FileRun']['AuthRedirect'];
			unset($_SESSION['FileRun']['AuthRedirect']);
		} else {
			if ($config['app']['login']['redirect_url']) {
				$redirectURL = $config['app']['login']['redirect_url'];
			} else {
				if ($_REQUEST['nonajax']) {
					$redirectURL = $config['url']['root'];
				}
			}
		}
	} else {
		if ($config['app']['login']['redirect_url_failure']) {
			$redirectURL = $config['app']['login']['redirect_url_failure'] ."feedback=".base64_encode(Lang::t($auth->error));
		}
	}

	if ($_REQUEST['nonajax']) {
		header("Location: " . $redirectURL);
	} else {
		$returnData['redirect_url'] = \S::forURL($redirectURL);
		header('Content-type: text/html');
		echo json_encode($returnData);
	}
	exit();
}

if (!\FileRun::isFree()) {
	if (!$auth) {
		$auth = new Auth(true);
	}
	$app['ssoEnabled'] = $auth->ssoAvailable();
	$app['ssoOnly'] = $auth->ssoOnly();
	$app['signUpEnabled'] = \FileRun::isSignupAvailable();
}

$currentLang = Lang::getCurrent(true);
$app['languages'] = [];
$rs = UI\TranslationUtils::listAvailable();
foreach ($rs as $k => $l) {
	if ($currentLang === $k) {
		$currentLang = $l;
	}
	$app['languages'][] = [$k, $l['displayName'], $l['shortName']];
}
$app['currentLanguage'] = mb_strtoupper($currentLang['shortName']);

if ($settings->maintenance) {
	$app['message'] = nl2br(Lang::t($settings->maintenance_message_users));
}

if ($settings->ui_login_logo) {
	$app['ui_login_logo'] = $settings->ui_login_logo;
}
if ($_REQUEST['client']) {
	if (is_array($config['app']['ui']['login_logos']) && array_key_exists($_REQUEST['client'], $config['app']['ui']['login_logos'])) {
		$app['ui_login_logo'] = $config['app']['ui']['login_logos'][$_REQUEST['client']];
	}
}

if ($settings->ui_login_bg) {
	if (substr($settings->ui_login_bg, 0, 1) == '#') {
		$app['ui_bg_color'] = $settings->ui_login_bg;
		$app['ui_bg'] = 0;
	} else {
		$app['ui_bg'] = 1;
	}
}

$app['settingsSet'] = json_encode([
	'ui_login_title' => \S::forHTML($settings->ui_login_title),
	'ui_login_logo' => $app['ui_login_logo'] ?: '',
	'ui_login_text' => nl2br($settings->ui_login_text),
	'ui_bg' => $app['ui_bg'],
	'ui_bg_color' => oneOrZero($app['ui_bg_color']),
	'ui_display_language_menu' => oneOrZero($settings->ui_display_language_menu),
	'androidAppURL' => isset($config['ui']['android_app_url']) ? $config['ui']['android_app_url'] : 'market://details?id=com.afian.FileRun',
	'signUpEnabled' => oneOrZero($app['signUpEnabled']),
	'ssoEnabled' => oneOrZero($app['ssoEnabled']),
	'ssoOnly' => oneOrZero($app['ssoOnly']),
	'passwordRecoveryEnabled' => $settings->auth_plugin ? 0 : oneOrZero($settings->password_recovery_enable),
	'languages' => $app['languages'],
	'selectedLang' => $app['currentLanguage'],
	'message' => $app['message'] ?: false
]);

\FileRun::displaySmartyPage();
if (\FileRun::isFree()) {
	echo '<div style="display:block !important;font-size:11px !important;position:fixed !important;bottom:0px !important;right:0px !important;background-color:#EEEEEE !important;border-top-left-radius:2px;color:black !important;padding:3px;z-index:99999 !important;">Website powered by <a href="https://www.filerun.com" style="color:#3079ED !important;font-size:11px !important;" target="_blank">FileRun</a></div>';

}
echo "\n\n</body></html>";


//Some maintenance
if (!\FileRun::isFree()) {
	Users::deactivateExpiredUsers();
	Users::markPasswordsAsExpired();
}
UserGuests::deleteExpired();
Log::clearOld();
Files\Logging\Utils::clearOld();
WebLinks::deleteExpired();