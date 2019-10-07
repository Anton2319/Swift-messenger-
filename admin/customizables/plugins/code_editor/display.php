<?php
global $settings, $config;

$enc = mb_list_encodings();
if ($_REQUEST['charset'] && in_array($_REQUEST['charset'], $enc)) {
	$this->data['contents'] = \S::convert2UTF8($this->data['contents'], $_REQUEST['charset']);
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title><?php echo \S::safeHTML(\S::forHTML($fileName));?></title>
	<?php \FileRun\UI\CSS::insertLink();?>
	<script src="js/min.php?extjs=1&v=<?php echo $settings->currentVersion;?><?php if ($config['misc']['developmentMode']) {echo '&debug=1';}?>"></script>
	<script src="<?php echo $this->url;?>/app.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="?module=fileman&section=utils&page=translation.js&sec=<?php echo \S::forURL("Custom Actions: Text Editor")?>&lang=<?php echo \S::forURL(\FileRun\Lang::getCurrent())?>"></script>
	<script src="<?php echo $this->url;?>/ace/ace.js"></script>
	<script src="<?php echo $this->url;?>/ace/ext-modelist.js"></script>
	<script>
		var URLRoot = '<?php echo \S::safeJS($config['url']['root'])?>';
		var path = '<?php echo \S::safeJS($this->data['relativePath'])?>';
		var filename = '<?php echo \S::safeJS($fileName)?>';
		var windowId = '<?php echo \S::safeJS(\S::fromHTML($_REQUEST['_popup_id']))?>';
		var charset = '<?php echo \S::safeJS(\S::fromHTML($_REQUEST['charset']))?>';
		var theme = '<?php echo \S::safeJS($settings->ui_theme)?>';
		var charsets = <?php
		$list = [];
		foreach($enc as $e) {
			$list[] = [$e];
		}
		echo json_encode($list);
		?>
	</script>
</head>

<body id="theBODY" onload="FR.init()">

<textarea style="display:none" id="textContents"><?php echo S::safeHTML(S::convert2UTF8($this->data['contents']))?></textarea>

</body>
</html>