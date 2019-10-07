<?php

class custom_arch extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions: Archive Explorer';

	function init() {
		$this->JSconfig = [
			"title" => self::t("Archive Explorer"),
			'iconCls' => 'fa fa-fw fa-file-archive-o',
			'useWith' => ['arch'],
			"popup" => true,
			'width' => 500, 'height' => 400,
			"requires" => ["download"]
		];
	}

	function run() {
		$data = $this->prepareRead();
		$arch = ArchUtil::init($data['fullPath']);
		if (!$arch) {
			exit("This type of archives is not supported by the current server configuration.");
		}
		$rs = $arch->open();
		if (!$rs) {
			exit($arch->error);
		}
		$list = $arch->getTOC(100);
		if (!is_array($list)) {
			exit("Failed to read archive contents!");
		}
		$arch->close();
		$count = $arch->itemsCount;
		require($this->path."/display.php");
		\FileRun\Log::add(false, "preview", [
			"relative_path" => $data['relativePath'],
			"full_path" => $data['fullPath'],
			"method" => "Archive Explorer"
		]);
	}
}