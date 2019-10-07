<?php
class custom_epub_reader extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: E-book Reader";
	static $publicMethods = ['stream'];

	function init() {
		$this->JSconfig = [
			"title" => self::t('E-book Reader'),
			'iconCls' => 'fa fa-fw fa-book',
			'extensions' => ['epub'],
			'popup' => true,
			"requiredUserPerms" => ["download"],
			"requires" => ["download"]
		];
	}

	function run() {
		require($this->path."/display.php");
	}

	function stream() {
		$this->downloadFile([
			'openInBrowser' => true,
			'logging' => ['details' => ['method' => 'E-book Reader']]
		]);
	}
}