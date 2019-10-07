<?php
namespace FileRun;
Lang::setSection("Admin: Users");

if (!Perms::canManageUsers()) {
	jsonFeedback(false, 'You are not allowed to manage users.');
}

if (\FileRun::isFree() && !$lx->c()) {
	\FileRun::displayFreeLicenseLimitPage();
	exit();
}

if ($_GET['action'] == "add") {
	if ($config['misc']['demoMode']) {
		jsonFeedback(false, 'Action unavailable in this demo version of the software!');
	} else {
		if (!$auth->hasValidCookie(true)) {
			jsonFeedback(false, 'Please reload the browser and try again.');
		}

		$d = Users::getTable();

		if (!$lx->c()) {
			jsonFeedback(false, 'You cannot create user accounts any more, the limit was reached!');
		}
		if (Perms::isIndependentAdmin()) {
			$maxUsers = Perms::getOne('admin_max_users');
			if ($maxUsers > 0) {
				$ownedUsersCount = $d->selectOneCol("COUNT(id)", array("owner", "=", $auth->currentUserInfo['id']));
				if ($ownedUsersCount >= $maxUsers) {
					jsonFeedback(false, 'You cannot create user accounts any more, the limit was reached!');
				}
			}
		}
		$data = array();
		$data['username'] = strtolower(trim(\S::fromHTML($_POST['unm'])));
		if (Perms::isIndependentAdmin()) {
			$data['owner'] = $auth->currentUserInfo['id'];
		}
		$data['password'] = trim(\S::fromHTML($_POST['pwd']));
		$data['last_pass_change'] = 'NOW()';
		$data['activated'] = 1;
		$data['require_password_change'] = (\S::fromHTML($_POST['require_password_change']) ? 1 : 0);

		$data['two_step_enabled'] = (\S::fromHTML($_POST['two_step_enabled']) ? 1 : 0);
		$data['two_step_secret'] = '';
		$data['last_otp'] = '';

		$data['expiration_date'] = Utils\Date::HTMLDate2MySQL(\S::fromHTML($_POST['expiration_date']), false, "/", array("y" => 2, "m" => 0, "d" => 1));
		if (!$data['expiration_date']) {
			$data['expiration_date'] = 'NULL';
		}
		$data['registration_date'] = 'NOW()';
		
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
		$data['receive_notifications'] = ($_POST['receive_notifications'] && strlen($data['email']) > 0) ? 1 : 0;
		
		$perms['role'] = trim(\S::fromHTML($_POST['perms']['role']));
		if ($perms['role'] == "-") {
			$perms['role'] = 'NULL';
		} else {
			$perms['role'] = intOrNULL($perms['role']);
		}
		
		$adminOver = \S::fromHTML($_POST['perms']['admin_over']);
		if ($adminOver == "-ALL-") {
			$perms['admin_over'] = "-ALL-";
		} else {
			$parts = explode("|", $adminOver);
			$tmp = array();
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

		
	/* Set Home Folder */
		$createHomeFolder = false;
		$post_homefolder = trim(\S::fromHTML($_POST['perms']['homefolder']));
		$post_homefolder = \FM::normalizePath($post_homefolder);

		$roleInfo = false;
		if ($perms['role'] > 0) {
			if (Perms::getOne('admin_homefolder_template')) {
				$perms['homefolder'] = Perms::applyPathTemplate(Perms::getOne('admin_homefolder_template'), $data, $auth->currentUserInfo);
				$createHomeFolder = true;
			} else {
				$roleInfo = UserRoles::getInfo($perms['role']);
				if (Perms::isIndependentAdmin()) {
					$roleInfo['homefolder'] = gluePath(Perms::getHomeFolder(), $roleInfo['homefolder']);
				}
				$perms['homefolder'] = Perms::applyPathTemplate($roleInfo['homefolder'], $data);
				if ($roleInfo['create_folder']) {
					$createHomeFolder = true;
				}
			}
			$perms['space_quota_max'] = $roleInfo['space_quota_max'];
		} else {
			if (Perms::getOne('admin_homefolder_template')) {
				$perms['homefolder'] = Perms::applyPathTemplate(Perms::getOne('admin_homefolder_template'), $data, $auth->currentUserInfo);
				$createHomeFolder = true;
			} else {
				if (Perms::isIndependentAdmin()) {
					$perms['homefolder'] = gluePath(Perms::getHomeFolder(), $post_homefolder);
					$createHomeFolder = true;
				} else {
					$perms['homefolder'] = $post_homefolder;
				}
			}
			$perms['space_quota_max'] = intOrNULL(trim(\S::fromHTML($_POST['perms']['space_quota_max'])));
		}
	/* End Set Home Folder */

		$perms['admin_max_users'] = intOrZero(trim(\S::fromHTML($_POST['perms']['admin_max_users'])));
		$perms['admin_homefolder_template'] = trim(\S::fromHTML($_POST['perms']['admin_homefolder_template']));
		$perms['admin_homefolder_template'] = \FM::normalizePath($perms['admin_homefolder_template']);
		
		$perms['admin_users'] = $_POST['perms']['admin_users'] ? 1 : 0;
		$perms['admin_roles'] = $_POST['perms']['admin_roles'] ? 1 : 0;
		$perms['admin_notifications'] = $_POST['perms']['admin_notifications'] ? 1 : 0;
		$perms['admin_logs'] = $_POST['perms']['admin_logs'] ? 1 : 0;
		$perms['admin_metadata'] = $_POST['perms']['admin_metadata'] ? 1 : 0;
		
		$perms['readonly'] = $_POST['perms']['readonly'] ? 1 : 0;
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
			$tmp = array();
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


		$usernameInUse = Users::isUsernameInUse($data['username']);
		
		$userRequiresSpaceQuota = (Perms::isIndependentAdmin() && Perms::getOne('space_quota_max') > 0 && ($perms['role'] == 'NULL'));
		$invalidSpaceQuota = $userRequiresSpaceQuota && (($perms['space_quota_max'] <= 0 && ($perms['role'] == "NULL")) || ($perms['role'] > 0 && $roleInfo['space_quota_max'] <= 0));



		$subUsersSpaceTotalQuota = 0;

		if ($userRequiresSpaceQuota) {
			$withoutRolesTotalQuotas = $db->GetRow("SELECT SUM(up.space_quota_max) as TotalSpaceQuotas FROM `".Users::$table."` AS u, `".Perms::$table."` AS up WHERE u.id=up.uid AND up.role IS NULL AND u.owner=".$auth->currentUserInfo['id']);
			$withRolesTotalQuota = $db->GetRow("SELECT SUM(ur.space_quota_max) as TotalSpaceQuotas FROM `".Users::$table."` AS u, `".Perms::$table."` AS up, `".UserRoles::$table."` AS ur WHERE u.id=up.uid AND up.role=ur.id AND up.role IS NOT NULL AND  u.owner=".$auth->currentUserInfo['id']);
			$subUsersSpaceTotalQuota = $withoutRolesTotalQuotas['TotalSpaceQuotas']+$withRolesTotalQuota['TotalSpaceQuotas'];

		}

		$pp = new \PassPolicy($data['password']);

		if (strlen($data['username']) < 1) {
			jsonFeedback(false, "Please type a username!", array("unm" => ""));
		} else if (strlen($data['name']) < 1) {
			jsonFeedback(false, "Please type a name!", array("name" => ""));
		} else if (!\S::okUsername($data['username'])) {
			jsonFeedback(false, "Please don't use special characters for the username!", array("unm" => ""));
		} else if ($usernameInUse) {
			jsonFeedback(false, "Username already in use. Please choose another one.", array("unm" => ""));
		} else if (strlen($data['password']) < 1) {
			jsonFeedback(false, "Please type a password!", array("pwd" => ""));
		} else if (!Perms::isSuperUser() && !$pp->validate()) {
			jsonFeedback(false, $pp->errors[0], array("pwd" => ""));
		} else if ($userRequiresSpaceQuota && $invalidSpaceQuota) {
			jsonFeedback(false, "You are required to set a space quota for this user!", array("perms[space_quota_max]" => ""));
		} else if ($userRequiresSpaceQuota && $subUsersSpaceTotalQuota+$perms['space_quota_max'] > Perms::getOne('space_quota_max')) {
			jsonFeedback(false, Lang::t("The maximum space quota you can assign to this user is %1MB!", false, array((Perms::getOne('space_quota_max')-$subUsersSpaceTotalQuota))), array("perms[space_quota_max]" => ""));
		} else if ($perms['admin_type'] == "simple" && $perms['admin_homefolder_template'] && $perms['admin_roles']) {
			jsonFeedback(false, "The permission to manage roles cannot be granted if a \"Home folder template path\" is set.");
		} else if ($perms['admin_type'] == "simple" && $perms['admin_roles'] && $perms['admin_over'] == "") {
			jsonFeedback(false, "If you wish to grant the permission to manage roles, please use the \"Can manage\" option to select at least one group of users.");
		} else if ($perms['admin_type'] == "simple" && $perms['admin_users'] && $perms['admin_over'] == "") {
			jsonFeedback(false, "If you wish to grant the permission to create new user accounts, please use the \"Can manage\" option to select at least one group.");
		} else if (!Perms::isSuperUser() && !Perms::isIndependentAdmin() && Perms::getOne('admin_over') != "-ALL-" && sizeof($_POST['groups']) < 1) {
			jsonFeedback(false, "You are required to select at least one group for the user.");
		} else {
			$unencryptedPass = $data['password'];
			$data['password'] = Auth::hashPassword($data['password']);

			$rs = $d->insert($data);
			$uid = $d->lastInsertId();
			if ($rs) {

				$rs = Perms::setPerms($uid, $perms);
				if (!$rs) {//delete user if permission record not set
					$d->deleteById($uid);
					jsonFeedback(false, 'Failed to set permissions for this user! The account was not created.');
				} else {

					Log::add(false, "user_added", array(
						"uid" => $uid,
						"user_info" => $data,
						"permissions" => $perms
					));
					
					if ($createHomeFolder) {
						if (!is_dir($perms['homefolder'])) {
							$contentTemplateFolderPath = $config['path']['data'].'/home_folder_template';
							if (is_dir($contentTemplateFolderPath)) {
								\FM::copyDir($contentTemplateFolderPath, $perms['homefolder']);
							} else {
								\FM::createPath($perms['homefolder']);
							}
						}
					}
					
					//add user to groups
					$gr = \S::fromHTML($_POST['groups']);
					$parts = explode("|", $gr);
					foreach ($parts as $part) {
						$rs = explode(":", $part);
						if ($rs[0] == "group") {
							UserGroups::addUserToGroup($uid, $rs[1]);
						}
					}

					if ($_POST['notify'] && strlen($data['email']) > 0) {
						$tpl = Notifications\Templates::parse("account_notification", ['From', 'FromName', 'BCC', 'Subject', 'Body']);
						$data['password'] = $unencryptedPass;
						$smarty = \FileRun::getSmarty();
						$smarty->assign("info", $data);
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
						$mail->addAddress($data['email']);
						@$mail->send();
					}

					jsonFeedback(true, 'User successfully created.');
				}
			} else {
				jsonFeedback(false, 'Failed to create user!');
			}
		}
	}
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
array_unshift($rolesList, array("-", Lang::t("- None -")));
$app['roles'] = json_encode($rolesList);

$app['user']['perms'] = Perms::getPerms($auth->currentUserInfo['id']);

\FileRun::displaySmartyPage();