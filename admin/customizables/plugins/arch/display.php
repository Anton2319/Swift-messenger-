<?php
global $config, $settings;
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title></title>
	<link rel="stylesheet" type="text/css" href="css/style.css?v=<?php echo $settings->currentVersion;?>" />
	<style>
		body {background-color:white;overflow:auto;}
		td {
			white-space: nowrap;
		}
	</style>
</head>

<body>
	<table width="100%" border="0" cellspacing="1" cellpadding="5" class="niceborder">
		<?php
		$limit = 100;
		$i = 0;
		foreach ($list as $key => $item) {
			if ($i == $limit) {
				?>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2">Archive contains <?php echo $count-$limit;?> files more that are not displayed in this preview.</td>
				</tr>
				<?php
				break;
			}
			if ($item['checksum'] != "00000000") {
				if ($item['utf8_encoded']) {
					$srcEnc = "UTF-8";
				} else {
					if ($config['app']['encoding']['unzip']) {//convert from a predefined encoding
						$srcEnc = $config['app']['encoding']['unzip'];
					} else {
						$srcEnc = S::detectEncoding($item['path']);
					}
				}

				$item['path'] = \S::convert2UTF8($item['path'], $srcEnc);
				if ($item['type'] == "file" && $item['path']) {
				?>
				<tr>
					<td width="16"><img src="<?php echo \FM::getFileIconURL($item['filename']);?>" border="0" height="16" width="16" /></td>
					<td><div><?php echo S::safeHTML($item['path']);?></div></td>
					<td align="center"><?php echo \FM::formatFileSize($item['filesize']);?></td>
				</tr>
				<?php
				$i++;
				}
			}
		}
		?>
	</table>
</body>
</html>