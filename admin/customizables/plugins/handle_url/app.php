<?php

class custom_handle_url extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions: Link Opener';

	function init() {
		$this->JSconfig = [
			'title' => self::t('Link Opener'),
			'iconCls' => 'fa fa-fw fa-share-square-o',
			'extensions' => ['url'],
			'popup' => true, "external" => true,
			"requiredUserPerms" => ["download"],
			'requires' => ['download'],
			'replaceDoubleClickAction' => true
		];
	}
	function run() {
		$this->data['contents'] = $this->readFile(['logging' => ['actionName' => 'preview', 'details' => ['method' => 'Link Opener']]]);
		$c = explode("\n", $this->data['contents']);
		foreach ($c as $r) {
			if (stristr($r, 'URL=') !== false) {
				header('Location: '.str_ireplace(['URL=', '\''], [''], $r));
				exit();
			}
		}
	}
}
