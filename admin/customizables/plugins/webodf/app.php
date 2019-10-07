<?php

class custom_webodf extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: OpenDocument Viewer";
	static $publicMethods = ['download'];

	function init() {
		$this->JSconfig = [
			"title" => self::t("OpenDocument Viewer"),
			'iconCls' => 'fa fa-fw fa-file-text-o',
			"extensions" => ["odt", "ods", "odp"],
			"popup" => true,
			"requiredUserPerms" => ["download"],
			"requires" => ["download"]
		];
	}
	function run() {
		require($this->path."/display.php");
	}
	function download() {
		$this->downloadFile([
			'openInBrowser' => true,
			'logging' => ['details' => ['method' => 'OpenDocument Viewer']]
		]);
	}
}