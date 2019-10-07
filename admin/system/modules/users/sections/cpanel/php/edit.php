<?php
namespace FileRun;
Lang::setSection("Admin: Users");

$UID = \S::fromHTML($_POST['id']);

$userInfo = Users::getInfo($UID);
$userInfo['fullName'] = Users::formatFullName($userInfo);
$userInfo['perms'] = Perms::getPerms($userInfo['id']);
$userInfo['groups'] = UserGroups::selectOneUsersGroups($userInfo['id']);
if (!Perms::canManageUser($userInfo, $userInfo['perms'], $userInfo['groups'])) {
	jsonFeedback(false, "You are not allowed to manage this user.");
}
$userInfo['isGuest'] = UserGuests::isGuest($userInfo['perms']['role']);
if ($userInfo['isGuest']) {
	$userInfo['guestAccessURL'] = UserGuests::getGuestAccessURL($userInfo['id']);
}

if ($_GET['action'] == "save") {
	$usd = Users::getTable();

	$passwordChanged = false;
	$usernameChanged = false;
	if ($config['misc']['demoMode']) {
		jsonFeedback(false, "Action unavailable in this demo version of the software!");
	} else {

		if (!$auth->hasValidCookie(true)) {
			jsonFeedback(false, 'Please reload the browser and try again.');
		}

		$data['username'] = strtolower(trim(\S::fromHTML($_POST['unm'])));
		if ($data['username'] != $userInfo['username']) {
			$usernameChanged = true;
		}

		$data['require_password_change'] = (\S::fromHTML($_POST['require_password_change']) ? 1 : 0);

		$postedPassword = trim(\S::fromHTML($_POST['pwd']));
		if (strlen($postedPassword) > 0) {
			$passwordChanged = true;
			$pp = new \PassPolicy($postedPassword);
			$data['password'] = $postedPassword;
			$data['last_pass_change'] = 'NOW()';
		}

		$data['two_step_enabled'] = (\S::fromHTML($_POST['two_step_enabled']) ? 1 : 0);
		if (isset($_POST['two_step_reset'])) {
			$data['two_step_secret'] = '';
			$data['last_otp'] = '';
		}

		$data['expiration_date'] = Utils\Date::HTMLDate2MySQL(\S::fromHTML($_POST['expiration_date']), false, "/", ["y" => 2, "m" => 0, "d" => 1]);
		if (!$data['expiration_date']) {
			$data['expiration_date'] = 'NULL';
		}
		if ($UID != 1) {
			$data['activated'] = $_POST['deactivate'] ? 0 : 1;
		} else {
			$data['activated'] = 1;
		}
		if (!$userInfo['activated'] && $data['activated']) {
			$data['failed_login_attempts'] = 0;
		}
		$data['name'] = trim(\S::fromHTML($_POST['name']));
        $data['name2'] = trim(\S::fromHTML($_POST['name2']));
		$data['phone'] = trim(\S::fromHTML($_POST['phone']));
		$data['company'] = trim(\S::fromHTML($_POST['company']));
		$data['email'] = trim(\S::fromHTML($_POST['email']));
		$data['website'] = trim(\S::fromHTML($_POST['website']));
		$data['logo_url'] = trim(\S::fromHTML($_POST['logo_url']));
		$data['description'] = \S::fromHTML($_POST['description']);
		if (Perms::isSuperUser()) {
			$perms['admin_type'] = trim(\S::fromHTML($_POST['perms']['admin_type']));
		} else {
			$perms['admin_type'] = "";
		}

		$perms['role'] = trim(\S::fromHTML($_POST['perms']['role']));
		if ($perms['role'] == "-") {
			$perms['role'] = 'NULL';
		} else {
			$perms['role'] = intOrNULL($perms['role']);
		}
		
		if ($UID == 1) {
			$perms['admin_over'] = "-ALL-";
		} else {
			$adminOver = \S::fromHTML($_POST['perms']['admin_over']);
			if ($adminOver == "-ALL-") {
				$perms['admin_over'] = "-ALL-";
			} else {
				$parts = explode("|", $adminOver);
				$tmp = [];
				foreach ($parts as $part) {
					$rs = explode(":", $part);
					if ($rs[0] == "group") {
						$tmp[] =  $rs[1];
					}
				}
				if (is_array($tmp)) {
					$tmp = array_unique($tmp);
				}
				if (sizeof($tmp) > 0) {
					$perms['admin_over'] = serialize($tmp);
				} else {
					$perms['admin_over'] = "";
				}
			}
		}
		
		$data['receive_notifications'] = $_POST['receive_notifications'] ? 1 : 0;
		
	/* Set Home Folder */

		$post_homefolder = trim(\S::fromHTML($_POST['perms']['homefolder']));
		$post_homefolder = \FM::normalizePath($post_homefolder);
		$oldHomeFolder = $userInfo['perms']['homefolder'];

		$roleInfo = false;
		if ($perms['role'] > 0) {
			$roleInfo = UserRoles::getInfo($perms['role']);
			if (Perms::getOne('admin_homefolder_template')) {
				$perms['homefolder'] = Perms::applyPathTemplate(Perms::getOne('admin_homefolder_template'), $data, $auth->currentUserInfo);
			} else {
				if (Perms::isIndependentAdmin()) {
					$hf = gluePath(Perms::getHomeFolder(), $roleInfo['homefolder']);
				} else {
					if ($userInfo['owner']) {//user created by indep
						$ownerPerms = Perms::getPerms($userInfo['owner']);
						if ($ownerPerms['admin_homefolder_template']) {
							$ownersInfo = Users::getInfo($userInfo['owner'], '*');
							$hf = Perms::applyPathTemplate($ownerPerms['admin_homefolder_template'], $data, $ownersInfo);
						} else {
							//indep has no aht, append path
							$hf = gluePath($ownerPerms['homefolder'], $roleInfo['homefolder']);
						}
					} else {
						$hf = $roleInfo['homefolder'];
					}
				}
				$perms['homefolder'] = Perms::applyPathTemplate($hf, $data);
			}
			$perms['space_quota_max'] = $roleInfo['space_quota_max'];
		} else {
			if (Perms::getOne('admin_homefolder_template')) {
				$perms['homefolder'] = Perms::applyPathTemplate(Perms::getOne('admin_homefolder_template'), $data, $auth->currentUserInfo);
			} else {
				if (Perms::isIndependentAdmin()) {
					$perms['homefolder'] = gluePath(Perms::getHomeFolder(), $post_homefolder);
				} else {//superuser or simple admin users without templates have full control over the path
					$perms['homefolder'] = $post_homefolder;
				}
			}
			$perms['space_quota_max'] = intOrNULL(trim(\S::fromHTML($_POST['perms']['space_quota_max'])));
		}
	/* End Set Home Folder */

		$perms['admin_max_users'] = intOrZero(trim(\S::fromHTML($_POST['perms']['admin_max_users'])));
		$oldAdminHomeFolderTpl = $userInfo['perms']['admin_homefolder_template'];
		$perms['admin_homefolder_template'] = trim(\S::fromHTML($_POST['perms']['admin_homefolder_template']));
		$perms['admin_homefolder_template'] = \FM::normalizePath($perms['admin_homefolder_template']);

		$perms['admin_users'] = $_POST['perms']['admin_users'] ? 1 : 0;
		$perms['admin_roles'] = $_POST['perms']['admin_roles'] ? 1 : 0;
		$perms['admin_notifications'] = $_POST['perms']['admin_notifications'] ? 1 : 0;
		$perms['admin_logs'] = $_POST['perms']['admin_logs'] ? 1 : 0;
		$perms['admin_metadata'] = $_POST['perms']['admin_metadata'] ? 1 : 0;
		
		$perms['readonly'] = $_POST['perms']['changes'] ? 0 : 1;
		$perms['upload'] = $_POST['perms']['upload'] ? 1 : 0;
		$perms['upload_max_size'] = intOrNULL(trim(\S::fromHTML($_POST['perms']['upload_max_size'])));
		$perms['upload_limit_types'] = trim(\S::fromHTML($_POST['perms']['upload_limit_types']));
		$perms['download'] = $_POST['perms']['download'] ? 1 : 0;
		$perms['download_folders'] = $_POST['perms']['download_folders'] ? 1 : 0;
		$perms['read_comments'] = $_POST['perms']['read_comments'] ? 1 : 0;
		$perms['write_comments'] = $_POST['perms']['write_comments'] ? 1 : 0;
		$perms['email'] = $_POST['perms']['email'] ? 1 : 0;
		$perms['weblink'] = $_POST['perms']['weblink'] ? 1 : 0;
		$perms['share'] = $_POST['perms']['share'] ? 1 : 0;
		$perms['share_guests'] = $_POST['perms']['share_guests'] ? 1 : 0;
		$perms['metadata'] = $_POST['perms']['metadata'] ? 1 : 0;
		$perms['file_history'] = $_POST['perms']['file_history'] ? 1 : 0;
		$perms['change_pass'] = $_POST['perms']['change_pass'] ? 1 : 0;
		$perms['edit_profile'] = $_POST['perms']['edit_profile'] ? 1 : 0;
		
		$usersMaySee = \S::fromHTML($_POST['users_may_see']);
		if ($usersMaySee == "-ALL-") {
			$perms['users_may_see'] = "-ALL-";
		} else {
			$parts = explode("|", $usersMaySee);
			$tmp = [];
			foreach ($parts as $part) {
				$rs = explode(":", $part);
				if ($rs[0] == "group") {
					$tmp['groups'][] =  $rs[1];
				} else if ($rs[0] == "user") {
					$tmp['users'][] = $rs[1];
				}
			}
			if (is_array($tmp['users'])) {
				$tmp['users'] = array_unique($tmp['users']);
			}
			if (is_array($tmp['groups'])) {
				$tmp['groups'] = array_unique($tmp['groups']);
			}
			$perms['users_may_see'] = serialize($tmp);
		}

		
		$userRequiresSpaceQuota = (Perms::isIndependentAdmin() && Perms::getOne('space_quota_max') > 0 && $perms['role'] == "NULL");
		$invalidSpaceQuota = $userRequiresSpaceQuota && (($perms['space_quota_max'] <= 0 && ($perms['role'] == "NULL" || $perms['role'] == "")) || ($perms['role'] > 0 && $roleInfo['space_quota_max'] <= 0));


		$subUsersSpaceTotalQuota = 0;

		if ($userRequiresSpaceQuota) {
			$withoutRolesTotalQuotas = $db->GetRow("SELECT SUM(up.space_quota_max) as TotalSpaceQuotas FROM `".Users::$table."` AS u, `".Perms::$table."` AS up WHERE u.id=up.uid AND up.role IS NULL AND  u.id != ".$UID." AND u.owner=".$auth->currentUserInfo['id']);
			$withRolesTotalQuota = $db->GetRow("SELECT SUM(ur.space_quota_max) as TotalSpaceQuotas FROM `".Users::$table."` AS u, `".Perms::$table."` AS up, `".UserRoles::$table."` AS ur WHERE u.id=up.uid AND up.role=ur.id AND up.role IS NOT NULL AND u.id != ".$UID." AND u.owner=".$auth->currentUserInfo['id']);
			$subUsersSpaceTotalQuota = $withoutRolesTotalQuotas['TotalSpaceQuotas']+$withRolesTotalQuota['TotalSpaceQuotas'];
		}

		$usernameInUse = Users::isUsernameInUse($data['username'], $UID);

		if (strlen($data['username']) < 1) {
			jsonFeedback(false, "Please type a username!", ["unm" => ""]);
		} else if (strlen($data['name']) < 1) {
			jsonFeedback(false, "Please type a name!", ["name" => ""]);
		} else if (!\S::okUsername($data['username'])) {
			jsonFeedback(false, "Please don't use special characters for the username!", ["unm" => ""]);
		} else if ($usernameInUse) {
			jsonFeedback(false, "Username already in use. Please choose another one.", ["unm" => ""]);
		} else if ($passwordChanged && !Perms::isSuperUser() && !$pp->validate()) {
			jsonFeedback(false, $pp->errors[0], ["pwd" => ""]);
		} else if ($userRequiresSpaceQuota && $invalidSpaceQuota) {
			jsonFeedback(false, "You are required to set a space quota for this user!", "error");
		} else if ($userRequiresSpaceQuota && $subUsersSpaceTotalQuota+$perms['space_quota_max'] > Perms::getOne('space_quota_max')) {
			jsonFeedback(false, Lang::t("The maximum space quota you can assign to this user is %1MB!", false,  [(Perms::getOne('space_quota_max')-$subUsersSpaceTotalQuota)]), "error");
		} else if ($perms['admin_type'] == "simple" && $perms['admin_roles'] && $perms['admin_homefolder_template']) {
			jsonFeedback(false, "The permission to manage roles cannot be granted if a \"Home folder template path\" is set.", "error");
		} else if ($perms['admin_type'] == "simple" && $perms['admin_roles'] && $perms['admin_over'] == "") {
			jsonFeedback(false, "If you wish to grant the permission to manage roles, please use the \"Can manage\" option to select at least one group of users.", "error");
		} else if ($perms['admin_type'] == "simple" && $perms['admin_users'] && $perms['admin_over'] == "") {
			jsonFeedback(false, "If you wish to grant the permission to create new user accounts, please use the \"Can manage\" option to select at least one group.", "error");
		} else if (!Perms::isSuperUser() && !Perms::isIndependentAdmin() && Perms::getOne('admin_over') != "-ALL-" && sizeof($_POST['groups']) < 1) {
			jsonFeedback(false, "You are required to select at least one group for the user.", "error");
		} else {

			UserGroups::removeUserFromAllGroups($UID);
			$gr = \S::fromHTML($_POST['groups']);
			$parts = explode("|", $gr);
			foreach ($parts as $part) {
				$rs = explode(":", $part);
				if ($rs[0] == "group") {
					UserGroups::addUserToGroup($UID, $rs[1]);
				}
			}
			$unencryptedPass = false;
			if ($passwordChanged) {
				$unencryptedPass = $data['password'];
				$data['password'] = Auth::hashPassword($data['password']);
			}

			$rs = $usd->update($data, ["id", "=", $usd->q($UID)]);
			if ($rs) {
				if ($passwordChanged) {
					if ($UID != $auth->currentUserInfo['id']) {
						$auth->sess->clearAllTokensForUser($userInfo['username']);
					}
				}

				Perms::setPerms($UID, $perms);

				if ($perms['homefolder'] != $oldHomeFolder) {
					Perms::updateHomeFolder($perms['homefolder'], $oldHomeFolder, $UID);

					if ($userInfo['perms']['admin_type'] == 'indep') {//user is indep
						if (!$userInfo['perms']['admin_homefolder_template']) {//user doesn't have an admin template
							//subusers depend on this indeps' home folder (appending)
							//so propagate change
							$rs = $usd->select('*', ['owner', '=', $usd->q($UID)]);
							foreach ($rs as $u) {
								$up = Perms::getPerms($u['id']);
								$hf = str_replace($oldHomeFolder, $perms['homefolder'], $up['homefolder']);
								Perms::updateHomeFolder($hf, $up['homefolder'], $u['id']);
							}
						}
					}
				}

				if ($perms['admin_homefolder_template']) {//user has an aht
					$updateSubUsers = false;
					if ($perms['admin_homefolder_template'] != $oldAdminHomeFolderTpl) {
						$updateSubUsers = true;
					}
					if (stristr($userInfo['perms']['admin_homefolder_template'], '{admusername}') !== false) {//template contains {admusername} variable
						if ($usernameChanged) {
							$updateSubUsers = true;
						}
					}
					if ($updateSubUsers) {
						//admin home folder template has changed, update all created users home folders
						$rs = $usd->select('*', ['owner', '=', $usd->q($UID)]);
						foreach ($rs as $u) {
							$up = Perms::getPerms($u['id']);
							$hf = Perms::applyPathTemplate($perms['admin_homefolder_template'], $u, $data);
							Perms::updateHomeFolder($hf, $up['homefolder'], $u['id']);
						}
					}
				}

				Log::add(false, 'user_edited', [
					"uid" => $UID,
					"user_info" => $data,
					"permissions" => $perms
				]);

				if ($_POST['notify'] && ($usernameChanged || $passwordChanged) && strlen($data['email']) > 0) {
					$tpl = Notifications\Templates::parse("account_login_change", ['From', 'FromName', 'BCC', 'Subject', 'Body']);
					$data['password'] = $unencryptedPass;
					$smarty = \FileRun::getSmarty();
					$smarty->assign("info", $data);
					$app = [
						'config' => $config,
						'settings' => $settings->data,
						'passwordChanged' => $passwordChanged,
						'usernameChanged' => $usernameChanged
					];
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
					$mail->addAddress($data['email']);
					@$mail->send();
				}

				jsonFeedback(true, 'User successfully updated!');
			} else {
				jsonFeedback(false, 'Failed to update user!');
			}
		}
	}
}


$g = [];
foreach ($userInfo['groups'] as $gid) {
	$name = UserGroups::getNameById($gid);
	if ($name) {
		$g[] = [
			"id" => $gid,
			"name" => $name,
			"type" => "group"
		];
	}
}
$userInfo['groups'] = $g;

if ($userInfo['perms']['users_may_see'] != "-ALL-") {
	if (is_array($userInfo['perms']['users_may_see'])) {
		$t = [];
		if (is_array($userInfo['perms']['users_may_see']['groups'])) {
			foreach ($userInfo['perms']['users_may_see']['groups'] as $gid) {
				$name = UserGroups::getNameById($gid);
				if ($name) {
					$t[] = ["id" => $gid, "name" => $name, "type" => "group"];
				}
			}
		}
		if (is_array($userInfo['perms']['users_may_see']['users'])) {
			foreach ($userInfo['perms']['users_may_see']['users'] as $uid) {
				$uName = Users::getNameById($uid);
				if ($uName) {
					$t[] = ["id" => $uid, "name" => $uName, "type" => "user"];
				}
			}
		}
		$userInfo['perms']['users_may_see'] = $t;
	} else {
		$userInfo['perms']['users_may_see'] = [];
	}
}

if ($userInfo['perms']['admin_over'] != "-ALL-") {
	if (is_array($userInfo['perms']['admin_over'])) {
		$t = [];
		foreach ($userInfo['perms']['admin_over'] as $gid) {
			$name = UserGroups::getNameById($gid);
			if ($name) {
				$t[] = ["id" => $gid, "name" => $name, "type" => "group"];
			}
		}
		$userInfo['perms']['admin_over'] = $t;
	}
}


if (Perms::isIndependentAdmin()) {
	$userInfo['perms']['homefolder'] = substr($userInfo['perms']['homefolder'], strlen(Perms::getHomeFolder()), strlen($userInfo['perms']['homefolder']));//hide indep admin's root path
}


if ($userInfo['expiration_date']) {
	$userInfo['expiration_date'] = Utils\Date::MySQLDate2HTML($userInfo['expiration_date']);
}

if (!$userInfo['perms']['role']) {
	$userInfo['perms']['role'] = "-";
}

$d = UserRoles::getTable();
if (Perms::isIndependentAdmin()) {
	$filterByOwner = [
		["owner", "=", $auth->currentUserInfo['id']],
		['id', '=', $d->q(UserGuests::getRoleId()), 'OR']
	];
} else {
	$filterByOwner = false;
}
$db->SetFetchMode(\PDO::FETCH_NUM);
$rolesList = $d->select(["id", "name"], $filterByOwner, ["name" => "ASC"]);
array_unshift($rolesList, ["-", Lang::t("- None -")]);
$app['roles'] = json_encode($rolesList);

$app['userInfo'] = json_encode($userInfo);
$app['user']['perms'] = Perms::getPerms($auth->currentUserInfo['id']);

\FileRun::displaySmartyPage();