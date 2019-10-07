<?php
namespace FileRun\Files;
use \FileRun\Utils\DP as DP;

class HandlersManager {

	static $table = 'df_file_handlers';
	static $map = [
		'txt' => 'Plain text files',
		'office' => 'Microsoft Office documents',
		'ooffice' => 'OpenOffice documents',
		'cad' => 'AutoCAD projects',
		'3d' => '3D model files',
		'mp3' => 'Web-playable audio files',
		'audio' => 'Audio files',
		'wvideo' => 'Web-playable video files',
		'video' => 'Video files',
		'arch' => 'Archive files',
		'img' => 'Image files',
		'img2' => 'Various image files',
		'raw' => 'Raw image files',
		'noext' => 'Files without extension'
	];

	static function getTable() {
		return DP::factory(self::$table);
	}

	static function getForFileTypeInfo($fileTypeInfo) {
		$d = self::getTable();
		$byExt = $d->selectOne('*', array('ext', '=', $d->q($fileTypeInfo['extension'])));
		if ($byExt) {return $byExt['handler'];}
		if ($fileTypeInfo['type']) {
			$byType = $d->selectOne('*', array('type', '=', $d->q($fileTypeInfo['type'])));
			if ($byType) {
				return $byType['handler'];
			}
		}
		return false;
	}

	static function nicerFileType($type) {
		return self::$map[$type];
	}
}