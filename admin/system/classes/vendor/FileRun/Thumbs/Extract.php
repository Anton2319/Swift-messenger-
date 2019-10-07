<?php
namespace FileRun\Thumbs;


class Extract {

	static function extract(array $fileData, $handler) {
		$className = '\FileRun\Thumbs\Extractors\\'.$handler;
		Utils::dbg('Extractor: '.$className);
		call_user_func($className.'::setDebug', Utils::$debug);
		$extractedFilePath = call_user_func($className.'::extract', $fileData['fullPath']);
		if (!$extractedFilePath) {
			return false;
		}
		if (!is_file($extractedFilePath)) {
			Utils::dbg('No file found at '.$extractedFilePath);
			return false;
		}
		Utils::dbg("Extracted to: ".$extractedFilePath);
		$resizeFileSize = \FM::getFileSize($extractedFilePath);
		if ($resizeFileSize == 0) {
			Utils::dbg("Extracted file is empty.");
			return false;
		}
		Utils::dbg("Extracted file size: ".$resizeFileSize);
		return [
			'fullPath' => $extractedFilePath,
			'fileNameExtension' => \FM::getExtension($extractedFilePath),
			'fileSize' => $resizeFileSize
		];
	}

}