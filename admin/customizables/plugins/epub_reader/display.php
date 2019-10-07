<?php
global $config, $settings;
$url = $config['url']['root']."/?module=custom_actions&action=epub_reader&method=stream&path=".\S::forURL($this->data['relativePath']);
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<title></title>
	<link rel="stylesheet" href="css/normalize.css">
	<link rel="stylesheet" href="<?php echo $this->relativeURL;?>/css/main.css">
	<link rel="stylesheet" href="<?php echo $this->relativeURL;?>/css/popup.css">

	<script src="js/jquery/jquery.min.js"></script>
	<script src="<?php echo $this->relativeURL;?>/js/libs/zip.min.js"></script>
	<script src="<?php echo $this->relativeURL;?>/js/libs/screenfull.min.js"></script>
	<script src="<?php echo $this->relativeURL;?>/js/epub.min.js"></script>
	<script src="<?php echo $this->relativeURL;?>/js/reader.min.js"></script>
	<script>
		"use strict";
		document.onreadystatechange = function () {
			if (document.readyState == "complete") {
				window.reader = ePubReader('<?php echo \S::safeJS($url);?>', {
					bookKey: 'EPUB-<?php echo md5($this->data['filePath']);?>',
					contained:true,
					restore: true,
					storage: true
				});
			}
		};
	</script>
</head>
<body>
<div id="sidebar">
	<div id="tocView" class="view">
	</div>
</div>
<div id="main">
	<div id="titlebar">
		<div id="opener">
			<a id="slider" title="<?php echo self::t('Menu');?>" class="icon-menu"><?php echo self::t('Menu');?></a>
		</div>
		<div id="metainfo">
			<span id="book-title"></span>
			<span id="title-seperator">&nbsp;&nbsp;–&nbsp;&nbsp;</span>
			<span id="chapter-title"></span>
		</div>
		<div id="title-controls">
			<a id="fullscreen" title="<?php echo self::t('Fullscreen');?>" class="icon-resize-full"><?php echo self::t('Fullscreen');?></a>
		</div>
	</div>
	<div id="divider"></div>
	<div id="prev" class="arrow">‹</div>
	<div id="viewer"></div>
	<div id="next" class="arrow">›</div>
	<div id="loader"><img src="<?php echo $this->url;?>/img/loader.gif"></div>
</div>
<div class="overlay"></div>
</body>
</html>