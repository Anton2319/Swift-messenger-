<?php
namespace FileRun;
\FileRun::checkAndForceHTTPS();
\FileRun::blockIfFree();
Lang::setSection("User Registration");


if ($_GET['action'] == "activate") {
	$userInfo = Users::getInfo(\S::fromHTML($_GET['uid']));
	if (\S::fromHTML($_GET['hash']) == md5($userInfo['id']."-".$userInfo['email'])) {
		if ($settings->user_registration_approval) {
			echo Lang::t("The user account needs to be approved by the administrator.");
			exit();
		} else {
			Users::activate($userInfo['id']);
			siteRedirect("");
		}
	}
}

if (!\FileRun::isSignupAvailable()) {
	siteRedirect("");
}

if ($_GET['action'] == "submit") {
	if ($config['misc']['demoMode']) {
		$feedback = "Action unavailable in this demo version of the software!";
	} else {

		$usd = Users::getTable();

		$data = [];
		$data['username'] = strtolower(trim(\S::fromHTML($_POST['username'])));

		if ($settings->user_registration_generate_passwords) {
			$pp = new \PassPolicy();
			$data['password'] = $pp->generate();
		} else {
			$data['password'] = trim(\S::fromHTML($_POST['password']));
		}
		$data['two_step_enabled'] = $config['app']['signup']['defaults']['two_step_enabled'] ?? 0;
		$data['two_step_secret'] = NULL;
		$data['last_otp'] = NULL;
		$data['last_pass_change'] = 'NOW()';
		$repassword = trim(\S::fromHTML($_POST['repassword']));
		$password = $data['password'];//to be emailed
		$data['cookie'] = NULL;
		$getpuid = \S::fromHTML($_GET['puid']);
		if ($getpuid > 0) {
			$isIndep = Perms::getPerms($getpuid);
			if ($isIndep) {
				$data['owner'] = $getpuid;
			} else {
				$data['owner'] = NULL;
			}
		} else {
			$data['owner'] = NULL;
		}
		$data['registration_date'] = 'NOW()';
		if ($settings->user_registration_approval || $settings->user_registration_email_verification) {
			$data['activated'] = 0;
		} else {
			$data['activated'] = 1;
		}
		$data['expiration_date'] = $config['app']['signup']['defaults']['expiration_date'] ?? NULL;
		$data['require_password_change'] = $config['app']['signup']['defaults']['require_password_change'] ?? 0;
		$data['failed_login_attempts'] = 0;
		$data['last_access_date'] = NULL;
		$data['last_notif_delivery_date'] = NULL;
		$data['last_login_date'] = NULL;
		$data['last_logout_date'] = NULL;
		$data['email'] = trim(\S::fromHTML($_POST['email']));
		$data['receive_notifications'] = $config['app']['signup']['defaults']['receive_notifications'] ?? 0;
		$data['new_email'] = NULL;
		$data['name'] = trim(\S::fromHTML($_POST['first_name']));
		$data['name2'] = trim(\S::fromHTML($_POST['last_name']));
		$data['phone'] = trim(\S::fromHTML($_POST['phone'])) ?: NULL;
		$data['company'] = trim(\S::fromHTML($_POST['company'])) ?: NULL;
		$data['website'] = trim(\S::fromHTML($_POST['website'])) ?: NULL;
		$data['description'] = \S::fromHTML($_POST['description']) ?: NULL;
		$data['logo_url'] = \S::fromHTML($_POST['logo_url']) ?: NULL;
		$data['avatar'] = NULL;

		$perms = [
			'role' => $settings->user_registration_default_role,
			'admin_type' => NULL,
			'admin_users' => 0,
			'admin_roles' => 0,
			'admin_notifications' => 0,
			'admin_logs' => 0,
			'admin_metadata' => 0,
			'admin_over' => NULL,
			'admin_max_users' => 0,
			'admin_homefolder_template' => NULL,
			'space_quota_max' => NULL,
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
		
		$usernameInUse = Users::isUsernameInUse($data['username']);
		$emailInUse = Users::isEmailInUse($data['email']);
		$companyNameInUse = false;
		if ($settings->user_registration_reqfields_company && $config['app']['signup']['unique_company']) {
			$companyNameInUse = $usd->selectOneCol('COUNT(id)', ['LOWER(company)', '=', $usd->q(strtolower($data['company']))]);
		}
		$validCaptcha = false;
		if ($settings->captcha) {
			$reCaptcha = new \ReCaptcha($settings->recaptcha_secret_key);
			if ($_POST['g-recaptcha-response']) {
				$resp = $reCaptcha->verifyResponse(
					\getIP(),
					\S::fromHTML($_POST['g-recaptcha-response'])
				);
				if ($resp != null && $resp->success) {
					$validCaptcha = true;
				}
			}
		}

		$roleInfo = UserRoles::getInfo($settings->user_registration_default_role);

		$pp = new \PassPolicy($data['password']);

		if (!$roleInfo) {
			$feedback = "The system is not configured with a valid role!";
		} else if ($settings->captcha && !$validCaptcha) {
			$feedback = "Please make sure you are not a robot! :)";
		} else if (strlen($data['username']) < 1) {
			$feedback = "Please type a username!";
		} else if (!\S::okUsername($data['username'])) {
			$feedback =  "Please avoid using special characters for the username!";
		} else if ($usernameInUse > 0) {
			$feedback =  "Username already in use. Please choose another one.";
		} else if (($settings->user_registration_reqfields_email || $settings->user_registration_email_verification) && strlen($data['email']) < 4) {
			$feedback = "Please type your email address!";
		} else if ($settings->user_registration_reqfields_email && $emailInUse > 0) {
			$feedback =  "E-mail address already registered. Please use the password recovery options if you forgot your password.";
		} else if (!$settings->user_registration_generate_passwords && strlen($data['password']) < 1) {
			$feedback =  "Please type a password!";
		} else if (!$settings->user_registration_generate_passwords && $data['password'] != $repassword) {
			$feedback = "Please retype the password correctly!";
		} else if (!$pp->validate()) {
			$feedback = $pp->errors[0];
		} else if (strlen($data['name']) < 1) {
			$feedback = "Please type your name!";
		} else if ($settings->user_registration_reqfields_name2 && !strlen($data['name2'])) {
            $feedback = "Please type your last name!";
        } else if ($settings->user_registration_reqfields_phone && !strlen($data['phone'])) {
			$feedback = "Please type the phone number!";
		} else if ($settings->user_registration_reqfields_company && !strlen($data['company'])) {
			$feedback = "Please type the company name!";
		} else if ($settings->user_registration_reqfields_company && $config['app']['signup']['unique_company'] && $companyNameInUse) {
			$feedback = "Please type a different company name!";
		} else if ($settings->user_registration_reqfields_website && !strlen($data['website'])) {
			$feedback = "Please type your website address!";
		} else if ($settings->user_registration_reqfields_description && !strlen($data['description'])) {
			$feedback = "Please type the comment!";
		} else {
			$data['password'] = Auth::hashPassword($data['password']);

			$rs = $usd->insert($data);
			$uid = $usd->lastInsertId();
			if ($rs) {
				$data['id'] = $uid;
					if (is_array($settings->user_registration_default_groups)) {
						foreach ($settings->user_registration_default_groups as $gid) {
							UserGroups::addUserToGroup($uid, $gid);
						}
					}
					$perms['homefolder'] = Perms::applyPathTemplate($roleInfo['homefolder'], $data);
					if ($roleInfo['create_folder']) {
						$contentTemplateFolderPath = $config['path']['data'].'/home_folder_template';
						if (is_dir($contentTemplateFolderPath)) {
							\FM::copyDir($contentTemplateFolderPath, $perms['homefolder']);
						} else {
							\FM::createPath($perms['homefolder']);
						}
					}
				Perms::setPerms($uid, $perms);
				
				$data["IP"] = \getIP();
				Log::add($uid, "new_user_registration", $data);
				
				if ($settings->user_registration_email_verification || $settings->user_registration_reqfields_email) {
					//read template file
					$tpl = Notifications\Templates::parse("signup_email", ['From', 'FromName', 'BCC', 'Subject', 'Body']);
					
					$data['password'] = $password;//if hashed
					//assign data and get output
					$smarty = \FileRun::getSmarty();
					$smarty->assign("info", $data);
					$smarty->assign("hash", md5($uid."-".$data['email']));
					
					$from = $smarty->fetch("string:".$tpl['From']);
					$fromName = $smarty->fetch("string:".$tpl['FromName']);
					$bcc = $smarty->fetch("string:".$tpl['BCC']);
					$subject = $smarty->fetch("string:".$tpl['Subject']);
					$body = $smarty->fetch("string:".$tpl['Body']);
					
					//send email
					$mail = new Utils\Email;
					$mail->setFrom($from, $fromName);
					$mail->Subject = $subject;
					$mail->Body = $body;
					if (strlen($bcc) > 3) {
						$mail->addBCC($bcc);
					}
					$mail->addAddress($data['email']);
					$result = @$mail->send();
					
					if (!$result) {
						$feedback = $mail->ErrorInfo;
					} else {
						$feedback = Lang::t("An e-mail message has been sent at \"%1\" with details on how to complete the registration process.", "User Registration", [\S::fromHTML($data['email'])]);
					}
				} else {
					$feedback = "Your account has been successfully created.";
				}
				if ($_GET['ajax']) {
					jsonOutput([
						"success" => true,
						"message" => Lang::t($feedback)
					]);
				} else {
					if ($config['app']['signup']['redirect_url']) {
						header("Location: " . $config['app']['signup']['redirect_url']);
					}
					exit();
				}
			} else {
				$feedback = "Failed to create user account!";
			}
		}
	}
	$feedback = Lang::t($feedback);
	
	if ($_GET['ajax']) {
		jsonOutput([
			"success" => false,
			"error" => $feedback
		]);
	} else {
		$_SESSION['postedData'] = $_POST;
		if ($config['app']['signup']['redirect_url_failure']) {
			$redirectTo = $config['app']['signup']['redirect_url_failure']."feedback=" . \S::forURL(base64_encode($feedback));
			header("Location: " . $redirectTo);
		}
		exit();
	}
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