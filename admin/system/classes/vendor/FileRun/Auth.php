<?php
namespace FileRun;

class Auth extends Utils\DP {

	var $debug = false;
	var $usersTable = 'df_users';
	var $usersPermsTable = 'df_users_permissions';
	var $table = 'df_users';
	var $currentUserInfo = false;
	var $TwoStep = false;
	var $error = false;
	var $errorCode = false;
	var $sess;
	var $customAuth;
	var $ephemeral = false;

	function __construct($useDbSessions, $ephemeral = false) {
		if ($useDbSessions) {
			$this->sess = new AuthSessions;
		}
		$this->ephemeral = $ephemeral;
	}

	function hasValidCookie($checkCSRF = false) {

		if ($checkCSRF) {//if using PHP sessions
			global $settings;
			if ($settings->logout_inactivity > 0) {
				if (!$_POST['csrf']) {return false;}
				if (!$_SESSION['FileRun']['csrf_token']) {return false;}
				if (\S::fromHTML($_POST['csrf']) === \S::fromHTML($_SESSION['FileRun']['csrf_token'])) {
					return true;
				}
			}
		}

		$token = $this->sess->getRemoteToken();
		if (!$token) {return false;}
		$rs = $this->sess->checkToken($token, $checkCSRF);
		if ($this->debug && $rs) {
			echo ' [has valid cookie] ';
		}
		return $rs;
	}

	function hasValidPHPSession() {
		$this->initPHPSession();
		return $_SESSION['FileRun']['username'] !== '';
	}

	function initPHPSession() {
		if (!session_id()) {
			if ($this->debug) {echo ' [filerun session started] ';}
			session_name('FileRunSID');
			@session_start();
		}
	}

	function markUserAsLoggedIn($username) {
		if ($this->debug) {echo ' [marking user as logged in] ';}
		$this->renewSessionId();
		$_SESSION['FileRun']['session_start'] = time();
		$_SESSION['FileRun']['username'] = strtolower($username);
	}

	function renewSessionId() {
		$this->initPHPSession();
		$_SESSION['test'] = time();
		session_regenerate_id(true);
	}

	function ssoAvailable() {
		global $settings;
		if (!$settings->auth_plugin) {return false;}
		if (!$this->customAuth) {$this->initCustomClass();}
		if (!$this->customAuth) {return false;}
		if (method_exists($this->customAuth, 'ssoEnabled')) {
			return $this->customAuth->ssoEnabled();
		}
		return method_exists($this->customAuth, 'singleSignOn');
	}

	function ssoOnly() {
		global $settings;
		if (!$settings->auth_plugin) {return false;}
		if (!$this->customAuth) {$this->initCustomClass();}
		if (!$this->customAuth) {return false;}
		return !\oneOrZero($settings->auth_allow_local);
	}
	
	function singleSignOn() {
		global $settings;
		if ($settings->auth_plugin) {
			$rs = $this->initCustomClass();
			if ($rs) {
				if (method_exists($this->customAuth, 'singleSignOn')) {
					if (method_exists($this->customAuth, 'ssoEnabled')) {
						if (!$this->customAuth->ssoEnabled()) {
							$this->errorCode = 'NO-SSO';
							$this->error = 'Single-sign-on is not enabled';
							return false;
						}
					}

					$username = $this->customAuth->singleSignOn();
					if (!$username) {
						$this->errorCode = 'PLUGIN_NO_SSO_SESSION';
						if ($this->customAuth->error) {
							$this->error = $this->customAuth->error;
						} else {
							$this->error = 'You are not logged in';
						}
						if ($this->customAuth->errorCode) {
							$this->errorCode = $this->customAuth->errorCode;
						}
						return false;
					}
					$username = strtolower($username);
					$rs = $this->validateAccount($username);
					if (!$rs && $this->errorCode === 'USERNAME_NOT_FOUND') {
						$userInfo = $this->customAuth->getUserInfo($username);
						if (!$userInfo) {
							return false;
						}
						$userData = $userInfo['userData'];
						$userPerms = $userInfo['userPerms'];
						$userGroups = $userInfo['userGroups'];
						$userData['username'] = $username;
						$rs = $this->insertLocalUser($userData, $userPerms, $userGroups);
						if (!$rs) {return false;}
						$userData['id'] = $rs;
						$this->currentUserInfo = $userData;
					}
					//some third-party apps take over the session handling
					if (session_id() && session_name() !== 'FileRunSID') {
						session_write_close();
						ini_set('session.save_handler', 'files');
						if ($_COOKIE['FileRunSID']) {
							session_id($_COOKIE['FileRunSID']);
						}
						session_name('FileRunSID');
						@session_start();
					}
					$persistent = ($settings->logout_inactivity == 0);
					if (!$persistent) {
						$this->markUserAsLoggedIn($username);
					} else {
						$this->sess->setToken($username);
					}
					return true;
				}
			}
		}
		return false;
	}

	function initCustomClass() {
		global $settings, $config;
		if ($this->customAuth) {return true;}
		$className = 'customAuth_'.$settings->auth_plugin;
		$customAuthFile = gluePath($config['path']['root'], '/customizables/auth/'.$settings->auth_plugin.'.auth.php');
		if (!is_file($customAuthFile)) {return false;}
		require_once $customAuthFile;
		if (!class_exists($className)) {echo 'Class '.$className.' not found';exit();}
		$this->customAuth = new $className;
		return true;
	}


	function validateAccount($username = false) {
		if (!$username) {
			$username = $_SESSION['FileRun']['username'];
		}
		//todo: this function does not check the plugins if the 3rd party user account still exists or is valid
		if (!$this->currentUserInfo) {
			$this->currentUserInfo = $this->selectOne('*', ['username', '=', $this->q(strtolower($username))]);
		}
		if (!$this->currentUserInfo) {
			if ($this->debug) {echo ' [username '.$username.' not found in local database] ';}
			$this->error = 'Invalid username.';
			$this->errorCode = 'USERNAME_NOT_FOUND';
			return false;
		}
		if (!$this->currentUserInfo['activated']) {
			if ($this->debug) {echo ' [local account deactivated] ';}
			$this->error = 'Your account has been deactivated!';
			$this->errorCode = 'DEACTIVATED';
			return false;
		}
		if ($this->debug) {echo ' [accounts has been validated] ';}
		return true;
	}

	function checkIP($username) {
		global $config;
		$IPLimit = $config['app']['login']['restrict_ip'][strtolower($username)];
		if ($IPLimit) {
			$ipAddr = getIP();
			$pass = false;
			if ($ipAddr === $IPLimit) {
				$pass = true;
			} else if (Utils\Network::ip_in_range($ipAddr, $IPLimit)) {
				$pass = true;
			}
			if (!$pass) {
				$this->error = 'Your account access is limited to a particular IP address!';
				$this->errorCode = 'IP_MISMATCH';
				return false;
			}
		}
		return true;
	}

	function authenticate_custom($username, $password) {
		global $settings;
		$rs = $this->initCustomClass();
		if (!$rs) {
			$this->error = 'Failed to initialize custom authentication plugin class';
			$this->errorCode = 'PLUGIN_CONFIG';
			return false;
		}
		if (!method_exists($this->customAuth, 'authenticate')) {
			//the allows the FileRun superuser (username=admin) to login using the local credentials
			$this->errorCode = 'PLUGIN_CONFIG';
			$this->error = 'Please use the SSO (Single Sign On) button';
			return false;
		}
		$userInfo = $this->customAuth->authenticate($username, $password);
		if (is_array($userInfo)) {//user has proper credentials
			if ($this->currentUserInfo['id']) {//user already in local database
				if ($settings->auth_sync_passwords) {
					$hashedPass = self::hashPassword($password);
					if ($hashedPass !== $this->currentUserInfo['password']) {
						//update stored password
						$this->updateById(['password' => $hashedPass, 'last_pass_change' => 'NOW()'], $this->currentUserInfo['id']);
					}
				}
				return true;
			}
			$userData = $userInfo['userData'];
			$userPerms = $userInfo['userPerms'];
			$userGroups = $userInfo['userGroups'];
			$userData['username'] = $username;
			return $this->insertLocalUser($userData, $userPerms, $userGroups);
		}
		//custom auth failed
		if ($this->customAuth->error) {
			$this->error = $this->customAuth->error;
			$this->errorCode = $this->customAuth->errorCode;
		} else {
			$this->error = "Authentication failed.";
			$this->errorCode = 'CUSTOM_FAIL';
		}
		return false;
	}

	function insertLocalUser($userData, $userPerms, $userGroups) {
		global $lx, $settings;
		if ($lx->c()) {
			$userData['username'] = strtolower($userData['username']);
			if ($settings->auth_sync_passwords && $userData['password']) {
				$userData['password'] = self::hashPassword($userData['password']);
			} else {
				$userData['password'] = NULL;
			}
			$userData['activated'] = 1;
			$userData['last_login_date'] = 'NOW()';
			$userData['registration_date'] = 'NOW()';
			if (!array_key_exists('role', $userPerms)) {
				$userPerms['role'] = $settings->user_registration_default_role;
			}
			if (!$userPerms['role'] || $userPerms['role'] == '-') {
				$this->error = 'A role needs to be assigned to new users!';
				$this->errorCode = '3RDA_NO_ROLE';
				return false;
			}
			$rs = $this->insert($userData);
			$user_id = $this->lastInsertId();
			$userPerms['uid'] = $user_id;
			if ($rs) {
				//automatically create groups and assign user to them
				if (count($userGroups) > 0) {
					$groups = UserGroups::getTable();
					foreach ($userGroups as $groupName) {
						$gid = $groups->selectOneCol('id', ['name', '=', $groups->q($groupName)]);
						if (!$gid) {
							$groupData['name'] = $groupName;
							$groupData['description'] = '';
							$rs = $groups->insert($groupData);
							if ($rs) {
								$gid = $groups->lastInsertId();
							}
						}
						if ($gid) {
							UserGroups::addUserToGroup($user_id, $gid);
						}
					}
				}
				if (is_array($settings->user_registration_default_groups)) {
					foreach ($settings->user_registration_default_groups as $gid) {
						UserGroups::addUserToGroup($user_id, $gid);
					}
				}
				$roleInfo = UserRoles::getInfo($userPerms['role']);
				if (!$userPerms['homefolder']) {
					$userPerms['homefolder'] = Perms::applyPathTemplate($roleInfo['homefolder'], $userData);
					$userPerms['homefolder'] = \FM::normalizePath($userPerms['homefolder']);
				}
				$this->table = $this->usersPermsTable;
				$this->insert($userPerms);
				$this->table = $this->usersTable;
				return $user_id;
			}
			$this->error = 'Failed to insert user to local database!';
			$this->errorCode = 'SQL_FAIL';
			return false;
		}
		$this->error = 'The software\'s license limits the creation of new user accounts!';
		$this->errorCode = 'LICENSE_LIMIT';
		return false;
	}

	function authenticate_local($username, $password) {
		if (!$this->currentUserInfo) {
			$this->error = 'Invalid username.';
			$this->errorCode = 'USERNAME_NOT_FOUND';
			return false;
		}
		if (empty($this->currentUserInfo['password'])) {
			$this->error = 'Please use the SSO (Single Sign On) button.';
			$this->errorCode = 'USE_SSO';
			return false;
		}
		$passwordIsCorrect = $this->verifyPassword($password, $this->currentUserInfo['password']);
		if (!$passwordIsCorrect) {
			$this->error = 'Invalid password.';
			$this->errorCode = 'WRONG_PASS';
			return false;
		}
		return true;
	}

	function validateOTP($otp, $two_step_secret) {
		if ($this->currentUserInfo['two_step_enabled']) {
			if ($this->currentUserInfo['two_step_secret']) {
				if (!$otp) {
					$this->error = 'Your account is configured for 2-step verification. You need to type in a verification code!';
					$this->errorCode = '2FA_ASK_OTP';
					return false;
				}
				$g = new \GoogleAuthenticator();
				if (($this->currentUserInfo['last_otp'] && md5($otp) == $this->currentUserInfo['last_otp']) || !$g->checkCode($this->currentUserInfo['two_step_secret'], $otp)) {
					$this->error = 'The provided verification code is not valid!';
					$this->errorCode = '2FA_WRONG_OTP';
					$this->TwoStep['currentCode'] = $g->getCode($this->currentUserInfo['two_step_secret']);//for logging
					return false;
				}
			} else {
				if ($two_step_secret) {
					$g = new \GoogleAuthenticator();
					if ($g->checkCode($two_step_secret, $otp)) {
						$this->TwoStep['secret'] = $two_step_secret;
					} else {
						$this->error = 'The provided verification code is not valid!';
						$this->errorCode = '2FA_WRONG_OTP';
						$this->TwoStep['currentCode'] = $two_step_secret;//for logging
						return false;
					}
				} else {
					$this->error = 'Your account is configured for 2-step verification. Click Ok to start the setup.';
					$this->errorCode = '2FA_INIT';
					$g = new \GoogleAuthenticator();
					$this->TwoStep['secret'] = $g->generateSecret();
					$this->TwoStep['keyURI'] = $g->getKeyUri($this->currentUserInfo['username'], $_SERVER['HTTP_HOST'], $this->TwoStep['secret']);
					return false;
				}
			}
		}
		return true;
	}

	function authenticate($username, $password = false, $persistent = false, $otp = false, $two_step_secret = false) {
		global $settings;
		$success = false;
		$username = strtolower($username);
		if (!$username) {
			$this->error = 'Please type an username.';
			$this->errorCode = 'TYPE_USERNAME';
			return false;
		}
		if (!$password) {
			$this->error = 'Please type a password.';
			$this->errorCode = 'TYPE_PASS';
			return false;
		}
		$rs = $this->checkIP($username);
		if (!$rs) {return false;}

		$this->currentUserInfo = $this->selectOne('*', ['username', '=', $this->q($username)]);

		if ($this->currentUserInfo && !$this->currentUserInfo['activated']) {
			$this->error = 'Your account has been deactivated!';
			$this->errorCode = 'DEACTIVATED';
			return false;
		}

		$afterTheLastSlash = trim(strrchr($password, '/'), '/');
		if (strlen($afterTheLastSlash) === 6) {
			$otp = $afterTheLastSlash;
			$password = substr($password, 0, strlen($password)-7);
		}

		$performLocal = false;
		if ($settings->auth_plugin) {
			$success = $this->authenticate_custom($username, $password);
			if ($success) {
				if ($settings->auth_plugin_ip_mask) {
					if (!Utils\Network::ip_in_range(getIP(), $settings->auth_plugin_ip_mask)) {
						$success = false;
						$this->error = 'Your account access is limited to a particular IP address!';
						$this->errorCode = 'IP_MISMATCH';
					}
				}
			} else {
				if ($this->errorCode === 'USERNAME_NOT_FOUND' && $settings->auth_allow_local) {
					$performLocal = true;
				} else if ($this->errorCode === 'PLUGIN_CONFIG' && (strtolower($username) === 'superuser' || strtolower($username) === 'admin')) {
					$performLocal = true;
				}
			}
		} else {
			$performLocal = true;
		}

		if ($performLocal) {
			$success = $this->authenticate_local($username, $password);
		}
		if ($success) {
			$rs = $this->validateAccount($username);
			if (!$rs) {
				return false;
			}
			$rs = $this->validateOTP($otp, $two_step_secret);
			if (!$rs) {
				return false;
			}

			//ALL IS GOOD
			if (!$persistent) {
				if (!$this->ephemeral) {
					$this->markUserAsLoggedIn($username);
				}
			} else {
				$this->renewSessionId();
				$this->sess->setToken($username);
			}
			$updateData = [
				'failed_login_attempts' => '0',
				'last_login_date' => 'NOW()'
			];
			if (isEmptyMySQLDate($this->currentUserInfo['last_notif_delivery_date'])) {
				$updateData['last_notif_delivery_date'] = 'NOW()';
			}
			if (!$this->currentUserInfo['two_step_secret'] && $this->TwoStep['secret']) {
				$updateData['two_step_secret'] = $this->TwoStep['secret'];
			}
			if ($otp) {
				$updateData['last_otp'] = md5($otp);
			}
			$this->updateById($updateData, $this->currentUserInfo['id']);
			return $this->currentUserInfo['id'];
		}
		if ($this->errorCode === 'WRONG_PASS') {
			if ($settings->max_login_attempts > 0) {
				$maxLoginAttempts = $settings->max_login_attempts;
				if ($this->currentUserInfo['id'] == 1) {$maxLoginAttempts = 20;}
				if ($this->currentUserInfo['failed_login_attempts'] >= $maxLoginAttempts) {
					$this->errorCode = 'FAIL_LIMIT';
					$this->updateById([
						'activated' => 0,
						'failed_login_attempts' => 0
					], $this->currentUserInfo['id']);
				} else {
					$this->updateById([
						'failed_login_attempts' => $this->currentUserInfo['failed_login_attempts']+1
					], $this->currentUserInfo['id']);
				}
			}
		}
		return false;
	}

	static function hashPassword($password) {
		return \password_hash($password , PASSWORD_DEFAULT, ['cost' => 10]);
	}

	function verifyPassword($password, $hash) {
		if (strpos($hash, '$2y$') !== 0) {//old hash format
			if (md5($password) !== $hash) {return false;}
			$newHash = self::hashPassword($password);
			if ($newHash) {
				$this->update(['password' => $newHash], ['password', '=', $this->q(md5($password))]);
			}
			return true;
		}
		return \password_verify($password, $hash);
	}

	function logout() {
		global $settings;
		if (!$this->ephemeral) {
			$token = $this->sess->getRemoteToken();
			if ($token) {
				$rs = $this->sess->clearToken($token);
				if ($rs) {
					$this->sess->removeRemoteToken();
				}
			}
			$_SESSION['FileRun'] = [];
			$this->renewSessionId();
		}
		$this->updateById(['last_logout_date' => 'NOW()'], $this->currentUserInfo['id']);
		$this->currentUserInfo = false;
		if ($settings->auth_plugin) {
			$this->initCustomClass();
			if (method_exists($this->customAuth, 'logout')) {
				$this->customAuth->logout();
			}
		}
		return true;
	}
}