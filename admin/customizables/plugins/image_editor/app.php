<?php

class custom_image_editor extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions: Image Editor';

	function init() {

		$this->JSconfig = [
			'title' => self::t('Image Editor'),
			'iconCls' => 'fa fa-fw fa-crop-alt icon-blue',
			'useWith' => ['img', 'img2', 'raw'],
			'popup' => true,
			'requiredUserPerms' => ['download', 'upload'],
			'requires' => ['download', 'alter']
		];
	}

	function run() {
		global $config;
		$data = $this->prepareRead(['expect' => 'file', 'errorHandling' => 'html']);
		$fileName = $data['alias'] ?: \FM::basename($data['fullPath']);

		$typeInfo = \FM::fileTypeInfo($fileName);
		if ($typeInfo['type'] == 'img') {
			$url = $config['url']['root'].'/?module=custom_actions&action=open_in_browser&path='.\S::forURL($this->data['relativePath']).'&noCache='.time();
		} else {
			if (!\FileRun\Thumbs\Utils::extCanHaveThumb($typeInfo['extension'])) {
				exit('Cannot convert file type to image.');
			}
			$url = $config['url']['root'].'/t.php?width=4000&height=4000&noIcon=true&p='.\S::forURL($this->data['relativePath']).'&noCache='.time();
		}
		if ($this->data['version']) {
			$url .= '&version='.\S::forURL($this->data['version']);
		}

		if ($config['app']['upload']['chunk_size']) {
			$chunkSize = $config['app']['upload']['chunk_size'];
		} else {
			$chunkSize = \FileRun\Files\Utils::getUploadChunkSize();
		}

		if (in_array($typeInfo['extension'], ['png', 'svg', 'gif', 'bmp'])) {
			$saveExtension = 'png';
			$saveMimeType = 'image/png';
		} else {
			$saveExtension = $typeInfo['extension'] == 'jpeg' ? 'jpeg' : 'jpg';
			$saveMimeType = 'image/jpeg';
		}

		$vars = [
			'URLRoot' => $config['url']['root'],
			'originalFileName' => $fileName,
			'saveFileName' => \FM::replaceExtension($fileName, $saveExtension),
			'saveMimeType' => $saveMimeType,
			'folderPath' => \FM::dirname($this->data['relativePath']),
			'imageURL' => $url,
			'windowId' => $_REQUEST['_popup_id'],
			'UploadChunkSize' => $chunkSize
		];

		require($this->path."/display.php");
	}
}