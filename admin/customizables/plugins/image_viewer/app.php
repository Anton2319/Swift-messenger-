<?php

class custom_image_viewer extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: Image Viewer";

	function init() {
		$this->JSconfig = [
			"title" => self::t("Image Viewer"),
			'iconCls' => 'fa fa-fw fa-picture-o',
			'useWith' => ['nothing']
		];
	}

	function run() {
		$data = $this->prepareRead(['expect' => 'file', 'errorHandling' => 'html']);
		$fileName = $data['alias'] ?: \FM::basename($data['fullPath']);
		$ext = \FM::getExtension($fileName);
		if (!\FileRun\Thumbs\Utils::isWebSafe($ext)) {
			exit('This file type is not supported by the web browsers.');
		}
		global $config;
		$url = $config['url']['root'].'?module=custom_actions&action=open_in_browser&path='.\S::forURL($this->data['relativePath']);
		if ($this->data['version']) {
			$url .= '&version='.\S::forURL($this->data['version']);
		}
		?>
		<html>
		<head>
			<title><?php echo $this->JSconfig['title'];?></title>
			<style>
				body {
					border: 0;  margin: 0;  padding: 0;  overflow:hidden;
					background-size:contain;
					background-position:center;
					background-repeat:no-repeat;
					<?php
					echo 'background-image:url('.$url.');';
					?>
			</style>
		</head>
		<body>
		</body>
		</html>
		<?php
	}
}