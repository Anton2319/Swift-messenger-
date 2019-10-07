<?php
namespace FileRun\Notifications;
use \FileRun\Lang;
use \FileRun\Users;
use \FileRun\UserGroups;
use \FileRun\Perms;
use \FileRun\Share;
use \FileRun\Utils\Email;
use \FileRun\Utils\DP;
use \FileRun\Files;

class Run {

	static $userInfoCache = [];

	static function getUserInfo($uid) {
		if (!self::$userInfoCache[$uid]) {
			$info = Users::getInfo($uid, '*', false);
			//$info['name'] = Users::formatFullName($info);
			$info['groups'] = UserGroups::selectOneUsersGroups($uid);
			$info['perms'] = Perms::getPerms($uid);
			self::$userInfoCache[$uid] = $info;
		}
		return self::$userInfoCache[$uid];
	}

	static function run($cli) {
		global $config, $settings, $db;
		if ($cli) {
			echo 'Analyzing activity since '.$settings->last_email_notification.":\n";
			flush();
		}

		$templates = [];
		$letters = [];
		$countL = 0;

		$lastLogEntryId = $settings->get('last_email_notification');
		if (!$lastLogEntryId) {$lastLogEntryId = 0;}

		$rs = $db->GetAll("SELECT * FROM `".\FileRun\Log::$table."` WHERE id > '".$lastLogEntryId."' ORDER BY id ASC");
		$count = count($rs);
		if ($count == 0) {
			if ($cli) {
				echo "No activity found.\n";
				flush();
			}
			return false;
		}
		if ($cli) {
			echo "Processing ".$count." records:\n";
			flush();
		}
		Lang::setDefault();
		$basicNotifActions = [
			'space_quota_warning',
			'shared_folder_available',
			'shared_file_available',
			'receive_copy',
			'receive_move',
			'receive_upload',
			'comment_received',
			'provide_download',
			'label_received'
		];
		if ($config['app']['email_notification']['basic_actions']) {
			$basicNotifActions = $config['app']['email_notification']['basic_actions'];
		}
		$lastLoggedAction = false;
		$manager = Manager::getTable();
		foreach ($rs as $loggedAction) {
			$loggedAction['details'] = unserialize($loggedAction['data']);

			$action = $loggedAction['action'];
			$lastLoggedAction = $loggedAction['id'];

			if (!$loggedAction['details']['_skip_notifications']) {

				$userInfo = self::getUserInfo($loggedAction['uid']);
				$sentByUserInfo = false;

				if ($userInfo['id']) {//if user was not deleted
					$usersGroupsIds = $userInfo['groups'];
					//find notification rules that might involve this user or its groups in the particular action
					$notifyList = $manager->select("*", [
						["action", "RLIKE", "'(^|\\\\|)" . $loggedAction['action'] . "([a-z]+|\\\\||$)'"],
						[
							[
								["type" => "AND", "object_type", "=", "'user'"],
								["object_id", "=", $userInfo['id']]
							],

							(count($usersGroupsIds) > 0) ?
								[
									["type" => "OR", "object_type", "=", "'group'"],
									["object_id", "IN", "('" . implode("','", $usersGroupsIds) . "')"]
								] :
								["1", "=", "1"]
						]
					]);

					//notify weblink owners
					if ($action == "weblink_download" || $action == "weblink_upload") {
						if ($loggedAction['details']['link_info']['notify']) {
							$notifyList[] = [
								"email_address" => $userInfo['email']
							];
						}
					//notify users with the basic notification checkbox enabled
					} else {
						if ($loggedAction['details']['from_uid']) {
							$sentByUserInfo = self::getUserInfo($loggedAction['details']['from_uid']);
						}
						if (in_array($action, $basicNotifActions)) {
							if ($userInfo['receive_notifications']) {
								//userul trebuie sa-l vada pe cel care a facut actiunea
								$maySee = false;
								if ($userInfo['perms']['users_may_see'] == "-ALL-") {
									$maySee = true;
								} else {
									if (is_array($userInfo['perms']['users_may_see']['users'])) {
										if (in_array($sentByUserInfo['id'], $userInfo['perms']['users_may_see']['users'])) {
											$maySee = true;
										}
									}
									if (!$maySee) {
										if (is_array($userInfo['perms']['users_may_see']['groups'])) {
											if (is_array($sentByUserInfo['groups'])) {
												$rs = array_intersect($sentByUserInfo['groups'], $userInfo['perms']['users_may_see']['groups']);
												if (count($rs) > 0) {
													$maySee = true;
												}
											}
										}
									}
								}
								if ($maySee) {
									$notifyList[] = ["email_address" => $userInfo['email']];
								}
							}
						}
					}
					//notify based on the above rules
					if (is_array($notifyList) && count($notifyList) > 0) {
						$info = [
							'userInfo' => $userInfo,
							'actionUserInfo' => $sentByUserInfo,
							'actionDescription' => Utils::getActionName($action),
							'details' => $loggedAction,
							//'settings' => $settings->data,
							//'config' => $config
						];

						$className = '\FileRun\Notifications\Format\\'.$action;
						if (!class_exists($className)) {
							$className = '\FileRun\Notifications\Format\generic';
						}
							$actionMessage = $className::fetch($info);
						/*} else {
							if (!$templates[$action]) {
								$templates[$action] = Templates::parse($action, ["Action"]);
							}
							$smarty = \FileRun::getSmarty();
							$smarty->assign("info", $info);
							$actionMessage = $smarty->fetch("string:" . $templates[$action]['Action']);
						}*/

						if ($cli) {
							echo "\t" . $loggedAction['date'] . " - " . $userInfo['username'] . " - " . $action . "\n";
						}
						foreach ($notifyList as $notify) {
							if ($cli) {
								echo "\t\t Notify: " . $notify['email_address'] . "\n";
								flush();
							}
							$letters[$notify['email_address']][] = [
								"message" => $actionMessage,
								"info" => $info
							];
						}
					}

					if ($settings->allow_folder_notifications) {
						$actionPaths = [
							'download' => ['full_path' => 'read'],
							'preview' => ['full_path' => 'read'],
							'upload' => ['full_path' => 'write'],
							'weblink_download' => ['full_path' => 'read'],
							'weblink_upload' => ['full_path' => 'write'],
							'file_copied' => [
								'from_full_path' => ['actionType' => 'read', 'tplName' => 'file_copied.copy'],
								'to_full_path' => ['actionType' => 'write', 'tplName' => 'file_copied.paste'],
							],
							'file_moved' => [
								'from_full_path' => ['actionType' => 'write', 'tplName' => 'file_moved.cut'],
								'to_full_path' => ['actionType' => 'write', 'tplName' => 'file_moved.paste']
							],
							'folder_moved' => [
								'from_full_path' => ['actionType' => 'write', 'tplName' => 'folder_moved.cut'],
								'to_full_path' => ['actionType' => 'write', 'tplName' => 'folder_moved.paste'],
							],
							'folder_copied' => [
								'from_full_path' => ['actionType' => 'read', 'tplName' => 'folder_copied.copy'],
								'to_full_path' => ['actionType' => 'write', 'tplName' => 'folder_copied.paste'],
							],
							'zip_files' => ['full_path' => 'write'],
							'comment_added' => ['full_path' => 'write'],
							'label_set' => ['full_path' => 'write'],
							'metadata_changed' => ['full_path' => 'write'],
							'file_deleted' => ['full_path' => 'write'],
							'deleted_file_restored' => ['to_full_path' => 'write'],
							'file_renamed' => ['full_path' => 'write'],
							'files_send_by_email' => ['full_path' => 'read'],
							'file_locked' => ['full_path' => 'write'],
							'file_unlocked' => ['full_path' => 'write'],
							'version_restored' => ['full_path' => 'write'],
							'version_deleted' => ['full_path' => 'write'],
							'new_folder' => ['full_path' => 'write'],
							'folder_deleted' => ['full_path' => 'write'],
							'deleted_folder_restored' => ['to_full_path' => 'write'],
							'folder_renamed' => ['full_path' => 'write']
						];

						$notifyList = [];
						if ($actionPaths[$action]) {
							foreach ($actionPaths[$action] as $pathField => $actionType) {
								$loggedPath = $loggedAction['details'][$pathField];
								$templateName = $action;

								if ($loggedPath) {//action found in above list and path is set
									if ($action == "files_send_by_email") {//the path is the containing path
										$parentFolderPath = $loggedPath;
									} else {
										$parentFolderPath = \FM::dirname($loggedPath);
									}

									//check the full path against the folder notification table
									$folderNotificationRules = Files\Notifications::getAllByPath($parentFolderPath);

									if (is_array($actionType)) {
										if ($actionType['tplName']) {
											$templateName = $actionType['tplName'];
										}
										$actionType = $actionType['actionType'];
									}

									if (is_array($folderNotificationRules)) {
										foreach ($folderNotificationRules as $rule) {
											if (($actionType == "read" && $rule['notify_read']) || ($actionType == "write" && $rule['notify_write'])) {

												//if is weblink_download userInfo is no longer the weblink owner, but random visitor
												if (($rule['uid'] != $userInfo['id']) || $action == "weblink_download") {
													$rUInfo = self::getUserInfo($rule['uid']);
													if ($rUInfo['email']) {
														//userul trebuie sa-l vada pe cel care a facut actiunea
														$maySee = false;
														if ($rUInfo['perms']['users_may_see'] == "-ALL-") {
															$maySee = true;
														} else {
															if (is_array($rUInfo['perms']['users_may_see']['users'])) {
																if (in_array($userInfo['id'], $rUInfo['perms']['users_may_see']['users'])) {
																	$maySee = true;
																}
															}
															if (!$maySee) {
																if (is_array($rUInfo['perms']['users_may_see']['groups'])) {
																	if (is_array($userInfo['groups'])) {
																		$rs = array_intersect($userInfo['groups'], $rUInfo['perms']['users_may_see']['groups']);
																		if (count($rs) > 0) {
																			$maySee = true;
																		}
																	}
																}
															}
														}
														if ($maySee) {
															$relativePath = substr($loggedPath, strlen(\FM::dirname($rule['path'])));
															//if path is from shared folder, replace folder name with alias
															if ($rule['shareid']) {
																$shareInfo = Share::getById($rule['shareid']);
																if ($shareInfo['alias']) {
																	$relativePath = gluePath($shareInfo['alias'], \FM::stripParents($relativePath, 1));
																}
															}


															$notifyList[] = [
																"template_name" => $templateName,
																"notifiedUserInfo" => $rUInfo,
																"full_path" => $loggedPath,
																"relative_path" => $relativePath
															];
														}
													}
												}
											}
										}
									}
								}
							}
						}
						//notify based on folder notification rules above
						if (count($notifyList) > 0) {
							if ($cli) {
								echo "\t" . $loggedAction['date'] . " - " . $userInfo['username'] . " - " . $action . "\n";
							}
							$info = [
								'details' => $loggedAction,
								'actionDescription' => Utils::getActionName($action),
								'settings' => $settings->data,
								'config' => $config
							];
							$smarty = \FileRun::getSmarty();
							foreach ($notifyList as $notify) {
								if ($cli) {
									echo "\t\t Notify: " . $notify['notifiedUserInfo']['email'] . "\n";
									flush();
								}
								$info['userInfo'] = $notify['notifiedUserInfo'];
								$info["actionUserInfo"] = $userInfo;
								$info["fullPath"] = $notify['full_path'];
								$info["relativePath"] = $notify['relative_path'];

								$smarty->assign("info", $info);

								if ($notify['template_name']) {
									$tplName = $notify['template_name'];
								} else {
									$tplName = $action;
								}
								if (!$templates[$tplName . "_fn"]) {
									$templates[$tplName . "_fn"] = Templates::parse($tplName, ["Action"], "folder_notifications");
								}
								$actionMessage = $smarty->fetch("string:" . $templates[$tplName . "_fn"]['Action']);
								$letters[$notify['notifiedUserInfo']['email']][] = [
									"message" => $actionMessage,
									"info" => $info
								];
							}
						}
					}//end if ($settings->allow_folder_notifications)
				}//end if user not deleted

			}//end if _skip_notification

			$countL = count($letters);

			$limit = $config['app']['email']['cron_message_limit'] ?: 20;
			if ($countL > $limit) {
				if ($cli) {
					echo "\tStop processing records, as ".$countL." messages are ready to be sent.\n";
					flush();
				}
				break;
			}
		}

		if ($cli) {
			if ($countL > 0) {
				echo "Sending ".$countL." emails:\n";
			} else {
				echo "No emails to send.\n";
			}
			flush();
		}

		if ($countL > 0) {
			$smarty = \FileRun::getSmarty();

			$templatesFolder = $config['path']['root']."/customizables/emails";
			$templateFile = gluePath($templatesFolder, Lang::getCurrent(), "notifications.tpl.txt");

			if (is_file($templateFile)) {
				$rs = Templates::parseFields(['Subject', 'Body'], file_get_contents($templateFile));
				$settings->notifications_template = $rs['Body'];
				$settings->notifications_subject_template = $rs['Subject'];
			}

			$isFree = \FileRun::isFree();
			foreach ($letters as $email => $actions) {
				if ($cli) {
					echo "\t".$email." (".count($actions)." actions): ";
				}
				$actions = array_reverse($actions);
				$info = [
					'userInfo' => $actions[0]['info']['userInfo'],
					'actionUserInfo' => $actions[0]['info']['actionUserInfo'],
					'actions' => $actions,
					'settings' => $settings->data,
					'config' => $config,
					'cli' => $cli
				];
				$smarty->assign("info", $info);
				$body = $smarty->fetch("string:".$settings->notifications_template);
				if ($isFree) {
					$body .= '<div style="margin:10px 0;font-size:11px;color:gray;">Notifications powered by FileRun</div>';
				}
				$from = $settings->default_notification_address;
				$fromName = $settings->default_notification_name;
				$subject = $smarty->fetch("string:".$settings->notifications_subject_template);
				$bcc = false;
				if ($settings->notifications_bcc) {
					$bcc = $smarty->fetch("string:".$settings->notifications_bcc);
				}

				$mail = new Email;
				$mail->setFrom($from, $fromName);
				$mail->Subject = $subject;
				$mail->Body = $body;
				if (strlen($bcc) > 3) {
					$mail->addBCC($bcc);
				}

				if ($email == "ALL") {
					$recipients = Users::getTable()->selectColumn(['email'], [
						['receive_notifications', '=', '1'],
						['CHAR_LENGTH(email)', '>', '3']
					]);
				} else {
					$recipients = [$email];
				}
				foreach ($recipients as $recipient) {
					$mail->addAddress($recipient);
				}
				$result = @$mail->send();
				if ($cli) {
					if ($result) {
						echo "Sent\n";
					} else {
						echo "Failed\n";
						echo "\t\t".$mail->ErrorInfo;
					}
				}
				self::addToLog([
					'from' => $from,
					'to' => $recipients,
					'bcc' => $bcc,
					'subject' => $subject,
					'errors' => $mail->ErrorInfo
				], $body, (strlen($mail->ErrorInfo) > 0));
				if ($config['system']['log_notification_emails']) {
					self::logNotificationAttempt(print_r($mail, 1));
				}
			}

		}
		if ($lastLoggedAction) {
			$settings->set("last_email_notification", $lastLoggedAction);
		}
		if ($countL == 0) {
			self::run($cli);
		}
		Lang::setAuto();
	}

	static function logNotificationAttempt($data) {
		global $config;
		$logFile = gluePath($config['path']['temp'], "notifications.log");
		$delim = "\r\n".str_repeat("-", 10).date("r").str_repeat("-", 10)."\r\n";
		$data = $delim.$data.$delim;
		if (!file_exists($logFile)) {
			return \FM::newFile($logFile, $data);
		}
		return \FM::appendData($logFile, $data);
	}

	static function addToLog($data, $message, $errors) {
		global $config;
		$d = DP::factory('df_notifications_logs');
		$d->insert([
			'date' => 'NOW()',
			'has_errors' => ($errors ? 1 : 0),
			'data' => serialize($data),
			'message' => $message
		]);
		if ($config['system']['email_notification']['logging']['expiration']) {
			$days = $config['system']['email_notification']['logging']['expiration'];
		} else {
			$days = '2';
		}
		$d->delete(['date', '<', 'DATE_SUB(NOW(), INTERVAL '.$days.' DAY)']);
	}
}