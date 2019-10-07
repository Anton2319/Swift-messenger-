<?php

class custom_markdown_viewer extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions: Markdown Viewer';

	function init() {
		$this->JSconfig = [
			"title" => self::t("Markdown Viewer"),
			'iconCls' => 'fa fa-fw fa-quote-right',
			'extensions' => ['md', 'markdown'],
			"popup" => true,
			"requiredUserPerms" => ["download"],
			"requires" => ["download"]
		];
	}

	function run() {
		$this->data['contents'] = $this->readFile(['logging' => ['actionName' => 'preview', 'details' => ['method' => 'Markdown Viewer']]]);
		$fileName = $this->prepareReadData['alias'] ?: \FM::basename($this->prepareReadData['fullPath']);
		$enc = mb_list_encodings();
		if ($_REQUEST['charset'] && in_array($_REQUEST['charset'], $enc)) {
			$this->data['contents'] = S::convert2UTF8($this->data['contents'], $_REQUEST['charset']);
		}
?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title><?php echo \S::safeHTML(\S::forHTML($fileName));?></title>
		<link href="<?php echo $this->url;?>/markdown.css" rel="stylesheet" />
	</head>
	<body class="markdown-body">
<?php echo \FileRun\Utils\Markup\Markdown::toHTML($this->data['contents']);?>
	</body>
	</html>
<?php
	}
}