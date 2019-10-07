<?php
use \FileRun\Files\Actions\Thumbnail;

class custom_admin_thumbnail_troubleshooter extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions';

	function init() {
		$this->JSconfig = [
			'title' => self::t('Admin: Thumbnail troubleshooter'),
			'iconCls' => 'fa fa-fw fa-bug',
			"popup" => true,
			'width' => 500
		];
	}

	function isDisabled() {
		return !\FileRun\Perms::isSuperUser();
	}

	function run() {
		$data = Thumbnail::prepare($this->data['relativePath']);
		if (!$data) {
			exit(Thumbnail::getError());
		}
		Thumbnail::show($data, [
			'skipNotification' => true,
			'caching' => false,
			'debug' => true
		]);
	}
}