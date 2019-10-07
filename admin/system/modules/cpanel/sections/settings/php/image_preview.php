<?php
namespace FileRun;

if (!Perms::isSuperUser()) {
	jsonFeedback(false, "You are not allowed to access this page.");
}

if ($_GET['action']) {
	if ($config['misc']['demoMode']) {
		echo "<div class=\"error\">" . Lang::t("Action unavailable in this demo version of the software!") . "</div>";
		exit();
	}
	$path = \S::fromHTML($_POST['path']);
	$path = \FM::normalizePath($path);
	if (strlen($path) < 1) {
		echo "<div class=\"error\">" . Lang::t("Please specify a path!") . "</div>";
		exit();
	}
	$return_text = [];
	$return_code = 0;

	if ($_GET['action'] == "checkImageMagick") {
		$cmd = $path . " -version";
		exec($cmd, $return_text, $return_code);
		$return_text = implode("<br>", $return_text);
		if (strpos(strtolower($return_text), "magick") === false) {
			echo "<div class=\"error\">Error: " . $return_code . ": " . $return_text . "</div>";
		} else {
			echo "<div class=\"ok\">" . $return_text . "</div>";
		}
	} else if ($_GET['action'] == "checkpngquant") {
		$cmd = $path . " --version";
		exec($cmd, $return_text, $return_code);
		$return_text = implode("<br>", $return_text);
		echo "<div>" . $return_text . "</div>";
	} else if ($_GET['action'] == "checkFFmpeg") {
		$cmd = $path . " -version";
		exec($cmd, $return_text, $return_code);
		$return_text = implode("<br>", $return_text);
		if (strpos(strtolower($return_text), "ffmpeg") === false) {
			echo "<div class=\"error\">Error: " . $return_code . ": " . $return_text . "</div>";
		} else {
			echo "<div class=\"ok\">" . $return_text . "</div>";
		}
	} else if ($_GET['action'] == "checkLibreOffice") {
		$cmd = $path . " --headless --nologo --nofirststartwizard --norestore --version";
		exec($cmd, $return_text, $return_code);
		$return_text = implode("<br>", $return_text);
		if (strpos(strtolower($return_text), "libreoffice") === false) {
			echo "<div class=\"error\">Error: " . $return_code . ": " . $return_text . "</div>";
		} else {
			echo "<div class=\"ok\">" . $return_text . "</div>";
		}
	} else if ($_GET['action'] == "checkStlPath") {
		$cmd = $path . " --version";
		exec($cmd, $return_text, $return_code);
		$return_text = implode("<br>", $return_text);
		if (strpos(strtolower($return_text), "stl-thumb") === false) {
			echo "<div class=\"error\">Error: " . $return_code . ": " . $return_text . "</div>";
		} else {
			echo "<div class=\"ok\">" . $return_text . "</div>";
		}
	}
	exit();
}


$t = [];
foreach ($settings->data as $key => $val) {
	if (strstr($key, "thumbnails_") !== false) {
		$t[$key] = $val;
	}
}
$app['AllSettings'] = json_encode($t);
\FileRun::displaySmartyPage();