<?php
global $settings, $config;
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo \S::safeHTML(\S::forHTML($fileName));?></title>
	<?php \FileRun\UI\CSS::insertLink();?>
	<link rel="stylesheet" href="<?php echo $this->url;?>/cropperjs/editor.css?v=<?php echo $settings->currentVersion;?>">

	<script src="js/min.php?extjs=1&v=<?php echo $settings->currentVersion;?><?php if ($config['misc']['developmentMode']) {echo '&debug=1';}?>"></script>
	<script src="<?php echo $this->url;?>/app.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="?module=fileman&section=utils&page=translation.js&sec=<?php echo \S::forURL("Custom Actions: Image Editor")?>&lang=<?php echo \S::forURL(\FileRun\Lang::getCurrent())?>"></script>
	<script src="<?php echo $this->url;?>/cropperjs/cropper.min.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="js/jquery/croppie/canvas.toBlob.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="js/min.php?flow=1&v=<?php echo $settings->currentVersion;?><?php if ($config['misc']['developmentMode']) {echo '&debug=1';}?>"></script>
	<script>
		FR.vars = <?php echo json_encode($vars);?>
	</script>
</head>

<body id="theBODY" onload="FR.init()">
</body>
</html>