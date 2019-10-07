<?php
namespace FileRun\Thumbs;
use \FileRun\Media\Image\Format\JPEG;

class Make {

	static function needsProcessing(array $fileData, array $opts) {
		global $config;
		if (!Utils::isWebSafe($fileData['fileNameExtension'])) {return true;}
		Utils::dbg('File is safe for web.');
		$maxFileSize = $config['thumbs']['output_small_max_filesize'] ?: 8388608;
		if ($fileData['fileSize'] > $maxFileSize) {
			Utils::dbg('File is larger than '.\FM::formatFileSize($maxFileSize));
			return true;
		}
		$maxFileSizeNoDimensionCheck = $config['thumbs']['output_small_max_filesize_without_size_check'] ?: 2097152;
		if ($fileData['fileSize'] > $maxFileSizeNoDimensionCheck) {
			if (in_array($fileData['fileNameExtension'], ['jpg', 'jpeg'])) {//JPEG file
				Utils::dbg('JPEG image is larger than '.\FM::formatFileSize($maxFileSizeNoDimensionCheck));
				//check image dimensions
				$dimension = JPEG\Size::get($fileData['fullPath']);
				if ($dimension) {
					Utils::dbg('Image width is ' . $dimension['width']);
					Utils::dbg('Image height is ' . $dimension['height']);
					Utils::dbg('Thumb max width is ' . $opts['width']);
					Utils::dbg('Thumb max height is ' . $opts['height']);

					if ($dimension['width'] <= $opts['width'] && $dimension['height'] <= $opts['height']) {
						Utils::dbg('Original image does not need resizing.');
						return false;
					}
					if ($dimension['width'] > $opts['width'] * 3 || $dimension['height'] < $opts['height'] * 3) {
						Utils::dbg('Original image is too large to send out.');
						return true;

						/*
						if (function_exists('exif_read_data')) {
							$exif = @exif_read_data($sourceData['fullPath'], 'EXIF');
							if ($exif && $exif['Orientation'] && $exif['Orientation'] != 1) {//needs reorientation
								$sendFull = false;
							}
						}
						*/
					}
				}
			} else {
				Utils::dbg('File is smaller than 2MB');
			}
		}
		return false;
	}

	static function make($fileData, $opts, $output = false) {
		global $settings, $config;
		Utils::dbg("Processing: ".$fileData['fullPath']);
		Utils::dbg("As: ".$opts['forceExt']);
		Utils::dbg("File size: ".$fileData['fileSize']);
		if ($fileData['fileSize'] == 0) {
			Utils::dbg('File is empty');
			return false;
		}
		if (!self::needsProcessing($fileData, $opts)) {
			if ($output) {
				Cache::outputCache($fileData);
			}
			return true;
		}
		$thumbCachePath = Cache::getThumbPath($fileData, $opts);
		$lockFilePath = $thumbCachePath.'.lock';
		if (is_file($lockFilePath)) {
			$lockFileDate = filectime($lockFilePath);
			if (!$opts['caching'] || (time() > $lockFileDate+300)) {
				Utils::dbg('Removing expired lock: '.$lockFilePath);
				@unlink($lockFilePath);
			} else {
				Utils::dbg('Lock file in place: '.$lockFilePath);
				Utils::dbg('Lock date: '.date('r', $lockFileDate));
				return false;
			}
		}
		Utils::dbg("Creating lock: ".$lockFilePath);
		\FM::newFile($lockFilePath);
		$resizeFileData = $fileData;

		$handler = Utils::extCanHaveThumb($opts['forceExt']);
		$extractedFileData = false;
		if ($handler && Utils::handlerIsExtractor($handler)) {
			$extractedFileData = Extract::extract($fileData, $handler);
			if (!$extractedFileData) {
				return false;
			}
			if (!self::needsProcessing($extractedFileData, $opts)) {
				Utils::dbg("Moving extracted to cache.");
				rename($extractedFileData['fullPath'], $thumbCachePath);
				if ($output) {
					Cache::outputCache($fileData, $thumbCachePath);
				}
				if (@is_file($lockFilePath)) {
					Utils::dbg("Removing lock: ".$lockFilePath);
					@unlink($lockFilePath);
				}
				return true;
			}
			$resizeFileData = [
				'fullPath' => $extractedFileData['fullPath'],
				'fileSize' => $extractedFileData['fileSize']
			];
		}

		$fileSizeLimit = $config['thumbs']['limit_file_size'];
		if ($fileSizeLimit) {
			if ($resizeFileData['fileSize'] > $fileSizeLimit) {
				Utils::dbg('File size is larger than configured limit '.\FM::formatFileSize($fileSizeLimit));
				return false;
			}
		}
		$rs = Resize::resize($resizeFileData['fullPath'], $thumbCachePath, $opts);
		if (!$rs) {
			Utils::dbg('Resize failed.');
			return false;
		}

		Utils::dbg('Resized image saved: '.$thumbCachePath);
		if ($extractedFileData) {
			Utils::dbg('Removing extracted: '.$extractedFileData['fullPath']);
			@unlink($extractedFileData['fullPath']);
		}
		if (@is_file($lockFilePath)) {
			Utils::dbg('Removing lock: '.$lockFilePath);
			@unlink($lockFilePath);
		}
		if ($settings->thumbnails_pngquant) {
			Optimize::optimizePNG($thumbCachePath);
		}

		if ($output) {
			if (!Utils::$debug) {
				Cache::outputCache($fileData, $thumbCachePath);
			}
			if (!$opts['caching']) {
				Utils::dbg('Caching is off. Removing resized image.');
				@unlink($thumbCachePath);
			}
		}
		if (Utils::$debug) {
			if (function_exists('xdebug_time_index')) {
				echo 'Time: '.round(xdebug_time_index(), 4).' seconds';
			}
		}
		return $thumbCachePath;
	}

}