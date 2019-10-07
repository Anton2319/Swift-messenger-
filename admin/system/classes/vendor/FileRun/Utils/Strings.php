<?php

class S {

	static function safeForHtml($str) {
		return self::safeHTML(self::forHTML($str));
	}
	static function forHTML($str, $detect = false) {
		$from = 'UTF-8';
		if ($detect) {$from = self::detectEncoding($str);}
		return self::convertEncoding($str, $from, 'UTF-8');
	}
	static function fromHTML($str, $urlDecode = false) {
		if ($urlDecode) {
			$str = self::URLDecode($str);
		}
		return $str;
	}
	static function convertEncoding($str, $from, $to) {
		if ($from === $to) {return $str;}
		if ($to === '[transliterate]') {$to = 'ASCII';}
		return mb_convert_encoding($str, $to, $from);
	}
	static function convert2UTF8($str, $from = '') {
		if (!$from) {$from = self::detectEncoding($str);}
		return self::convertEncoding($str, $from, 'UTF-8');
	}
	static function detectEncoding($str, $detectList = '') {
		if (!$detectList) {
			global $config;
			if ($config['app']['encoding']['detect']) {
				$detectList = $config['app']['encoding']['detect'];
			} else {
				$detectList = 'UTF-8, ISO-8859-1, ASCII';
			}
		}
		return mb_detect_encoding($str, $detectList);
	}
	static function URLEncode($str) {
		return str_replace(['+', ' '], ['%2B', '%20'], rawurlencode($str));
	}
	static function URLDecode($str) {
		return str_replace(['%2B', '%20'], ['+', ' '], rawurldecode($str));
	}
	static function forURL($str) {
		return htmlspecialchars(self::URLEncode($str), ENT_QUOTES);
	}
	static function safeJS($str) {
		return addslashes(str_replace(["\n", "\r"],'', $str));
	}
	static function safeHTML($str) {
		return htmlspecialchars($str, ENT_QUOTES);
	}
	static function safeHTMLFileName($str) {
		return str_replace(['<', '>'], '', $str);
	}
	static function safeXML($str) {
	    return strtr(
	        $str, [
	            "<" => "&lt;",
	            ">" => "&gt;",
	            '"' => "&quot;",
	            "'" => "&apos;",
	            "&" => "&amp;"
	        ]
	    );
	}
	static function okUsername($string) {
		$alphaNum = ['q','w','e','r','t','y','u','i','o','p','l','k','j','h',
			'g','f','d','s','a','z','x','c','v','b','n','m','Q','W',
			'E','R','T','Y','U','I','O','P','L','K','J','H','G','F',
			'D','S','A','Z','X','C','V','B','N','M','0','1','2','3',
			'4','5','6','7','8','9','_','-','@','.'];
		$len = strlen($string);
		for ($i = 0; $i < $len; $i++) {
			if (!in_array($string[$i], $alphaNum)) {
				return false;
			}
		}
		return true;
	}

	static function stripHTMLChars($string) {
		$string = str_replace(['<', '>', '\'', '"', '&', ';', '`', "\r", "\n", '\\', '/', '%'], '', $string);
		return $string;
	}
}