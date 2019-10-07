<?php
namespace FileRun;
Lang::setSection("Admin: Tools: Import Users");
\FileRun::blockIfFree();
\FileRun::blockIfFreelancer();

if (!Perms::isSuperUser()) {
	if (!Perms::isIndependentAdmin()) {
		jsonFeedback(false, "You are not allowed to use this function.");
	}
}

if ($config['misc']['demoMode']) {
	jsonFeedback(false, "Action unavailable in this demo version of the software!");
}

$importColumns = [];
foreach ($auth->currentUserInfo as $field => $value) {
	if ($field != 'id') {
		$importColumns[] = $field;
	}
}

$maxUsers = false;
if (Perms::isIndependentAdmin()) {
	$maxUsers = Perms::getOne('admin_max_users');
}

$fileName = "import_users_".$auth->currentUserInfo['id'].".csv";
$filePath = gluePath($config['path']['temp'], $fileName);
if (!is_file($filePath)) {
	jsonFeedback(false, "The imported file was not found!");
}

$delimiter = \S::fromHTML($_POST['delimiter']);
$clearTextPass = \S::fromHTML($_POST['clear_text_pass']);
if ($delimiter == "comma") {
	$delimiterChar = ",";
} else if ($delimiter == "semicolon") {
	$delimiterChar = ";";
} else if ($delimiter == "tab") {
	$delimiterChar = "\t";
}
require($config['path']['classes']."/csv.lib.php");
$csv = new \parseCSV();
$csv->heading = false;
$csv->delimiter = $delimiterChar;
$csv->parse($filePath);

if (count($csv->data) < 2) {
	jsonFeedback(false, "Failed to parse file contents!");
}

$roleId = \S::fromHTML($_POST['role']);
$offset = \S::fromHTML($_POST['offset']);
$gen_pass = \S::fromHTML($_POST['gen_pass']) ? true : false;
$require_password_change = \S::fromHTML($_POST['require_password_change']) ? true : false;

$mappings = [];
foreach ($_POST['mappings'] as $key => $val) {
	if ($val != "") {
		$mappings[$val] = $key;
	}
}

if (!array_key_exists("username", $mappings)) {
	if (!array_key_exists("username_and_email", $mappings)) {
		jsonFeedback(false, "You need to map a column for the username!");
	}
}
if (!$gen_pass) {
	if (!array_key_exists("password", $mappings)) {
		jsonFeedback(false, "You need to map a column for the password!");
	}
}
if (!array_key_exists("name", $mappings) && !array_key_exists("name2", $mappings)) {
	jsonFeedback(false, "You need to map at least one column for the name!");
}
$roleInfo = UserRoles::getInfo($roleId);
if (!$roleInfo) {
	jsonFeedback(false, "The selected role was not found!");
}

if (!@unlink($filePath)) {
	jsonFeedback(false, "Failed to delete temporary file from server! Please manually delete the file ".$filePath);
}

$feedback = [];
$pp = new \PassPolicy();
$d = Users::getTable();

$size = count($csv->data);

for ($i = $offset-1; $i < $size; $i++) {
	$record = $csv->data[$i];

	$defaults = [
		'owner' => Perms::isIndependentAdmin() ? $auth->currentUserInfo['id'] : NULL,
		'registration_date' => 'NOW()',
		'last_pass_change' => 'NOW()',
		'activated' => 1,
		'require_password_change' => $require_password_change ? 1 : 0,
		'failed_login_attempts' => 0
	];

	$userInfo = [];
	foreach($importColumns as $fieldName) {
		$value = NULL;
		if (array_key_exists($fieldName, $mappings)) {
			$value = \S::convert2UTF8($record[$mappings[$fieldName]]);
		}
		if ($fieldName == 'username' || $fieldName == 'email') {
			if ($value == NULL) {
				if (array_key_exists('username_and_email', $mappings)) {
					$value = \S::convert2UTF8($record[$mappings['username_and_email']]);
				}
			}
		} else if ($fieldName == 'password') {
			if ($gen_pass) {
				$clearPass = $pp->generate();
				$value = Auth::hashPassword($clearPass);
			} else {
				if ($clearTextPass) {
					$clearPass = $value;
					$value = Auth::hashPassword($value);
				} else {
					$clearPass = '[encrypted]';
				}
			}
		} else {
			if ($value == NULL) {
				if (isset($defaults[$fieldName])) {
					$value = $defaults[$fieldName];
				} else {
					if (isset($config['app']['signup']['defaults'][$fieldName])) {
						$value = $config['app']['signup']['defaults'][$fieldName];
					}
				}
			}
		}
		if ($value != NULL) {
			$userInfo[$fieldName] = $value;
		}
	}

	$userPerms = [
		'role' => $roleId,
		'admin_type' => NULL,
		'admin_users' => 0,
		'admin_roles' => 0,
		'admin_notifications' => 0,
		'admin_logs' => 0,
		'admin_metadata' => 0,
		'admin_over' => '',
		'admin_max_users' => 0,
		'admin_homefolder_template' => '',
		'homefolder' => Perms::applyPathTemplate($roleInfo['homefolder'], $userInfo),
		'space_quota_max' => 0,
		'space_quota_current' => 0,
		'readonly' => $config['app']['signup']['defaults']['perms']['readonly'] ?? 0,
		'upload' => $config['app']['signup']['defaults']['perms']['upload'] ?? 1,
		'upload_max_size' => $config['app']['signup']['defaults']['perms']['upload_max_size'] ?? 1,
		'upload_limit_types' => $config['app']['signup']['defaults']['perms']['upload_limit_types'] ?? 1,
		'download' => $config['app']['signup']['defaults']['perms']['download'] ?? 1,
		'download_folders' => $config['app']['signup']['defaults']['perms']['download_folders'] ?? 1,
		'read_comments' => $config['app']['signup']['defaults']['perms']['read_comments'] ?? 1,
		'write_comments' => $config['app']['signup']['defaults']['perms']['write_comments'] ?? 1,
		'email' => $config['app']['signup']['defaults']['perms']['email'] ?? 1,
		'weblink' => $config['app']['signup']['defaults']['perms']['weblink'] ?? 1,
		'share' => $config['app']['signup']['defaults']['perms']['share'] ?? 1,
		'share_guests' => $config['app']['signup']['defaults']['perms']['share_guests'] ?? 1,
		'metadata' => $config['app']['signup']['defaults']['perms']['metadata'] ?? 1,
		'file_history' => $config['app']['signup']['defaults']['perms']['file_history'] ?? 1,
		'users_may_see' => $config['app']['signup']['defaults']['perms']['users_may_see'] ?? '-ALL-',
		'change_pass' => $config['app']['signup']['defaults']['perms']['change_pass'] ?? 1,
		'edit_profile' => $config['app']['signup']['defaults']['perms']['edit_profile'] ?? 1
	];

	//group from CSV file
	if (array_key_exists('group_names', $mappings) && $record[$mappings['group_names']]) {
		$groupNames = $record[$mappings['group_names']];
	} else {
		$groupNames = false;
	}


	if (!$lx->c(true)) {
		$feedback[] = Lang::t("You cannot create user accounts any more, the limit was reached!", "Admin: Users");
		break;
	}

	if ($maxUsers) {
		$ownedUsersCount = $d->selectOneCol("COUNT(id)", ["owner", "=", $auth->currentUserInfo['id']]);
		if ($ownedUsersCount >= $maxUsers) {
			$feedback[] = Lang::t("You cannot create user accounts any more, the limit was reached!", "Admin: Users");
			break;
		}
	}

	if (strlen($userInfo['username']) < 1) {
		$feedback[] = Lang::t("Record %1: The username is missing! The record was skipped.", false, [$i]);
	} else if (strlen($userInfo['name']) < 1) {
		$feedback[] = Lang::t("Record %1: The name is missing! The record was skipped.", false, [$i]);
	} else if (strlen($userInfo['password']) < 1) {
		$feedback[] = Lang::t("Record %1: The password is missing! The record was skipped.", false, [$i]);
	} else {
		$usernameInUse = Users::isUsernameInUse($userInfo['username']);
		if ($usernameInUse) {
			$feedback[] = Lang::t("Record %1: The username \"%2\" is already in use! The record was skipped.", false, [$i, $userInfo['username']]);
		} else {
			$rs = $d->insert($userInfo);
			if (!$rs) {
				$feedback[] = Lang::t("Record %1: Failed to insert record for %2", false, [$i, $userInfo['username']]);
			} else {
				$uid = $d->lastInsertId();
				$rs = Perms::setPerms($uid, $userPerms);
				if (!$rs) {//delete user if permission record not set
					$d->deleteById($uid);
					$feedback[] = Lang::t("Record %1: Failed to set permission for %2", false, [$i, $userInfo['username']]);
				} else {
					Log::add(false, "user_added", [
						"uid" => $uid,
						"user_info" => $userInfo,
						"permissions" => $userPerms,
						"method" => "csv_import"
					]);

					if ($_POST['notify'] && strlen($userInfo['email']) > 0) {
						$tpl = Notifications\Templates::parse("account_notification", ['From', 'FromName', 'BCC', 'Subject', 'Body']);
						$userInfo['password'] = $clearPass;
						$smarty = \FileRun::getSmarty();
						$smarty->assign("info", $userInfo);
						$app['config'] = $config;
						$app['settings'] = $settings->data;
						$app['url']['root'] = $config['url']['root'];
						$smarty->assign("app", $app);
						$from = $smarty->fetch("string:".$tpl['From']);
						$fromName = $smarty->fetch("string:".$tpl['FromName']);
						$subject = $smarty->fetch("string:".$tpl['Subject']);
						$body = $smarty->fetch("string:".$tpl['Body']);

						$mail = new Utils\Email;
						$mail->setFrom($from, $fromName);
						$mail->Subject = $subject;
						$mail->Body = $body;
						if (strlen($tpl['BCC']) > 3) {
							$mail->addBCC($tpl['BCC']);
						}
						$mail->addAddress($userInfo['email']);
						@$mail->send();
					}

					$feedback[] = Lang::t('Record %1: User "%2" has been added. Password: %3', false, array($i, $userInfo['username'], \S::safeHTML($clearPass)));
					if ($roleInfo['create_folder']) {
						$perms = Perms::getPerms($uid);
						if ($perms['homefolder']) {
							if (!is_dir($perms['homefolder'])) {
								if (!\FM::createPath($perms['homefolder'])) {
									$feedback[] = Lang::t("&nbsp; * Failed to create home folder for user %2", false, [$i, $userInfo['username']]);
								}
							}
						}
					}

					$add2groups = [];
					if ($groupNames) {
						$groupNames = explode(';', $groupNames);
						foreach ($groupNames as $groupName) {
							$gid = UserGroups::getIdByName($groupName);
							if ($gid) {
								$add2groups[] = $gid;
							}
						}
					}
					if ($_POST['groups']) {
						$parts = explode("|", \S::fromHTML($_POST['groups']));
						foreach ($parts as $part) {
							$rs = explode(":", $part);
							if ($rs[0] == "group") {
								$add2groups[] = $rs[1];
							}
						}
					}
					foreach ($add2groups as $gid) {
						$rs = UserGroups::addUserToGroup($uid, $gid);
						if (!$rs) {
							$feedback[] = Lang::t("&nbsp; * Failed to add user %2 to group %3", false, array($i, $userInfo['username'], $rs[1]));
						}
					}
				}
			}
		}
	}
}

jsonFeedback(true, implode("<br>", $feedback));
