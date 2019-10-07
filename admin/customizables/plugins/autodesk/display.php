<?php
global $app, $settings, $config;
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<title></title>
	<?php \FileRun\UI\CSS::insertLink();?>
	<link rel="stylesheet" href="<?php echo $this->apiURL;?>/viewers/style.min.css" type="text/css">
	<script src="<?php echo $this->apiURL;?>/viewers/three.min.js"></script>
	<script src="<?php echo $this->apiURL;?>/viewers/viewer3D.min.js"></script>
	<script src="js/min.php?extjs=1&v=<?php echo $settings->currentVersion;?>"></script>
	<script src="<?php echo $this->url;?>/app.js?v=<?php echo $settings->currentVersion;?>"></script>
	<script src="?module=fileman&section=utils&page=translation.js&sec=<?php echo S::forURL(self::$localeSection)?>&lang=<?php echo S::forURL(\FileRun\Lang::getCurrent())?>"></script>
	<script>
		var URLRoot = '<?php echo S::safeJS($config['url']['root'])?>';
		var path = '<?php echo S::safeJS($this->data['relativePath'])?>';
		var filename = '<?php echo S::safeJS($this->data['fileName'])?>';
		var windowId = '<?php echo S::safeJS(S::fromHTML($_REQUEST['_popup_id']))?>';
	</script>
</head>

<body id="theBODY">
</body>
</html>