<?php
use \FileRun\WebLinks;

class custom_bing_kml_viewer extends \FileRun\Files\Plugin {

	var $online = true;
	static $localeSection = 'Custom Actions: Bing Maps';

	function init() {
		$this->settings = [
			[
				'key' => 'APIKey',
				'title' => self::t('Bing Maps API Key'),
				'comment' => '<a href="https://msdn.microsoft.com/en-us/library/ff428642.aspx" target="_blank">Getting a Bing Maps Key</a>'
			]
		];
		$this->JSconfig = [
			"title" => self::t("Bing Maps"),
			'icon' => 'images/icons/bing.png',
			"extensions" => ["xml", "kmz", "gpx"],
			"popup" => true,
			"requiredUserPerms" => ["download"],
			"requires" => ["download"]
		];
	}

	function isDisabled() {
		return (strlen(self::getSetting('APIKey')) == 0);
	}

	function run() {
		$data = $this->prepareRead(['expect' => 'file']);
		$url = WebLinks::getOneTimeDownloadLink($data['fullPath'], $data['shareInfo']['id']);
		if (!$url) {exit("Failed to setup weblink");}
		\FileRun\Log::add(false, "preview", [
			"relative_path" => $data['relativePath'],
			"full_path" => $data['fullPath'],
			"method" => 'Bing Maps'
		]);
		require($this->path."/display.php");
	}
}