<?php
namespace FileRun;
use \FileRun\Files\Actions\Browse\Browse;

$relativePath = \S::fromHTML($_REQUEST['path']);

$data = Browse::getList($relativePath);
if (!$data) {
	jsonFeedback(false, Lang::t(Browse::getError()['public']));
}

$list = [];

if ($data['path']['type'] == 'root' || $data['path']['type'] == 'users_with_shares') {
	$list = $data['items'];
} else {

	$perms = Perms::getPerms($auth->currentUserInfo['id']);
	$lastLoginTimeStamp = Utils\Date::MySQLDateToUnix($auth->currentUserInfo['last_access_date']);

	$userCanShare = ($perms['share'] && $perms['users_may_see'] != '');
	$folderNotifications = !\FileRun::isFree() && Perms::getsNotifications();

	foreach ($data['items'] as $item) {
		if ($data['path']['type'] == 'shared' && !$data['path']['share_id']) {
			$folderName = $item['fileName'];
			$folderPath = $item['shareInfo']['path'];
		} else {
			$folderName = $item;
			$folderPath = gluePath($data['fullPath'], $folderName);
		}
		$rs = [
			'text' => (string) \S::safeHTMLFileName($folderName)
		];
		if (isset($config['app']['ui']['folder_icon_css_class'][$folderPath])) {
			$rs['iconCls'] = $config['app']['ui']['folder_icon_css_class'][$folderPath];
		}
		if ($data['path']['type'] == 'shared' && !$data['path']['share_id']) {
			$rs['pathname'] = $item['shareInfo']['id'];
		}

		$modified = @filemtime($folderPath);
		if ($lastLoginTimeStamp && $modified > $lastLoginTimeStamp) {
			$rs['new'] = 1;
		}
		$pathId = Paths::getId($folderPath);
		if ($pathId) {
			$metaFileInfo = MetaFiles::getByPid('*', $pathId);
			if ($metaFileInfo) {
				$star = Stars::getByFileId($metaFileInfo['id'], $auth->currentUserInfo['id']);
				if ($star) {$rs['custom']['star'] = 1;}


				if ($perms['read_comments'] && (!$data['shareInfo'] || $data['shareInfo']['perms_read_comments'])) {
					$label = Labels::getByFileId($metaFileInfo['id']);
					if ($label) {
						$rs['custom']['label'] = $label;
					}
				}
			}
			if ($perms['weblink'] && (!$data['shareInfo'] || $data['shareInfo']['perms_share'])) {
				if (WebLinks::hasLinkByPid($pathId)) {$rs['custom']['weblink'] = 1;}
			}
			if ($folderNotifications) {
				$nInfo = Files\Notifications::getByPathId($pathId);
				if ($nInfo) {
					$rs['custom']['notInfo'] = [
						'w' => $nInfo['notify_write'] ? 1 : 0,
						'r' => $nInfo['notify_read'] ? 1 : 0
					];
				}
			}
		}

		$shareInfo = $item['shareInfo'] ?? $data['shareInfo'];

		if ($shareInfo) {
			$rs['section'] = 'sharedFolder';
			$rs['perms'] = [
				'upload' => ($shareInfo['perms_upload'] == 1),
				'download' => ($shareInfo['perms_download'] == 1),
				'alter' => ($shareInfo['perms_alter'] == 1),
				'comment' => ($shareInfo['perms_comment'] == 1),
				'read_comments' => ($shareInfo['perms_read_comments'] == 1),
				'share' => ($shareInfo['perms_share'] == 1)
			];
			$rs['readonly'] = true;
		} else {
			if ($userCanShare) {
				if (Share::isShared($folderPath)) {
					$rs['custom']['share'] = 1;
				}
			}
		}
		$list[] = $rs;
	}
}

noCacheHeaders();
jsonOutput([
	'folderName' => $data['folderName'],
	'items' => $list
]);