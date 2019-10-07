<?php

class custom_video_player extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions: Video Player';
	static $publicMethods = ['stream'];

	function init() {
		$this->JSconfig = [
			'title' => self::t('Video Player'),
			'iconCls' => 'fa fa-fw fa-play-circle-o',
			'useWith' => ['wvideo'],
			'popup' => true,
			'requiredUserPerms' => ['download'],
			'requires' => ['download']
		];
	}

	function run() {
		global $config;
		$data = $this->prepareRead([
			'expect' => 'file',
			'errorHandling' => 'html'
		]);
		$fileName = $data['alias'] ?: \FM::basename($data['fullPath']);
		$ext = \FM::getExtension($fileName);
		$handlers = [
			'm4v' => 'html5',
			'mpg' => 'mpg',
			'wmv' => 'wmv',
			'mov' => 'html5',
			'ogv' => 'html5',
			'mp4' => 'html5',
			'mkv' => 'html5',
			'webm' => 'html5'
		];
		$handle = $handlers[$ext];
		if (!$handle) {
			self::outputError('The file type is not supported by this player.', 'html');
		}
		require gluePath($this->path, $handle, 'display.php');
	}

	function stream() {
		$this->downloadFile([
			'openInBrowser' => true,
			'logging' => ['details' => ['method' => 'Video Player']]
		]);
	}
}