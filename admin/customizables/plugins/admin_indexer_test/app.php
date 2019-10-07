<?php

class custom_admin_indexer_test extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions';

	function init() {
		$this->JSconfig = [
			'title' => self::t('Admin: Text Indexer Test'),
			'iconCls' => 'fa fa-fw fa-bug',
			"popup" => true,
			'width' => 500,
			'requires' => ['section-myfiles']
		];
	}

	function isDisabled() {
		global $settings;
		return (!$settings->search_enable || !\FileRun\Perms::isSuperUser());
	}

	function run() {
		$data = $this->prepareRead(['expect' => 'file']);

		$rs = \FileRun\Files\Search\Utils::isIndexable($data['fullPath']);
		if (!$rs) {
			echo self::t('This file cannot be indexed');
			exit();
		}

		header('Content-type: text/plain; charset=UTF-8');
		try {
			echo \FileRun\Files\Search\Index::getTextContents($data['fullPath']);
		} catch(Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}
}