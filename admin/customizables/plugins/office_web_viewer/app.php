<?php

class custom_office_web_viewer extends \FileRun\Files\Plugin {

	var $online = true;
	static $localeSection = "Custom Actions: Office Web Viewer";

	function init() {
		$this->JSconfig = [
			"title" => self::t("Office Web Viewer"),
			"icon" => 'images/icons/office.png',
			"extensions" => [
				"doc", "docx", "docm", "dotm", "dotx",
				"xls", "xlsx", "xlsb", "xls", "xlsm",
				"ppt", "pptx", "ppsx", "pps", "pptm", "potm", "ppam", "potx", "ppsm"
			],
			"popup" => true,
			"requiredUserPerms" => ["download"],
			"requires" => ["download"]
		];
	}
	function run() {
		$data = $this->prepareRead(['expect' => 'file']);
		$url = \FileRun\WebLinks::getOneTimeDownloadLink($data['fullPath'], $data['shareInfo']['id']);
		if (!$url) {
			self::outputError('Failed to setup weblink', 'html');
		}
		\FileRun\Log::add(false, "preview", [
			"full_path" => $data['fullPath'],
			"relative_path" => $data['relativePath'],
			"method" => "Office Web Viewer"
		]);
?>
<html>
<head>
	<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
	<style>
		body {border:0; margin:0; padding:0; overflow:hidden;}
	</style>
</head>
<body>
<iframe scrolling="no" width="100%" height="100%" border="0" src="https://view.officeapps.live.com/op/view.aspx?src=<?php echo urlencode($url)?>"></iframe>
</body>
</html>
<?php
	}
}