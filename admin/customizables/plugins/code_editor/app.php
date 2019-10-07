<?php

class custom_code_editor extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: Text Editor";
	static $publicMethods = ['saveChanges'];

	function init() {

		$this->JSconfig = [
			"title" => self::t("Text Editor"),
			'iconCls' => 'fa fa-fw fa-file-text-o',
			'useWith' => ['txt', 'noext'],
			"popup" => true,
			"createNew" => [
				"title" => self::t("Text File"),
				"options" => [
					[
						'fileName' => self::t('New Text File.txt'),
						'title' => self::t('Plain Text'),
						'iconCls' => 'fa fa-fw fa-file-text-o',
					],
					[
						'fileName' => 'script.js',
						'title' => self::t('JavaScript'),
						'iconCls' => 'fa fa-fw fa-file-code-o',
					],
					[
						'fileName' => 'style.css',
						'title' => self::t('CSS'),
						'iconCls' => 'fa fa-fw fa-file-code-o',
					],
					[
						'fileName' => 'index.php',
						'title' => self::t('PHP'),
						'iconCls' => 'fa fa-fw fa-file-code-o',
					],
					[
						'fileName' => 'readme.md',
						'title' => self::t('Markdown'),
						'iconCls' => 'fa fa-fw fa-file-code-o',
					],
					[
						'fileName' => '',
						'title' => self::t('Other..'),
						'iconCls' => 'fa fa-fw fa-file-text-o'
					]
				]
			],
			"requiredUserPerms" => ["download"],
			'requires' => ['download']
		];
	}

	function run() {
		$this->data['contents'] = $this->readFile(['logging' => ['details' => ['method' => 'Code Editor']]]);
		$fileName = $this->prepareReadData['alias'] ?: \FM::basename($this->prepareReadData['fullPath']);
		require($this->path."/display.php");
	}

	function saveChanges() {
		$contents = \S::fromHTML($_POST['textContents']);
		$charset = \S::fromHTML($_POST['charset']);
		if ($charset != 'UTF-8') {
			$contents = \S::convertEncoding($contents, 'UTF-8', $charset);
		}
		$this->writeFile([
			'source' => 'string',
			'contents' => $contents,
			'logging' => ['details' => ['method' => 'Code Editor']]
		]);
		jsonFeedback(true, 'File successfully saved');
	}

	function createBlankFile() {
		$_POST['textContents'] = '';
		$_POST['charset'] = 'UTF-8';
		$this->saveChanges();
	}
}