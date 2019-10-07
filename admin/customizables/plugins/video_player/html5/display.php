<?php
$URL = $config['url']['root'].'/?module=custom_actions&action=video_player&method=stream&path='.\S::forURL($data['relativePath']);
?>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo \S::safeHTML(\S::forHTML($fileName));?></title>
	<style>
		body {
			border: 0;
			margin: 0;
			padding: 0;
			overflow: hidden;
		}
		video {
			width: 100%;
            height: 100%;
		}
	</style>
</head>
<body>
<video controls="controls" autoplay="autoplay" preload="auto" src="<?php echo $URL?>" <?php
$mime = \FM::mime_type($fileName);
if ($mime) {
	echo 'type="'.$mime.'"';
}
?>></video>
</body>
</html>