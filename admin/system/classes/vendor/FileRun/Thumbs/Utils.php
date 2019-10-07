<?php
namespace FileRun\Thumbs;

class Utils {

	static $debug;
	static function dbg($msg) {
		if (self::$debug) {echo $msg.'<br>';flush();}
	}

	static function isWebSafe($ext) {
		return in_array($ext, ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp']);
	}

	static function isTextDoc($ext) {
		$typeInfo = \FM::fileTypeInfo(false, $ext);
		return in_array($typeInfo['type'], ['txt', 'office', 'ooffice']);
	}

	static function extCanHaveThumb($ext) {
		global $settings, $config;
		if (self::isWebSafe($ext)) {
			return true;
		}
		if (in_array($ext, ['mp3', 'm4a', 'flac']) && !$config['app']['thumbnails']['disable_for_mp3']) {
			return 'mp3';
		}
		if ($ext == 'epub' && !$config['app']['thumbnails']['disable_for_epub']) {
			return 'epub';
		}
		if ($settings->thumbnails_imagemagick) {
			if (in_array($ext, explode(",", strtolower($settings->thumbnails_imagemagick_ext)))) {
				return 'imagemagick';
			}
		}
		if ($settings->thumbnails_ffmpeg) {
			if (in_array($ext, explode(",", strtolower($settings->thumbnails_ffmpeg_ext)))) {
				return 'ffmpeg';
			}
		}
		if ($settings->thumbnails_libreoffice) {
			if (in_array($ext, explode(",", strtolower($settings->thumbnails_libreoffice_ext)))) {
				return 'office';
			}
		}
		if ($settings->thumbnails_stl && $ext == 'stl') {
			return 'stl';
		}
		if (isset($config['thumbs']['extractors']) && is_array($config['thumbs']['extractors'])) {
			foreach ($config['thumbs']['extractors'] as $handlerName => $extensions) {
				if (is_array($extensions) && in_array($ext, $extensions)) {
					return $handlerName;
				}
			}
		}
		if (isset($config['thumbs']['cached_only']) && is_array($config['thumbs']['cached_only']) && in_array($ext, $config['thumbs']['cached_only'])) {
			return 'cached_only';
		}
		return false;
	}

	static function handlerIsExtractor($handler): bool {
		return in_array($handler, self::listAvailableExtractors(), true);
	}

	static function listAvailableExtractors(): array {
		global $config;
		$default = ['ffmpeg', 'epub', 'mp3', 'office', 'stl'];
		if (isset($config['thumbs']['extractors']) && is_array($config['thumbs']['extractors'])) {
			return array_merge($default, array_keys($config['thumbs']['extractors']));
		}
		return $default;
	}

}