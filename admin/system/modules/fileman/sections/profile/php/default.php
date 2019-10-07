<?php
namespace FileRun;
\FileRun::checkAndForceHTTPS();
Lang::setSection("Account Settings");

$canChangePass = ($settings->allow_change_pass && Perms::check('change_pass') && !is_null($auth->currentUserInfo['password']));

$app = [];

if ($canChangePass) {
	if (isset($config['app']['login']['2step']['allow_user_control'])) {
		$app['system']['enable2stepOption'] = $config['app']['login']['2step']['allow_user_control'];
	} else {
		$app['system']['enable2stepOption'] = true;
	}
}

if ($_GET['action'] == 'upload_avatar') {
	if ($config['misc']['demoMode']) {
		jsonFeedback(false, "Action unavailable in this demo version of the software!");
	}
	\FileRun::checkPerms('edit_profile');

	$PHPUploadErrorMessages = [
		'0' => false,
		'1' => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		'2' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		'3' => 'The uploaded file was only partially uploaded.',
		'4' => 'No file was uploaded.',
		'6' => 'Missing a temporary folder.',
		'7' => 'Failed to write file to disk.',
		'8' => 'File upload stopped by extension.'
	];
	$uploadFile = $_FILES['file'];
	$PHPUploadError = $PHPUploadErrorMessages[$uploadFile['error']];
	$tmpPath = $uploadFile['tmp_name'];
	$targetPath = Utils\Avatar::getPath($auth->currentUserInfo['id']);
	if ($PHPUploadError) {
		jsonFeedback(false, Lang::t("Failed to upload image file: %1", false, [$PHPUploadError]));
	}
	$data = file_get_contents($tmpPath);
	$fileExists = is_file($targetPath);
	if ($fileExists) {
		\FM::deleteFile($targetPath);
	}
	$rs = \FM::newFile($targetPath, $data);
	if (!$rs) {
		jsonFeedback(false, Lang::t("Failed to upload image file: %1", false, [\FM::$errMsg]));
	}
	$rs = Users::getTable()->updateById(['avatar' => json_encode(['type' => 'custom'])], $auth->currentUserInfo['id']);
	if (!$rs) {
		jsonFeedback(true, 'Failed to update avatar database record!');
	}
	jsonFeedback(true, 'Profile image successfully updated');
}

$oauth2Sess = new OAuth2\SessionManager;

if ($_GET['action'] == 'save') {
	if ($config['misc']['demoMode']) {
		jsonFeedback(false, "Action unavailable in this demo version of the software!");
	}
	if (!$auth->hasValidCookie(true)) {
		jsonFeedback(false, 'Please reload the browser and try again.');
	}

	$data = [];

	if (Perms::check('edit_profile')) {
		$name = trim(\S::stripHTMLChars(\S::fromHTML($_POST['name'])));
		if ($name != $auth->currentUserInfo['name']) {
			$data['name'] = $name;
			if (strlen($data['name']) < 1) {
				jsonFeedback(false, 'Please type your name');
			}
		}
		$data['name2'] = trim(\S::stripHTMLChars(\S::fromHTML($_POST['name2'])));
		$email = trim(\S::stripHTMLChars(\S::fromHTML($_POST['email'])));
		if ($email != $auth->currentUserInfo['email']) {
			$data['email'] = $email;
			if (strlen($data['email']) < 4) {
				jsonFeedback(false, 'Please type your e-mail address');
			}
		}
		$data['phone'] = trim(\S::stripHTMLChars(\S::fromHTML($_POST['phone'])));
		$receive_notifications = oneOrZero($_POST['receive_notifications']);
		if ($receive_notifications != $auth->currentUserInfo['receive_notifications']) {
			$data['receive_notifications'] = $receive_notifications;
		}
	}

	if ($canChangePass) {
		if ($app['system']['enable2stepOption']) {
			$two_step_enabled = oneOrZero($_POST['two_step_enabled']);
			if ($two_step_enabled != $auth->currentUserInfo['two_step_enabled']) {
				$data['two_step_enabled'] = $two_step_enabled;
			}
		}
		$current_password = trim(\S::fromHTML($_POST['current_password']));
		if ($current_password) {
			$new_password = trim(\S::fromHTML($_POST['new_password']));
			$confirm_new_password = trim(\S::fromHTML($_POST['confirm_new_password']));
			$passMatch = $auth->verifyPassword($current_password, $auth->currentUserInfo['password']);
			$pp = new \PassPolicy($new_password, $auth->currentUserInfo['id']);
			$encryptedPass = Auth::hashPassword($new_password);
			if (!$passMatch) {
				jsonFeedback(false, "Please type the current password correctly.");
			} else if (strstr($_POST['new_password'], "?")) {
				jsonFeedback(false, "Please do not use question marks (?) for the new password.");
			} else if ($new_password != $confirm_new_password) {
				jsonFeedback(false, "Please retype the new password correctly.");
			} else if ($auth->verifyPassword($new_password, $auth->currentUserInfo['password'])) {
				jsonFeedback(false, "The new password is the same as the current one.");
			} else if (!Perms::isSuperUser() && !$pp->validate()) {
				jsonFeedback(false, $pp->errors[0]);
			} else {
				$data['password'] = $encryptedPass;
				$data['require_password_change'] = 0;
				$data['last_pass_change'] = 'NOW()';
			}
		}
	}

	$success = true;
	$msg = [];
	if (count($data) > 0) {
		$rs = Users::getTable()->updateById($data, $auth->currentUserInfo['id']);
		if ($rs) {
			if ($data['password']) {
				Log::add(false, "password_changed", ["data" => $data]);
			}
			$msg[] = "The changes has been successfully saved";
		} else {
			$success = false;
			$msg[] = "Failed to save the changes!";
		}
	}

	if (is_array($_POST['revoke']) && count($_POST['revoke']) > 0) {
		$count = 0;
		foreach($_POST['revoke'] as $k =>  $sessionId) {
			$sessionId = \S::fromHTML($sessionId);
			$rs = $oauth2Sess->revoke($sessionId);
			if ($rs) {
				$count++;
			}
		}
		if ($count > 0) {
			$msg[] = Lang::t('Access revoked for %1 apps', false, [$count]);
		}
	}
	if (count($msg) == 0) {
		$msg[] = 'No changes made';
	}
	jsonFeedback($success, $msg);
}

$app['system']['showApps'] = (boolean) $settings->oauth2;
$app['system']['allowUserToEdit'] = Perms::check('edit_profile');
$app['system']['allowUserToChangePass'] = $canChangePass;
$app['system']['csrf_token'] = $auth->sess->getCSRFToken();

$userInfo = [
	'id' => $auth->currentUserInfo['id'],
	'name' => $auth->currentUserInfo['name'],
    'name2' => $auth->currentUserInfo['name2'],
	'email' => $auth->currentUserInfo['email'],
	'phone' => $auth->currentUserInfo['phone'],
	'receive_notifications' => (boolean) $auth->currentUserInfo['receive_notifications'],
	'two_step_enabled' => (boolean) $auth->currentUserInfo['two_step_enabled']
];
if (!isEmptyMySQLDate($auth->currentUserInfo['last_pass_change'])) {
	$userInfo['last_pass_change'] = Utils\Date::MySQLTimeDiff($auth->currentUserInfo['last_pass_change']);
}

$rs = $oauth2Sess->getClients($auth->currentUserInfo['id']);
if (count($rs) == 0) {
	$app['system']['showApps'] = false;
} else {
	$app['connectedApps'] = json_encode($rs);
}

$app['system_json'] = json_encode($app['system']);
$app['userInfo'] = json_encode($userInfo);
\FileRun::displaySmartyPage();