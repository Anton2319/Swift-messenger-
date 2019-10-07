<?php

class custom_open_in_browser extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions';

	function init() {
		$this->JSconfig = [
			"title" => self::t('Open in browser'),
			'iconCls' => 'fa fa-fw fa-eye',
			'useWith' => ['nothing'],
			"requires" => ["download"]
		];
	}

	function run() {
		$this->downloadFile([
			'openInBrowser' => true,
			'logging' => ['details' => ['method' => 'Open in browser']]
		]);
	}
}