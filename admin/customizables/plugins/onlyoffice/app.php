<?php
use \FileRun\WebLinks;

class custom_onlyoffice extends \FileRun\Files\Plugin {

	var $online = true;
	static $localeSection = 'Custom Actions: ONLYOFFICE';

	var $outputName = 'content';
	var $canEditTypes = [
		'doc', 'docx', 'odt', 'rtf', 'txt',
		'xls', 'xlsx', 'ods', 'csv',
		'ppt', 'pptx', 'odp'
	];

	function init() {
		$this->settings = [
			[
				'key' => 'serverURL',
				'title' => self::t('ONLYOFFICE server URL'),
				'comment' => self::t('Download and install %1', ['<a href="https://github.com/ONLYOFFICE/DocumentServer" target="_blank">ONLYOFFICE DocumentServer</a>'])
			]
		];
		$this->JSconfig = [
			"title" => self::t("ONLYOFFICE"),
			"popup" => true,
			'icon' => 'images/icons/onlyoffice.png',
			"loadingMsg" => self::t('Loading document in ONLYOFFICE. Please wait...'),
			'useWith' => ['office'],
			"requires" => ["download"],
			"requiredUserPerms" => ["download"],
			"createNew" => [
				"title" => self::t("Document with ONLYOFFICE"),
				"options" => [
					[
						"fileName" => self::t("New Document.docx"),
						"title" => self::t("Word Document"),
						"iconCls" => 'fa fa-fw fa-file-word-o'
					],
					[
						"fileName" => self::t("New Spreadsheet.xlsx"),
						"title" => self::t("Spreadsheet"),
						"iconCls" => 'fa fa-fw fa-file-excel-o'
					],
					[
						"fileName" => self::t("New Presentation.pptx"),
						"title" =>  self::t("Presentation"),
						"iconCls" => 'fa fa-fw fa-file-powerpoint-o'
					]
				]
			]
		];
	}

	function isDisabled() {
		return (strlen(self::getSetting('serverURL')) == 0);
	}

	function run() {
		$data = $this->prepareRead(['expect' => 'file']);
		$weblinkInfo = WebLinks::createForService($data['fullPath'], false, $data['shareInfo']['id']);
		if (!$weblinkInfo) {
			self::outputError('Failed to setup weblink', 'html');
		}
		$url = WebLinks::getURL(['id_rnd' => $weblinkInfo['id_rnd'], 'download' => 1]);

		$extension = \FM::getExtension($data['fullPath']);

		$saveURL = false;
		$mode = 'view';
		if (\FileRun\Perms::check('upload')) {
			if ((!$data['shareInfo'] || ($data['shareInfo'] && $data['shareInfo']['perms_upload']))) {
				$saveURL = WebLinks::getSaveURL($weblinkInfo['id_rnd'], false, "onlyoffice");
				if (in_array($extension, $this->canEditTypes)) {
					$mode = 'edit';
				}
			}
		}

		if (in_array($extension, ['docx', 'doc','odt','txt','rtf','html','htm','mht','epub','pdf','djvu','xps'])) {
			$docType = 'text';
		} else if (in_array($extension, ['xlsx','xls','ods','csv'])) {
			$docType = 'spreadsheet';
		} else {
			$docType = 'presentation';
		}

		global $auth;
		$author = \FileRun\Users::formatFullName($auth->currentUserInfo);

		$fileSize = \FM::getFileSize($data['fullPath']);
		$fileModifTime = filemtime($data['fullPath']);
		$documentKey = substr($fileSize.md5($data['fullPath']), 0, 12).substr($fileModifTime,2,10);

		require($this->path."/display.php");

		\FileRun\Log::add(false, "preview", [
			"relative_path" => $data['relativePath'],
			"full_path" => $data['fullPath'],
			"method" => "ONLYOFFICE"
		]);
	}

	private function saveFeedback($success, $message) {
		if ($this->POST["status"] != 2) {
			//ONLYOFFICE makes various calls to the save URL
			exit('{"error":0}');
		}
		if ($success) {
			exit('{"error":0}');
		} else {
			exit($message);
		}
	}

	function saveRemoteChanges() {
		$rs = @file_get_contents("php://input");
		if ($rs === false) {
			self::outputError(error_get_last()['message'], 'text');
		}
		if (!$rs) {
			self::outputError('Empty contents.', 'text');
		}
		$rs = json_decode($rs, true);
		if ($rs["status"] != 2) {
			echo json_encode(['error' => 0]);
			return false;
		}
		$contents = @file_get_contents($rs["url"]);
		if ($contents === false) {
			self::outputError(error_get_last()['message'], 'text');
		}
		if (!$contents) {
			self::outputError('Empty contents.', 'text');
		}
		$this->writeFile([
			'source' => 'string',
			'contents' => $contents,
			'logging' => ['details' => ['method' => 'ONLYOFFICE']]
		]);
		echo 'File successfully saved';
	}

	function createBlankFile() {
		$ext = \FM::getExtension($this->data['fileName']);
		if (!in_array($ext, $this->canEditTypes)) {
			jsonOutput([
				"rs" => false,
				"msg" => self::t('The file extension needs to be one of the following: %1', [implode(', ', $this->canEditTypes)])
			]);
		}
		$sourceFullPath = gluePath($this->path, 'blanks/blank.'.$ext);
		$this->writeFile([
			'source' => 'copy',
			'sourceFullPath' => $sourceFullPath,
			'logging' => ['details' => ['method' => 'ONLYOFFICE']]
		]);
		jsonFeedback(true, 'Blank file created successfully');
	}
}