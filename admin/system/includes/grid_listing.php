<?php
namespace FileRun;
use \FileRun\Files\Utils;
use \FileRun\Utils\Date;

$returnedList = [];
$count = 0;

if (is_array($list)) {
	$listCount = count($list);

	$perms = Perms::getPerms($auth->currentUserInfo['id']);

	$displayMetaDataFileType = false;
	$displayTags = false;
	$displayRating = false;
	$metadataFields = [];

	$FileRunIsFree = \FileRun::isFree();

	if ($perms['metadata']) {
		$tmp = $_POST['metadata'];
		if (is_array($tmp)) {
			foreach ($tmp as $val) {
				$val = \S::fromHTML($val);
				if ($val == "filetype") {
					$displayMetaDataFileType = true;
				} else if ($val == "tags") {
					$displayTags = true;
				} else if ($val == "rating") {
					$displayRating = true;
				} else {
					if ($val > 0) {
						$metadataFields[] = $val;
					}
				}
			}
		}
		if ($mode == 'music') {
			$artistFieldInfo = Media\Music::getFieldInfo('Artist');
			if (!in_array($artistFieldInfo['id'], $metadataFields)) {
				$metadataFields[] = $artistFieldInfo['id'];
			}
			$albumFieldInfo = Media\Music::getFieldInfo('Album');
			if (!in_array($albumFieldInfo['id'], $metadataFields)) {
				$metadataFields[] = $albumFieldInfo['id'];
			}
			$titleFieldInfo = Media\Music::getFieldInfo('Title');
			if (!in_array($titleFieldInfo['id'], $metadataFields)) {
				$metadataFields[] = $titleFieldInfo['id'];
			}
			$durationFieldInfo = Media\Music::getFieldInfo('Duration');
			if (!in_array($durationFieldInfo['id'], $metadataFields)) {
				$metadataFields[] = $durationFieldInfo['id'];
			}
		}
	}

	$folderNotifications = false;
	$userCanShare = ($perms['share'] && $perms['users_may_see'] != '');

	if (!$FileRunIsFree) {
		$folderNotifications = Perms::getsNotifications();
	}

	$homeFolderPath = $perms['homefolder'];
	$homeFolderPathLength = strlen($homeFolderPath);

	$lastLoginTimeStamp = Date::MySQLDateToUnix($auth->currentUserInfo['last_access_date']);

	foreach ($list as $item) {
		$record = [];

		$metaFileInfo = false;
		$shareInfo = false;
		if (in_array($mode, ['recent', 'photos', 'music', 'starred', 'weblinked', 'search', 'collection'])) {
			$filePath = $item['path'];
			$fileName = $item['filename'];

			if (in_array($mode, ['recent', 'starred', 'weblinked', 'collection'])) {
				if ($item['share_id']) {
					$shareInfo = Share::getById($item['share_id']);
					if (!$shareInfo || (!\FM::inPath($filePath, $shareInfo['path']) && $filePath != $shareInfo['path'])) {
						//share no longer available or file is no longer inside the shared folder
						if ($mode == 'starred') {
							Stars::remove($filePath, $auth->currentUserInfo['id']);
						}
						continue;
					}
					$subPath = \FM::stripRoot($filePath, $shareInfo['path']);
					$shareInfo['id'] = $item['share_id'];
					$record['p'] = Utils::buildRelPath(array('shareInfo' => $shareInfo, 'addToPath' => $subPath));
				} else {
					if (!$homeFolderPath) {continue;}
					$record['p'] = gluePath('/ROOT/HOME', substr($filePath, $homeFolderPathLength));
				}
			} else if ($mode == 'search') {
				if ($data['shareInfo']) {
					$shareInfo = $data['shareInfo'];
					$record['p'] = substr_replace($filePath, $relativePath, 0, strlen($data['fullPath']));
				} else {
					$record['p'] = substr_replace($filePath, '/ROOT/HOME', 0, $homeFolderPathLength);
				}
			} else {
				$record['p'] = substr_replace($filePath, '/ROOT/HOME', 0, $homeFolderPathLength);
			}
			$pathId = $item['pid'];
			if (!in_array($mode, ['recent', 'weblinked', 'collection'])) {
				$metaFileInfo = $item;
			}
		} else if ($mode == 'userWithShares') {
			$shareInfo = $item;
			$filePath = $shareInfo['path'];

			if ($shareInfo['alias']) {
				$fileName = $shareInfo['alias'];
			} else {
				$fileName = \FM::basename($shareInfo['path']);
			}

			$record['p'] = Utils::buildRelPath(['shareInfo' => $shareInfo]);
			$pathId = Paths::getId($filePath);
		} else if ($mode == 'shares') {
			$filePath = $item['path'];
			$fileName = \FM::basename($item['path']);
			$record['p'] = substr_replace($filePath, '/ROOT/HOME', 0, $homeFolderPathLength);
			$pathId = Paths::getId($filePath);
		} else {
			$fileName = $item;
			$filePath = gluePath($path, $fileName);
			$pathId = Paths::getId($filePath);
		}

		if (!$metaFileInfo) {
			if ($pathId) {
				$metaFileInfo = MetaFiles::getByPid("*", $pathId);
			} else {
				Paths::insertPath($filePath);
				$metaFileInfo = false;
			}
		}

		if (!@file_exists($filePath)) {continue;}

		$modified = @filemtime($filePath);
		$isFile = is_file($filePath);
		if ($mode == 'photos' || $mode == 'music') {
			if (!$isFile) {continue;}
		}

		$created = @filectime($filePath);

		if ($mode == 'photos') {
			$dateTaken = false;
			if ($dateTakenFieldInfo) {
				$dateTaken = trim($item['date_taken']);
				if ($dateTaken) {
					$dateTaken = Date::EXIFDate2Unix($dateTaken);
				}
			}
			if (!$dateTaken) {
				$dateTaken = $modified;
			}
			$record['dt'] = $dateTaken;
		}

		if ($metaFileInfo) {
			if ($displayTags) {
				$tags = Tags::getValuesByMetaFileId($metaFileInfo['id']);
				$record['tg'] = \S::safeForHtml(implode(', ', $tags));
			}
			if ($displayRating) {
				$rating = Rating::getByMetaFileId($metaFileInfo['id']);
				if ($rating) {
					$record['r'] = $rating;
				}
			}
			if ($metaFileInfo['type_id'] && $displayMetaDataFileType) {
				$record['mf'] = \S::safeForHtml(MetaTypes::getName($metaFileInfo['type_id']));//todo: cache
			}
			if (sizeof($metadataFields) > 0) {
				$medatadaInfo = MetaValues::getByFile($metaFileInfo['id'], $metadataFields);
				foreach ($medatadaInfo as $r) {
					$record['meta_' . $r['field_id']] = \S::safeForHtml($r['val']);
				}
			}
			if ($mode != 'starred') {
				$star = Stars::getByFileId($metaFileInfo['id'], $auth->currentUserInfo['id']);
				if ($star) {
					$record['st'] = 1;
				}
			}
			if ($perms['read_comments'] && (!$shareInfo || ($shareInfo && $shareInfo['perms_read_comments']))) {
				$commentsCount = Comments::countByMetaFileId($metaFileInfo['id']);
				if ($commentsCount > 0) {
					$record['cc'] = $commentsCount;
				}
				$label = Labels::getByFileId($metaFileInfo['id']);
				if ($label) {
					$record['l'] = \S::safeForHtml($label);
				}
			}
		}

		$record['n'] = (string) \S::safeHTMLFileName($fileName);
		$record['c'] = Date::Unix2Grid($created);
		$record['ch'] = Date::getShort($created);

		if (isValidTimeStamp($modified)) {
			$record['m'] = Date::Unix2Grid($modified);
			$record['mh'] = Date::getShort($modified);
		}

		if ($mode != 'shares') {
			if ($userCanShare) {
				if (Share::isShared($filePath)) {
					$record['sh'] = 1;
				}
			}
		}

		if ($isFile) {
			$fileSize = \FM::getFileSize($filePath);
			$fileTypeInfo = \FM::fileTypeInfo($fileName);

			$record['s'] = $fileSize;
			$record['ns'] = \FM::formatFileSize($fileSize);
			$record['i'] = $fileTypeInfo['icon'];
			$record['t'] = $fileTypeInfo['description'];
			$record['ft'] = $fileTypeInfo['type'];

			if ($perms['download'] && $fileSize > 0) {
				if (Thumbs\Utils::extCanHaveThumb($fileTypeInfo['extension'])) {
					if ($mode != 'userWithShares' || ($mode == 'userWithShares' && $item['perms_download'])) {
						$record['th'] = 1;
					}
				}
			}

			if ($listCount < 50) {
				$lockerUid = Versions::isLocked($filePath);
				if ($lockerUid) {
					$record['lI'] = \S::safeForHtml(Users::getNameById($lockerUid));
				}
				if ($settings->versioning_max) {
					$curVer = Versions::getCurrentNumber($filePath);
					if ($curVer) {
						$record['v'] = $curVer;
					}
				}
			}
		} else {
			$record['dir'] = 1;
			$record['t'] = 'Folder';

			if (isset($config['app']['ui']['folder_icon_css_class'][$filePath])) {
				$record['i'] = $config['app']['ui']['folder_icon_css_class'][$filePath];
			}

			if ($pathId) {
				if ($folderNotifications) {
					$fNotifInfo = Files\Notifications::getByPathId($pathId);
					if ($fNotifInfo) {
						$record['fn'] = [
							'w' => $fNotifInfo['notify_write'] ? 1 : 0,
							'r' => $fNotifInfo['notify_read'] ? 1 : 0
						];
					}
				}
			}
		}
		if ($lastLoginTimeStamp && $modified > $lastLoginTimeStamp) {
			$record['new'] = 1;
		}
		if ($mode != 'weblinked') {
			if ($pathId) {
				if ($perms['weblink']) {
					if (WebLinks::hasLinkByPid($pathId)) {
						$record['hW'] = 1;
					}
				}
			}
		}
		$count++;
		$record['id'] = (is_array($item) && $item['id']) ? $item['id'] : $count;
		$returnedList[] = $record;
	}
}