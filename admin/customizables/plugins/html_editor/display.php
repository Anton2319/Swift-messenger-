<?php
global $settings, $config;
$translationCode = self::getTranslationCode();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo \S::safeHTML(\S::forHTML($fileName));?></title>
	<?php \FileRun\UI\CSS::insertLink();?>
	<link href="<?php echo $this->url;?>/summernote/bootstrap/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo $this->url;?>/summernote/summernote.css">
	<script src="js/min.php?extjs=1&v=<?php echo $settings->currentVersion;?><?php if ($config['misc']['developmentMode']) {echo '&debug=1';}?>"></script>
	<script src="<?php echo $this->url;?>/app.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="?module=fileman&section=utils&page=translation.js&sec=<?php echo \S::forURL("Custom Actions: HTML Editor")?>&lang=<?php echo \S::forURL(\FileRun\Lang::getCurrent())?>"></script>
	<script src="js/jquery/jquery.min.js"></script>
	<script src="<?php echo $this->url;?>/summernote/bootstrap/bootstrap.min.js"></script>
	<script src="<?php echo $this->url;?>/summernote/summernote.min.js"></script>
	<script src="<?php echo $this->url;?>/summernote/summernote-ext-print.js"></script>
	<?php if ($translationCode) { ?>
	<script src="<?php echo $this->url;?>/summernote/lang/summernote-<?php echo $translationCode;?>.js"></script>
	<?php } ?>
	<script>
		var URLRoot = '<?php echo \S::safeJS($config['url']['root'])?>';
		var path = '<?php echo \S::safeJS($this->data['relativePath'])?>';
		var filename = '<?php echo \S::safeJS($fileName)?>';
		var windowId = '<?php echo \S::safeJS(\S::fromHTML($_REQUEST['_popup_id']))?>';
		var language = <?php echo $translationCode ? '\''.\S::safeJS($translationCode).'\'' : 'false';?>;
	</script>
	<style>
		.note-editor ol {padding-left:20px;list-style-type: decimal;}
		.note-editor ul {padding-left:20px;list-style-type: disc;}
		.panel {border-radius:0;}
		.note-editor.note-frame {border-color:#D9D9D9;}
		.ext-el-mask { background-color: white; }
	</style>
</head>

<body id="theBODY" onload="FR.init()">

<textarea style="display:none" id="textContents"><?php echo S::safeHTML(S::convert2UTF8($this->data['contents']))?></textarea>

</body>
</html>