<?php

class custom_html_editor extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: HTML Editor";
	static $publicMethods = ['saveChanges'];

	function init() {

		$this->JSconfig = [
			"title" => self::t("HTML Editor"),
			'iconCls' => 'fa fa-fw fa-file-code-o',
			'extensions' => ['html', 'htm'],
			"popup" => true,
			"createNew" => [
				"title" => self::t("HTML File"),
				'defaultFileName' => self::t('index.html'),
				'iconCls' => 'fa fa-fw fa-file-code-o'
			],
			"requiredUserPerms" => ["download"],
			'requires' => ['download']
		];
	}

	function run() {
		$this->data['contents'] = $this->readFile(['logging' => ['details' => ['method' => 'HTML Editor']]]);
		$fileName = $this->prepareReadData['alias'] ?: \FM::basename($this->prepareReadData['fullPath']);
		require($this->path."/display.php");
	}

	function saveChanges() {
		$this->writeFile([
			'source' => 'string',
			'contents' => isset($_POST['textContents']) ? \S::fromHTML($_POST['textContents']) : '',
			'logging' => [
				'details' => [
					'method' => 'HTML Editor'
				]
			]
		]);
		jsonFeedback(true, 'File successfully saved');
	}

	function createBlankFile() {
		$this->saveChanges();
	}

	static function getTranslationCode() {
		$map = [
			'basque' => false,
			'brazilian portuguese' => 'pt-BR',
			'chinese traditional' => 'zh-TW',
			'chinese' => 'zh-CN',
			'danish' => 'da-DK',
			'dutch' => 'nl-NL',
			'english' => false,
			'finnish' => 'fi-FI',
			'french' => 'fr-FR',
			'german' => 'de-DE',
			'greek' => 'el-GR',
			'italian' => 'it-IT',
			'polish' => 'pl-PL',
			'romanian' => 'ro-RO',
			'russian' => 'ru-RU',
			'spanish' => 'es-ES',
			'swedish' => 'sv-SE',
			'turkish' => 'tr-TR'
		];
		$current = \FileRun\Lang::getCurrent();
		return $map[$current];
	}
}