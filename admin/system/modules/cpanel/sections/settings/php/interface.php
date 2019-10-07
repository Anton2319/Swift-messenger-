<?php
namespace FileRun;
if (!Perms::isSuperUser()) {
	jsonFeedback(false, "You are not allowed to access this page.");
}

if ($_GET['action'] == 'upload_translation') {
	if ($config['misc']['demoMode']) {
		jsonFeedback(false, 'Action unavailable in this demo version of the software!');
	}
	$PHPUploadErrorMessages = [
		'0' => false,
		'1' => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		'2' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		'3' => 'The uploaded file was only partially uploaded.',
		'4' => 'No file was uploaded.',
		'6' => 'Missing a temporary folder.',
		'7' => 'Failed to write file to disk.',
		'8' => 'File upload stopped by extension.'
	];

	$uploadFile = $_FILES['file'];
	$PHPUploadError = $PHPUploadErrorMessages[$uploadFile['error']];
	$tmpPath = $uploadFile['tmp_name'];
	if ($_REQUEST['flowFilename']) {
		$niceFilename = \S::fromHTML($_REQUEST['flowFilename']);
	} else {
		$niceFilename = \S::fromHTML($uploadFile['name']);
	}
	$niceFilename = strtolower($niceFilename);
	$ext = \FM::getExtension($niceFilename);
	if ($ext != 'php') {
		jsonFeedback(false, 'Please upload only .php FileRun-specific language files!');
	}
	if (strpos($niceFilename, ' ') !== false) {
		jsonFeedback(false, 'The filename should not contain space characters!');
	}
	if (!Files\Utils::isCleanFileName($niceFilename)) {
		jsonFeedback(false, 'Invalid file name!');
	}
	$targetPath = gluePath(Lang::getDataFolderPath(), $niceFilename);

	if ($PHPUploadError) {
		jsonFeedback(false, Lang::t("Failed to upload file \"%1\": %2", false, [$niceFilename, $PHPUploadError]));
	}

	$data = file_get_contents($tmpPath);
	$fileExists = is_file($targetPath);
	if ($fileExists) {
		$rs = \FM::deleteFile($targetPath);
		if (!$rs) {
			jsonFeedback(false, Lang::t("Failed to upload language file: %1", false, [\FM::$errMsg]));
		}
	}
	$rs = \FM::newFile($targetPath, $data);

	$languageName = '"'.ucwords(\FM::stripExtension(str_replace('_', ' ', $niceFilename))).'"';
	if (!$rs) {
		jsonFeedback(false, Lang::t("Failed to upload language file: %1", false, [\FM::$errMsg]));
	}
	if ($fileExists) {
		jsonFeedback(true, Lang::t('%1 translation successfully updated', false, [$languageName]));
	}
	jsonFeedback(true, Lang::t('%1 translation successfully uploaded', false, [$languageName]));
	exit();
}


$t = [];
foreach ($settings->data as $key => $val) {
	if (substr($key, 0, 3) == "ui_" || $key == 'thumbnails_size') {
		$t[$key] = $val;
	}
}
$app['AllSettings'] = json_encode($t);

$app['langs'] = [];
$list = UI\TranslationUtils::listAvailable();
foreach ($list as $key => $val) {
	$app['langs'][] = [$key, $val['displayName']];
}
$app['langs'] = json_encode($app['langs']);

\FileRun::displaySmartyPage();