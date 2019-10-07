<?php
namespace FileRun\Thumbs\Extractors;

class stl extends extractor {
	static function extract($src) {
		global $settings;
		self::debug("Generating thumb for STL file.");

		$target = self::getTargetPath($src);

		$cmd = $settings->thumbnails_stl_path." -f PNG ";
		$cmd .= "\"".$src."\" ";
		$cmd .= "\"".$target."\"";

		if (\FM::getOS() == 'win') {
			$cmd .= "  && exit";
		} else {
			$cmd .= " 2>&1";
		}
		$return_text = [];
		$return_code = 0;
		self::debug("Running: ".$cmd);
		exec($cmd, $return_text, $return_code);
		if ($return_code != 0) {
			if (self::$debug) {
				echo "Results:<br>";
				echo " * returned code: ".$return_code."<br>";
				echo " * returned text: ";
				print_r($return_text);
				flush();
			}
			return false;
		}
		return $target;
	}
}