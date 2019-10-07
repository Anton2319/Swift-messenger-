<?php
namespace FileRun\Files;
use FileRun\Share;
use FileRun\Collections;
use FileRun\Users;
use FileRun\Perms;

class Utils {

	static $hiddenFilePatterns;
	static $errMsg;

	static function getError() {
		$error = self::$errMsg;
		self::$errMsg = false;
		return $error;
	}

	static function isCleanPath($path) {
		if (!$path) {return false;}
		if ($path[0] === ' ') {return false;}
		if ($path == '.') {return false;}
		if ($path == '..') {return false;}
		if (strpos($path, '<')  !== false){return false;}
		if (strpos($path, '>')  !== false){return false;}
		return !(
			preg_match("/^\.{1,}\//",$path) ||
			preg_match("/\/\.{1,}$/",$path) ||
			preg_match("/\/\.{1,}\//",$path)
		);
	}

	static function isCleanFileName($filename) {
		if ($filename == ".." || $filename == ".") {return false;}
		if (strpos($filename, "./")  !== false){return false;}
		$illegalChars = ["\\","/","*","?","<",">","|","\""];
		if (\FM::getOS() == "win") {$illegalChars[] = ':';}
		foreach ($illegalChars as $c) {if (strpos($filename, $c) !== false) {return false;}}
		return true;
	}

	static function parsePath($path) {
		if ($path == '/ROOT') {
			return ['type' => 'root'];
		}
		if ($path == '/ROOT/SHARED') {
			return ['type' => 'users_with_shares'];
		}
		$split = explode("/", $path);
		if ($split['2'] == 'STARRED') {
			return ['type' => 'starred'];
		}
		$data = ['type' => false];
		$relativePathPartIndex = false;
		if ($split['2'] == 'TRASH') {
			if (is_numeric($split[3])) {
				$data['trash_id'] = $split[3];
			}
			if ($data['trash_id']) {
				$data['type'] = 'trash';
			}
		} else if ($split['2'] == 'Collections') {
			$data['type'] = 'collection';
			if (is_numeric($split[3])) {
				$data['collection_id'] = $split[3];
			}
			if ($split[4]) {
				$data['collection_item_id'] = $split[4];
				$data['type'] = 'collection_item';
			}
			$relativePathPartIndex = 5;
		} else if ($split['2'] == 'SHARED') {
			$data['type'] = 'shared';
			if (is_numeric($split[3])) {
				$data['uid'] = $split[3];
				if (is_numeric($split[4])) {
					$data['share_id'] = $split[4];
					$relativePathPartIndex = 5;
				}
			}
		} else {
			$relativePathPartIndex = 3;
			if ($split['2'] == "HOME") {
				$data['type'] = 'home';
			} else {
				if (strstr($split[2], ':')) {
					$s = explode(':', $split[2]);
					if (is_numeric($s[0])) {
						$data['uid'] = $s[0];
					}
					if (is_numeric($s[1])) {
						$data['share_id'] = $s[1];
					}
				} else {
					$relativePathPartIndex = 4;
					if (is_numeric($split[2])) {
						$data['uid'] = $split[2];
					}
					if (is_numeric($split[3])) {
						$data['share_id'] = $split[3];
					}
				}
				if ($data['uid'] || $data['share_id']) {
					$data['type'] = 'shared';
				}
			}
		}
		if ($relativePathPartIndex) {
			$data['relative_path'] = implode("/", array_slice($split, $relativePathPartIndex));
		}
		return $data;
	}

	static function isSharedPath($path):bool {
		$pathInfo = self::parsePath($path);
		return ($pathInfo['type'] == 'shared');
	}

	static function isTrashPath($path):bool {
		$pathInfo = self::parsePath($path);
		return ($pathInfo['type'] == 'trash');
	}

	static function getPathInfo($relativePath) {
		$info = [
			'relativePath' => $relativePath,
			'relativePathInfo' => self::parsePath($relativePath)
		];
		if ($info['relativePathInfo']['type'] == 'shared') {
			if (!$info['relativePathInfo']['share_id']) {
				self::$errMsg = ['public' => 'Invalid shared folder path!'];
				return false;
			}
			$info['shareInfo'] = Share::getInfoById($info['relativePathInfo']['share_id']);
			if (!$info['shareInfo']) {
				self::$errMsg = ['public' => 'The share is no longer available.'];
				return false;
			}
			$info['fullPath'] = $info['shareInfo']['path'];
			if ($info['relativePathInfo']['relative_path']) {
				$info['fullPath'] = gluePath($info['shareInfo']['path'], $info['relativePathInfo']['relative_path']);
			}
			if ($info['shareInfo']['alias']) {
				if (!$info['relativePathInfo']['relative_path']) {
					$info['alias'] = $info['shareInfo']['alias'];
				}
			}
			$info['uid'] = $info['shareInfo']['uid'];
			$info['userHomeFolderPath'] = Perms::getOne('homefolder', $info['shareInfo']['uid']);
			if (!$info['userHomeFolderPath']) {
				self::$errMsg = ['public' => 'User does not have a home folder assigned!'];
				return false;
			}
		} else {
			global $auth;
			$info['uid'] = $auth->currentUserInfo['id'];
			$info['userHomeFolderPath'] = Perms::getOne('homefolder');
			if ($info['relativePathInfo']['type'] == 'home') {
				if (!$info['userHomeFolderPath']) {
					self::$errMsg = ['public' => 'User does not have a home folder assigned!'];
					return false;
				}
				$info['fullPath'] = gluePath($info['userHomeFolderPath'], $info['relativePathInfo']['relative_path']);
			} else if ($info['relativePathInfo']['type'] == 'trash') {
				$trashRecord = Trash::getInfo($info['relativePathInfo']['trash_id']);
				if (!$trashRecord) {
					self::$errMsg = ['public' => 'Trashed file not found!'];
					return false;
				}
				if ($trashRecord['uid'] != $auth->currentUserInfo['id']) {
					self::$errMsg = ['public' => 'Trashed file belongs to different user!'];
					return false;
				}
				$info['fullPath'] = Trash::getTrashPath($trashRecord);
				$info['alias'] = \FM::basename($trashRecord['relative_path']);
			} else if ($info['relativePathInfo']['type'] == 'collection' || $info['relativePathInfo']['type'] == 'collection_item') {
				$info['collectionInfo'] = Collections\Collections\Prepare::byId($info['relativePathInfo']['collection_id']);
				if (!$info['collectionInfo']) {
					self::$errMsg = Collections\Collections\Prepare::getError();
					return false;
				}
				if ($info['relativePathInfo']['collection_item_id']) {
					$info['collectionItemInfo'] = Collections\Items::getInfo($info['relativePathInfo']['collection_item_id']);
					if (!$info['collectionItemInfo']) {
						self::$errMsg = ['public' => 'Collection item not found!'];
						return false;
					}
				}
			} else {
				self::$errMsg = ['public' => 'Invalid path!'];
				return false;
			}
		}
		return $info;
	}

	static function humanRelPath($path) {
		$pathInfo = self::parsePath($path);
		if ($pathInfo['type'] == 'shared') {
			$rs = "";
			$uName = Users::getNameById($pathInfo['uid']);
			if ($uName) {
				$rs .= "[".$uName."]";
			} else {
				$rs .= "[Deleted user (".$pathInfo['uid'].")]";
			}
			if ($pathInfo['share_id']) {
				$shareInfo = Share::getById($pathInfo['share_id']);
				if ($shareInfo) {
					if ($shareInfo['alias']) {
						$rs .= "/" . $shareInfo['alias'];
					} else {
						$rs .= "/" . \FM::basename($shareInfo['path']);
					}
				} else {
					$rs .= "/[Deleted share]";
				}
			}
			if ($pathInfo['relative_path']) {
				$rs .= '/'.$pathInfo['relative_path'];
			}
		} else if ($pathInfo['type'] == 'home') {
			$rs = substr($path, 6);
		} else if ($pathInfo['type'] == 'collection') {
			$collectionInfo = Collections\Collections\Prepare::byId($pathInfo['collection_id']);
			if (!$collectionInfo) {
				$rs = '[Deleted collection]';
			} else {
				$rs = 'Collections/'.$collectionInfo['name'];
			}
		} else if ($pathInfo['type'] == 'trash') {
			$rs = '[Trash]';
		} else {
			$rs = $path;
		}
		return $rs;
	}

	static function buildRelPath($data) {
		if ($data['root']) {
			$path = $data['root'];
		} else {
			$path = '/ROOT';
		}
		if ($data['shareInfo']) {
			if ($data['shareInfo']['anonymous']) {
				$path = gluePath($path, $data['shareInfo']['uid'].':'.$data['shareInfo']['id']);
			} else {
				$path = gluePath($path, $data['shareInfo']['uid'], $data['shareInfo']['id']);
			}
			$path = gluePath($path, \FM::stripRoot($data['fullPath'], $data['shareInfo']['path']));
		} else {
			$path = gluePath($path, 'HOME');
			if ($data['homeFolderPath'] && $data['fullPath']) {
				$path = gluePath($path, \FM::stripRoot($data['fullPath'], $data['homeFolderPath']));
			}
		}
		if ($data['addToPath']) {
			$path = gluePath($path, $data['addToPath']);
		}
		return $path;
	}

	//only for /ROOT/HOME
	static function getRelativePath($fullPath, $uid = false) {
		$userHomeFolderPath = Perms::getOne('homefolder', $uid);
		if (!$userHomeFolderPath) {
			self::$errMsg = [
				'private' => 'Failed to retrieve home folder path for user with ID '.$uid,
				'public' => 'Internal path error!'
			];
			return false;
		}
		return self::buildRelPath([
			'homeFolderPath' => $userHomeFolderPath,
			'fullPath' => $fullPath
		]);
	}

	static function findUsersWithHomeFoldersUnderPath($path) {
		//get list of users who's home folder is located inside the specified folder
		$d = \FileRun\Utils\DP::factory('df_users_permissions');
		return $d->selectColumn('uid', [
			['homefolder', 'LIKE', $d->q(gluePath($path, '/%'))],
			['homefolder', '=', $d->q(rtrim($path, '/')), 'OR']
		]);
	}

	static function findUsersInPath($path) {
		//get list of users who's home folder includes the path
		$d = \FileRun\Utils\DP::factory('df_users_permissions');
		$rs = $d->select(['uid', 'homefolder'], [
			["SUBSTRING(".$d->q($path).", 1, CHAR_LENGTH(homefolder))", '=', 'homefolder'],
			['homefolder', '!=', "''"]
		]);
		$uids = [];
		if (is_array($rs)) {
			foreach ($rs as $record) {
				//avoid paths that are similar but only part of folder name ("jsmith" is part of "jsmith1")
				//so if slash or end of string comes after, it's all good
				$nextChar = substr($path, strlen($record['homefolder']), 1);
				if ($nextChar == '/' || $nextChar == '') {
					$uids[] = $record['uid'];
				}
			}
		}
		return $uids;
	}

	static function getUploadChunkSize() {
		$upload_max_filesize = strtoupper(ini_get("upload_max_filesize"));
		$post_max_size = strtoupper(ini_get("post_max_size"));
		//UPLOAD_MAX_FILESIZE
		$minSafe = 2097152; //just less than 2MB
		$maxUseful = 20971520; //20 megabytes
		$lastChar = substr($upload_max_filesize, -1);
		if ($lastChar == "K") {
			$chunkSize1 = (int)$upload_max_filesize * 1024;
		} else if ($lastChar == "M") {
			$chunkSize1 = (int)$upload_max_filesize * 1048576;
		} else if ($lastChar == "G") {
			$chunkSize1 = $maxUseful;
		} else {
			$chunkSize1 = $minSafe;
		}
		//POST_MAX_SIZE
		$lastChar = substr($post_max_size, -1);
		if ($lastChar == "K") {
			$chunkSize2 = (int)$post_max_size * 1024;
		} else if ($lastChar == "M") {
			$chunkSize2 = (int)$post_max_size * 1048576;
		} else if ($lastChar == "G") {
			$chunkSize2 = $maxUseful;
		} else {
			$chunkSize2 = $minSafe;
		}
		$chunkSize = min($maxUseful, $chunkSize1, $chunkSize2);
		$chunkSize = $chunkSize-10240; //minus 10KB just to make sure
		return $chunkSize;
	}

	static function getHiddenFilePatterns() {
		global $config, $auth;
		if (self::$hiddenFilePatterns) {
			return $config['app']['hidden_file_names'];
		}
		if (is_array($config['app']['custom_hidden_files']['groups'])) {
			$groups = \FileRun\UserGroups::selectOneUsersGroups($auth->currentUserInfo['id']);
			foreach ($config['app']['custom_hidden_files']['groups'] as $gid => $ext) {
				if (in_array($gid, $groups)) {
					$config['app']['hidden_file_names'] = array_merge($config['app']['hidden_file_names'], $ext);
				}
			}
		}
		if (is_array($config['app']['custom_hidden_files']['users'])) {
			foreach ($config['app']['custom_hidden_files']['users'] as $uid => $ext) {
				if ($uid == $auth->currentUserInfo['id']) {
					$config['app']['hidden_file_names'] = array_merge($config['app']['hidden_file_names'], $ext);
				}
			}
		}
		if (is_array($config['app']['custom_hidden_files']['roles'])) {
			$role = \FileRun\Perms::getRoleByUserId($auth->currentUserInfo['id']);
			foreach ($config['app']['custom_hidden_files']['roles'] as $rid => $ext) {
				if ($rid == $role) {
					$config['app']['hidden_file_names'] = array_merge($config['app']['hidden_file_names'], $ext);
				}
			}
		}
		self::$hiddenFilePatterns = true;
		return array_unique($config['app']['hidden_file_names']);
	}

	static function glueFiles($firstPath, $secondPath) {
		if (!file_exists($firstPath)) {
			self::$errMsg = "glueFiles error: File \"".$firstPath."\" doesn't exist.";
			return false;
		}
		if (!file_exists($secondPath)) {
			self::$errMsg = "glueFiles error: File \"".$secondPath."\" doesn't exist.";
			return false;
		}
		$src = fopen($secondPath, "rb");
		if (!$src) {
			self::$errMsg = "glueFiles error: Unable to open source file for reading.";
			return false;
		}
		$trg = fopen($firstPath, "ab");
		if (!$trg) {
			self::$errMsg = "glueFiles error: Unable to open target file for appending.";
			return false;
		}
		while (($buf = fread($src, 10485760)) != '') {
			$rs = fwrite($trg, $buf);
			unset($buf);
			if (!$rs) {
				self::$errMsg = "glueFiles error: Unable to write to file.";
				break;
			}
		}
		fclose($trg);
		fclose($src);
		return true;
	}

	//todo: find better location for it
	static function fetchFromURL($url, $savePath = false, $postVars = false, $overwrite = false) {
		global $config;
		$http = new \GuzzleHttp\Client();
		try {
			$method = 'GET';
			$options = [];
			if ($savePath) {
				$options['stream'] = true;
			}
			if ($postVars) {
				$method = 'POST';
				$options['form_params'] = $postVars;
			}
			if ($config['system']['http']['proxy']) {
				$options['proxy'] = $config['system']['http']['proxy'];
			}
			$response = $http->request($method, $url, $options);
		} catch (\GuzzleHttp\Exception\ConnectException $e) {
			self::$errMsg = 'Network connection error: '.$e->getMessage();
			return false;
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			self::$errMsg = 'HTTP error: '.$e->getResponse()->getStatusCode();
			return false;
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			self::$errMsg = 'Server error: '.$e->getResponse()->getStatusCode();
			return false;
		} catch (\RuntimeException $e) {
			self::$errMsg = 'Error: '.$e->getMessage();
			return false;
		}
		if (!$savePath) {
			return $response->getBody()->getContents();
		}
		if (file_exists($savePath)) {
			if ($overwrite) {
				$rs = @unlink($savePath);
				if (!$rs) {
					$err = error_get_last();
					self::$errMsg = "Delete error: ".$err['message'];
					return false;
				}
			} else {
				self::$errMsg = 'File already exists: ' . $savePath;
				return false;
			}
		}
		$fp = @fopen($savePath, 'wb');
		if (!$fp) {
			self::$errMsg = 'Failed to open file: ' . $savePath;
			return false;
		}
		$body = $response->getBody();
		while (!$body->eof()) {
			$piece = $body->read(1024);
			$len = strlen($piece);
			if ($len) {
				$rs = @fwrite($fp, $piece, $len);
				if (!$rs) {
					self::$errMsg = 'Failed to write data to file: ' . $savePath;
					return false;
				}
			}
		}
		@fclose($fp);
		return true;
	}
}