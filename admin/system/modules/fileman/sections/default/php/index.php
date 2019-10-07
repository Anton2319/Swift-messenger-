<?php
namespace FileRun;

\FileRun::checkAndForceHTTPS();

$userPerms = Perms::getPerms($auth->currentUserInfo['id']);
$homeFolder = $userPerms['homefolder'];

if ($homeFolder && !is_dir($homeFolder)) {
	if (Perms::check('create_folder', $userPerms)) {
		$rs = \FM::createPath($homeFolder);
		if (!$rs) {
			Lang::d('Failed to create home folder for the user. Please contact the site administrator.');
			exit();
		}
	} else {
		Lang::d('The path of your home folder doesn\'t point to an existing folder. Please contact the site administrator.');
		echo "<br>";
		Lang::d('Click <a href="panel/">here</a> to access the application\'s control panel.');
		exit();
	}
}

$isFreeVersion = \FileRun::isFree();
$isFreeLancerVersion = \FileRun::isFreelancer();
$uidsWithShares = [];


//GET SHARES
$tmp = [];
if (Perms::check('users_may_see', $userPerms)) {
	$shares = Share::getShares();
	foreach ($shares as $val) {
		if ($val['anonymous']) {
			$hasWebLink = false;
			if (Perms::check('weblink', $userPerms)) {
				$hasWebLink = WebLinks::hasLink($val['path']);
			}
			if ($val['alias']) {
				$sharedFolderName = $val['alias'];
			} else {
				$sharedFolderName = \FM::basename($val['path']);
			}
			$notInfo = false;
			if (!$isFreeVersion) {
				$fNotifInfo = Files\Notifications::getByPath($val['path']);
				if ($fNotifInfo) {
					$notInfo = [
						'w' => $fNotifInfo['notify_write'] ? 1 : 0, 
						'r' => $fNotifInfo['notify_read'] ? 1 : 0
					];
				}
			}
			$record = [
				'text' => $sharedFolderName,
				'pathname' => $val['uid'] . ':' . $val['id'],
				'custom' => [
					'weblink' => $hasWebLink ? 1 : 0,
					'notInfo' => $notInfo
				],
				'perms' => [
					'upload' => ($val['perms_upload'] == 1),
					'download' => ($val['perms_download'] == 1),
					'alter' => ($val['perms_alter'] == 1),
					'comment' => ($val['perms_comment'] == 1),
					'read_comments' => ($val['perms_read_comments'] == 1),
					'share' => ($val['perms_share'] == 1)
				]
			];
			if (isset($config['app']['ui']['folder_icon_css_class'][$val['path']])) {
				$record['iconCls'] = $config['app']['ui']['folder_icon_css_class'][$val['path']];
			}
			$metaFileInfo = MetaFiles::getByPath('*', $val['path']);
			if ($metaFileInfo['id']) {
				$star = Stars::getByFileId($metaFileInfo['id'], $auth->currentUserInfo['id']);
				if ($star) {
					$record['custom']['star'] = 1;
				}
			}
			$tmp[$sharedFolderName] = $record;
		} else {
			$uidsWithShares[$val['uid']] = $val['uid'];
		}
	}
	natksort($tmp);
	$tmp = array_values($tmp);
}
$app['anonShares'] = json_encode($tmp);


//INFO ON HOME FOLDER
$notInfo = false;
if ($homeFolder) {
	if (!$isFreeVersion) {
		$fNotifInfo = Files\Notifications::getByPath($homeFolder);
		if ($fNotifInfo) {
			$notInfo = [
				'w' => $fNotifInfo['notify_write'] ? 1 : 0,
				'r' => $fNotifInfo['notify_read'] ? 1 : 0
			];
		}
	}
}

$app['homeFolderCfg'] = json_encode([
	'customAttr' => [
		'notInfo' => $notInfo
	]
]);

//GET LIST OF USERS WITH SHARES
$usersWithShares = [];
if (count($uidsWithShares) > 0) {
	foreach ($uidsWithShares as $uid) {
		$uInfo = Users::getInfo($uid, ['id', 'name', 'name2', 'username']);
		$uInfo['name'] = Users::formatFullName($uInfo);
		$usersWithShares[$uInfo['name']] = $uInfo;
	}
	ksort($usersWithShares);
	$usersWithShares = array_values($usersWithShares);
}
$app['usersWithShares'] = json_encode($usersWithShares);

Users::updateTimestamp('last_access_date');


$app['UISettings'] = json_encode([
	'demo_mode' => $config['misc']['demoMode'],
	'free_mode' => $isFreeVersion,
	'freelancer_mode' => $isFreeLancerVersion,
	'title' => \S::forHTML($settings->app_title),
	'ui_theme' => $settings->ui_theme,
	'ui_user_logo' => (string) $auth->currentUserInfo['logo_url'],
	'ui_logo_url' => $settings->ui_logo_url,
	'ui_logo_link_url' => $settings->ui_logo_link_url,
	'ui_title_logo' => $settings->ui_title_logo?1:0,
	'welcomeMessage' => trim(nl2br(\S::forHTML($settings->ui_welcome_message))),
	'ui_default_view' => $settings->ui_default_view,
	'ui_thumbs_in_detailed' => (bool) $settings->ui_thumbs_in_detailed,
	'ui_enable_rating' => ($settings->ui_enable_rating && Perms::check('metadata', $userPerms)),
	'ui_enable_download_cart' => (bool) $settings->ui_enable_download_cart,
	'ui_double_click' => $settings->ui_double_click,
	'hideLogout' => (bool) $settings->logout_hide,
	'logoutURL' => $settings->logout_url?:false,
	'helpURL' => $settings->ui_help_url?:false,
	'thumbnail_size' => (int) $settings->thumbnails_size,
	'ui_photos_thumbnail_size' => (int) $settings->ui_photos_thumbnail_size,
	'fullTextSearch' => (bool) $settings->search_enable,
	'search_default_mode' => $settings->search_default_mode,
	'disable_versioning' => ($settings->versioning_max == 0),
	'filelog_for_shares' => (bool) $config['app']['filelog']['enable_for_shares'],
	'allow_folder_notifications' => (!$isFreeVersion && $settings->allow_folder_notifications && $auth->currentUserInfo['email']),
	'grid_short_date' => ($config['app']['ui']['grid_short_date'] ?: true),
	'enablePusher' => (bool) $settings->pushercom_enable,
	'pusherAppKey' => $settings->pushercom_app_key,
	'pusherCluster' => $settings->pushercom_cluster,
	'sound_notification' => (bool) $config['app']['disable_sound_notification'],
	'media_folders_photos' => (bool) $settings->ui_media_folders_photos_enable,
	'media_folders_music' => (bool) $settings->ui_media_folders_music_enable,
	'google_static_maps_api_key' => $settings->google_static_maps_api_key,
	'has_home_folder' => ($homeFolder != false),
	'upload_chunk_size' => $config['app']['upload']['chunk_size'] ?: Files\Utils::getUploadChunkSize(),
	'upload_max_simultaneous' => $config['app']['upload']['max_simultaneous'] ?: 3,
	'upload_blocked_types' => trim_array(explode(',', $settings->upload_blocked_types))
]);

$trashCount = $db->GetOne("SELECT COUNT(id) FROM `df_modules_trash` WHERE uid = '" . $auth->currentUserInfo['id'] . "'");

$canChangePass = ($settings->allow_change_pass && Perms::check('change_pass', $userPerms));

$app['UIUser'] = [
	'id' => $auth->currentUserInfo['id'],
	'fname' => \S::safeForHtml(Users::formatFullName($auth->currentUserInfo)),
	'isAdmin' => (Perms::isSuperUser() || Perms::isSimpleAdmin()),
	'isIndep' => Perms::isIndependentAdmin(),
	'lastLogout' => Utils\Date::MySQLDateToUnix($auth->currentUserInfo['last_logout_date']),
	'lastAccessDate' => $auth->currentUserInfo['last_access_date'],
	'trashCount' => (int) $trashCount,
	'requiredToChangePass' => ($auth->currentUserInfo['require_password_change'] && $canChangePass),
	'perms' => [
		'upload' => Perms::check('upload', $userPerms),
		'download' => Perms::check('download', $userPerms),
		'download_folders' => Perms::check('download_folders', $userPerms),
		'read_comments' => Perms::check('read_comments', $userPerms),
		'write_comments' => Perms::check('write_comments', $userPerms),
		'read_only' => Perms::check('readonly', $userPerms),
		'alter' => !Perms::check('readonly', $userPerms),
		'email' => (Perms::check('email', $userPerms) && $auth->currentUserInfo['email']),
		'weblink' => Perms::check('weblink', $userPerms),
		'share' => Perms::check('share', $userPerms),
		'metadata' => Perms::check('metadata', $userPerms),
		'file_history' => (!$settings->disable_file_history && !$isFreeVersion && Perms::check('file_history')),
		'account_settings' => Perms::check('edit_profile', $userPerms) || $canChangePass
	],
	'csrf_token' => $auth->sess->getCSRFToken()
];

//space quota
if ($homeFolder) {
	$maxSpace = Perms::check('space_quota_max', $userPerms);
	if ($maxSpace) {
		$maxSpace = $maxSpace * 1024 * 1024;
		$usage = Files\Quota::getUsage($auth->currentUserInfo['id']);
		$app['UIUser']['perms']['space_quota_used'] = \FM::formatFileSize($usage, 2, "None");
		$app['UIUser']['perms']['space_quota_free'] = \FM::formatFileSize($maxSpace - $usage, 2, "None");
		$app['UIUser']['perms']['space_quota_percent_used'] = percent($usage, $maxSpace, 0);
		$app['UIUser']['perms']['space_quota_max'] = $maxSpace;
	}
}

$app['UIUser'] = json_encode($app['UIUser']);

\FileRun::displaySmartyPage();