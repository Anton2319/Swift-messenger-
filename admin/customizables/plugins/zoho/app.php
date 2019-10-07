<?php
use \FileRun\WebLinks;

class custom_zoho extends \FileRun\Files\Plugin {

	var $online = true;
	static $localeSection = "Custom Actions: Zoho";

	var $writerExtensions = ["doc", "docx", "html", "rtf", "txt", "odt", "sxw"];
	var $sheetExtensions = ["xls", "xlsx", "sxc", "csv", "ods", "tsv"];
	var $showExtensions = ["ppt", "pptx", "pps", "odp", "sxi", "ppsx"];

	var $writerURL = [
		"https://writer.zoho.com/writer/remotedoc.im",
		'https://writer.zoho.com/v1/officeapi/document/preview'
	];
	var $sheetURL = "https://sheet.zoho.com/sheet/remotedoc.im";
	var $showURL = "https://show.zoho.com/show/remotedoc.im";

	function init() {
		$this->settings = [
			[
				'key' => 'APIKey',
				'title' => self::t('API key'),
				'comment' => self::t('Get it from %1', ['<a href="https://zapi.zoho.com" target="_blank">https://zapi.zoho.com</a>'])
			],
			[
				'key' => 'api_hostname',
				'title' => self::t('Zoho API domain'),
				'comment' => self::t('Either <b>zoho.com</b> or <b>zoho.eu</b>')
			]
		];
		$this->JSconfig = [
			"title" => self::t("Zoho Editor"),
			'icon' => 'images/icons/zoho.png',
			"extensions" => array_merge($this->writerExtensions, $this->sheetExtensions, $this->showExtensions),
			"popup" => true,
			"requires" => ["download"],
			"requiredUserPerms" => ["download"],
			"createNew" => [
				"title" => self::t("Document with Zoho"),
				"options" => [
					[
						"fileName" => self::t("New Document.odt"),
						"title" => self::t("Word Document"),
						"icon" => 'images/icons/zwriter.png'
					],
					[
						"fileName" => self::t("New Spreadsheet.ods"),
						"title" => self::t("Spreadsheet"),
						"icon" => 'images/icons/zsheet.png'
					],
					[
						"fileName" => self::t("New Presentation.odp"),
						"title" =>  self::t("Presentation"),
						"icon" => 'images/icons/zshow.png'
					]
				]
			]
		];
	}

	function isDisabled() {
		return (strlen(self::getSetting('APIKey')) == 0);
	}

	function run() {
		global $auth;
		$data = $this->prepareRead(['expect' => 'file']);
		$fileName = $data['alias'] ?: \FM::basename($data['fullPath']);
		$weblinkInfo = WebLinks::createForService($data['fullPath'], 2, $data['shareInfo']['id']);
		if (!$weblinkInfo) {
			self::outputError('Failed to setup weblink', 'html');
		}

		$mode = 'view';
		$saveURL = false;
		if (\FileRun\Perms::check('upload')) {
			if ((!$data['shareInfo'] || ($data['shareInfo'] && $data['shareInfo']['perms_upload']))) {
				$saveURL = WebLinks::getSaveURL($weblinkInfo['id_rnd'], false, "zoho");
				$mode = 'edit';
			}
		}

		$extension = \FM::getExtension($fileName);
		$isWriterViewMode = false;
		if (in_array($extension, $this->writerExtensions)) {
			if ($mode == 'edit') {
				$url = $this->writerURL[0];
			} else {
				$url = $this->writerURL[1];
				$isWriterViewMode = true;
			}
		} else if (in_array($extension, $this->showExtensions)) {
			$url = $this->showURL;
		} else {
			$url = $this->sheetURL;
		}
		$customAPIHostname = self::getSetting('api_hostname');
		if ($customAPIHostname && $customAPIHostname != 'zoho.com') {
			$url = str_replace('zoho.com', $customAPIHostname, $url);
		}

		$author = \FileRun\Users::formatFullName($auth->currentUserInfo);

		$filePointer = $this->readFile([
			'returnFilePointer' => true,
			'logging' => ['details' => ['method' => 'Zoho']]
		]);

		$post = [
			['name' => 'apikey', 'contents' => self::getSetting('APIKey')]
		];

		if ($isWriterViewMode) {
			$post[] = ['name' => 'document', 'contents' => $filePointer];
		} else {
			$post[] = ['name' => 'username', 'contents' => $author];
			$post[] = ['name' => 'output', 'contents' => 'url'];
			$post[] = ['name' => 'mode', 'contents' => 'collabedit'];
			$post[] = ['name' => 'filename', 'contents' => $fileName];
			$post[] = ['name' => 'format', 'contents' => $extension];
			if ($saveURL) {
				$post[] = ['name' => 'saveurl', 'contents' => $saveURL];
			}
			$post[] = ['name' => 'content', 'contents' => $filePointer];
		}


		$d = \FileRun\MetaFields::getTable();
		$docIdMetaFieldId = $d->selectOneCol('id', [['`system`', '=', 1], ['name', '=', $d->q('zoho_collab')]]);
		$metaFileId = FileRun\MetaFiles::getId($data['fullPath']);
		if ($metaFileId) {
			$zohoDocId = \FileRun\MetaValues::get($metaFileId, $docIdMetaFieldId);
			if ($zohoDocId) {
				$post[] = ['name' => 'documentid', 'contents' => $zohoDocId];
				$post[] = ['name' => 'id', 'contents' => $zohoDocId];
			}
		} else {
			$post[] = ['name' => 'id', 'contents' => uniqid(rand())];
		}

		$http = new \GuzzleHttp\Client();
		try {
			$response = $http->request('POST', $url, [
				'headers' => ['User-Agent' => ''],
				'multipart' => $post
			]);
		} catch (\GuzzleHttp\Exception\ConnectException $e) {
			jsonFeedback(false, 'Error uploading file: Network connection error: '.$e->getMessage());
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			echo 'Error uploading file: Server error: '.$e->getResponse()->getStatusCode();
			echo $e->getMessage();
			exit();
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			echo 'Error uploading file: Server error: '.$e->getResponse()->getStatusCode();
			echo $e->getMessage();
			exit();
		} catch (RuntimeException $e) {
			echo 'Error: '.$e->getMessage();
			exit();
		}
		$rs = $response->getBody()->getContents();
		if (!$rs) {
			jsonFeedback(false, 'Error uploading file: empty server response!');
		}
		if ($isWriterViewMode) {
			$rs = json_decode($rs, true);
			if ($rs['preview_url']) {
				header("Location: " . $rs['preview_url'] . "");
				exit();
			}
		} else {
			$rs = $this->parseZohoReply($rs);

			if ($rs['RESULT'] != "FALSE") {
				//save document id for collaboration
				if ($rs['DOCUMENTID']) {
					\FileRun\MetaValues::setByPath($data['fullPath'], $docIdMetaFieldId, $rs['DOCUMENTID']);
				}
				header("Location: " . $rs['URL'] . "");
				exit();
			} else {
				echo "<strong>Zoho:</strong>";
				echo "<div style=\"margin:5px;border:1px solid silver;padding:5px;overflow:auto;\"><pre>";
				echo $response;
				if (strstr($rs['warning'], "unable to import content")) {
					echo "\r\n\r\nZoho.com service does not support this type of documents or was not able to access this web server.";
				} else {
					echo $response;
				}
				echo "</pre></div>";
			}
		}
	}

	function createBlankFile() {
		$this->writeFile([
			'source' => 'string',
			'contents' => '',
			'logging' => ['details' => ['method' => 'Zoho Editor']]
		]);
		jsonFeedback(true, 'Blank file created successfully');
	}

	function saveRemoteChanges() {
		$uploadTempPath = $_FILES['content']['tmp_name'];
		if (!$uploadTempPath) {
			self::outputError('Missing upload file', 'text');
		}
		$this->writeFile([
			'source' => 'move',
			'moveFullPath' => $uploadTempPath,
			'logging' => ['details' => ['method' => 'Zoho Editor']]
		]);
		echo 'File successfully saved';
	}

	private function parseZohoReply($reply) {
		$lines = explode("\n", $reply);
		$rs = array();
		foreach ($lines as $line) {
			$line = trim($line);
			$p = strpos($line, "=");
			$key = substr($line, 0, $p);
			$val = substr($line, $p+1);
			if (strlen($key) > 0) {
				$rs[$key] = $val;
			}
		}
		return $rs;
	}
}