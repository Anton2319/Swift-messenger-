<?php

class custom_audio_player extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: Audio Player";
	static $publicMethods = ['stream'];

	function init() {
		$this->JSconfig = [
			"title" => self::t("Audio Player"),
			'iconCls' => 'fa fa-fw fa-music',
			'useWith' => ['nothing'],
			"requires" => ["download"]
		];
	}
	function run() {
		require($this->path."/display.php");
	}

	function stream() {
		/*ffmpeg -i input.wav -acodec libmp3lame -b:a 128k output.mp3*/
		$this->downloadFile([
			'openInBrowser' => true,
			'logging' => ['details' => ['method' => 'Audio Player']]
		]);
	}
}