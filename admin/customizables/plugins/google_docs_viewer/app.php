<?php

class custom_google_docs_viewer extends \FileRun\Files\Plugin {

	var $online = true;
	static $localeSection = "Custom Actions: Google Docs Viewer";

	function init() {
		$this->JSconfig = [
			"title" => self::t("Google Docs Viewer"),
			'icon' => 'images/icons/gdocs.png',
			"extensions" => [
				"pdf", "ppt", "pptx", "doc", "docx", "xls", "xlsx", "dxf", "ps", "eps", "xps",
				"psd", "tif", "tiff", "bmp", "svg",
				"pages", "ai", "dxf", "ttf"
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
			"method" => "Google Docs Viewer"
		]);
?>
<html>
<head>
<title><?php echo $this->JSconfig['title'];?></title>
<style>body {  border: 0;  margin: 0;  padding: 0;  overflow:hidden;  }</style>
</head>
<body>
<iframe scrolling="no" width="100%" height="100%" border="0" src="https://docs.google.com/viewer?url=<?php echo urlencode($url)?>&embedded=true">
</iframe> 
</body>
</html>
<?php
	}
}