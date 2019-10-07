<?php
namespace FileRun;
$uid = (int) \S::fromHTML($_REQUEST['uid']);
if (!$uid) {exit('Missing uid');}

$debug = (\S::fromHTML($_REQUEST['debug']) && Perms::isSuperUser());

$userInfo = Users::getInfo($uid, ['name', 'name2', 'email', 'avatar'], false);
if (!$userInfo) {
	if ($debug) {echo 'User account not found.';exit();}
	Utils\Downloads::sendImageToBrowser($config['path']['data'].'/avatars/invalid.png');
	exit();
}

$text = mb_strtoupper(mb_substr($userInfo['name'], 0, 1));
if ($userInfo['name2']) {
	$text .= mb_strtoupper(mb_substr($userInfo['name2'], 0, 1));
} else {
	$text .= mb_substr($userInfo['name'], 1, 1);
}

$imagePath = Utils\Avatar::getPath($uid);

$fileExists = is_file($imagePath);
$avatarInfo = json_decode($userInfo['avatar'], true);

if ($fileExists) { //decide if needs refreshing
	if ($debug) {echo 'Image file exists.<br>';}
	if ($avatarInfo['type'] == 'gravatar' && $settings->gravatar_enabled && $userInfo['email']) {
		if (($avatarInfo['email'] != $userInfo['email']) //address changed, refresh
			|| (filemtime($imagePath)+1036800 < time()) //refresh every 12 days
		) {
			$rs = Utils\Avatar::getGravatar($userInfo['email'], $imagePath);
			if ($rs) {
				Users::getTable()->updateById(['avatar' => json_encode([
					'type' => 'gravatar',
					'email' => $userInfo['email']
				])], $uid);
				Utils\Downloads::sendImageToBrowser($imagePath);
				exit();
			}
			$avatarInfo['type'] = '';
		} else {
			Utils\Downloads::sendImageToBrowser($imagePath);
			exit();
		}
	}

	if ($avatarInfo['type'] == 'custom') {
		Utils\Downloads::sendImageToBrowser($imagePath);
		exit();
	}

	if ($avatarInfo['type'] != 'gravatar'  //it might reach here because the above gravatar update failed
		&& $settings->gravatar_enabled && $userInfo['email']) {
		if ($avatarInfo['email'] != $userInfo['email'] //address changed, so even if no avatar was previously used, try to set one now
			|| ($avatarInfo['lastUpdate'] && $avatarInfo['lastUpdate']+86400 < time()) //try once every 24 hours to find a gravatar
		) {
			$rs = Utils\Avatar::getGravatar($userInfo['email'], $imagePath);
			if ($rs) {
				Users::getTable()->updateById(['avatar' => json_encode([
					'type' => 'gravatar',
					'email' => $userInfo['email']
				])], $uid);
				Utils\Downloads::sendImageToBrowser($imagePath);
				exit();
			}
			$avatarInfo['email'] = $userInfo['email'];
			Users::getTable()->updateById(['avatar' => json_encode($avatarInfo)], $uid);
		}
	}

	if ($text != $avatarInfo['text'] || !$avatarInfo['lastUpdate']) {
		$color = $avatarInfo['color'];
		if (!$color) {
			$color = Utils\Avatar::getColor($uid);
		}
		$rs = Utils\Avatar::generate($color, $text, $imagePath);
		if ($rs) {
			Users::getTable()->updateById(['avatar' => json_encode([
				'type' => 'generated',
				'color' => $color,
				'text' => $text,
				'lastUpdate' => time()
			])], $uid);
		} else {
			$imagePath = $config['path']['data'].'/avatars/invalid.png';
		}
	}

	Utils\Downloads::sendImageToBrowser($imagePath);
	exit();
}
if ($debug) {echo 'No image on file.<br>';}

//GRAVATAR
if ($settings->gravatar_enabled && $userInfo['email']) {
	//check for gravatar
	if ($debug) {echo 'Checking for gravatar.<br>';}
	$rs = Utils\Avatar::getGravatar($userInfo['email'], $imagePath);
	if ($rs) {
		if ($debug) {echo 'Gravatar downloaded.<br>';}
		Users::getTable()->updateById(['avatar' => json_encode([
			'type' => 'gravatar',
			'email' => $userInfo['email']
		])], $uid);
		if ($debug) {exit();}
		Utils\Downloads::sendImageToBrowser($imagePath);
		exit();
	}
	if ($debug) {echo 'No gravatar.<br>';}
}

//COLOR
$color = Utils\Avatar::getColor($uid);
if ($debug) {echo 'Generating colorful avatar.<br>';}
$rs = Utils\Avatar::generate($color, $text, $imagePath);
if ($rs) {
	Users::getTable()->updateById(['avatar' => json_encode([
		'type' => 'generated',
		'color' => $color,
		'text' => $text
	])], $uid);
} else {
	if ($debug) {echo 'Failed. Outputing generic one.<br>';}
	$imagePath = $config['path']['data'].'/avatars/invalid.png';
}

if ($debug) {exit();}
Utils\Downloads::sendImageToBrowser($imagePath);
exit();