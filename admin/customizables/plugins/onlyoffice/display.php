<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
	<style>
		body {
			border: 0;
			margin: 0;
			padding: 0;
			overflow:hidden;
		}
	</style>
</head>

<body>
<div id="placeholder"></div>
<script type="text/javascript" src="<?php echo gluePath(self::getSetting('serverURL'), '/web-apps/apps/api/documents/api.js');?>"></script>
<script>
	var innerAlert = function (message) {
		if (console && console.log)
			console.log(message);
	};

	var onReady = function () {
		innerAlert("Document editor ready");
	};

	var onDocumentStateChange = function (event) {
		var title = document.title.replace(/\*$/g, "");
		document.title = title + (event.data ? "*" : "");
	};

	var onError = function (event) {
		if (event) innerAlert(event.data);
	};

	var docEditor = new DocsAPI.DocEditor("placeholder", {
		"documentType": "<?php echo $docType;?>",
		"type": "desktop",
		"document": {
			"fileType": "<?php echo $extension;?>",
			"key": "<?php echo $documentKey;?>",
			"title": "<?php echo \S::safeJS($this->data['fileName']);?>",
			"url": "<?php echo \S::safeJS($url);?>",
			"info": {
				"author": "<?php echo \S::safeJS($author);?>"
			}
		},
		"editorConfig": {
			"mode": '<?php echo $mode;?>',
			"lang": '<?php echo \FileRun\UI\TranslationUtils::getShortName(\FileRun\Lang::getCurrent());?>',
			<?php if ($saveURL) { ?>
			"callbackUrl": "<?php echo \S::safeJS($saveURL);?>",
			<?php } ?>
			"user": {
				"firstname": "<?php echo \S::safeJS($auth->currentUserInfo['name']);?>",
				"id": "<?php echo \S::safeJS($auth->currentUserInfo['id']);?>",
				"lastname": "<?php echo \S::safeJS($auth->currentUserInfo['name2']);?>"
			}
		},
		"customization": {
			'about': false,
			'comments': false,
			'feedback': false,
			'goback': false
		},
		"events": {
			'onReady': onReady,
			'onDocumentStateChange': onDocumentStateChange,
			'onError': onError
		},
		"height":"100%",
		"width":"100%",
		"token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkb2N1bWVudCI6eyJmaWxlVHlwZSI6ImRvY3giLCJrZXkiOiJhcGl3aDkxOWZiYWYwLWIzYTEtNGJmMi05OGMxLTcyYTEzZmJhZTkyOSIsInRpdGxlIjoiRXhhbXBsZSBEb2N1bWVudCBUaXRsZS5kb2N4IiwidXJsIjoiaHR0cHM6Ly9kMm5sY3RuMTJ2Mjc5bS5jbG91ZGZyb250Lm5ldC9hc3NldHMvZG9jcy9zYW1wbGVzL2RlbW8uZG9jeCJ9LCJkb2N1bWVudFR5cGUiOiJ0ZXh0IiwiZWRpdG9yQ29uZmlnIjp7ImNhbGxiYWNrVXJsIjoiaHR0cHM6Ly9hcGkub25seW9mZmljZS5jb20vZWRpdG9ycy9jYWxsYmFjayJ9LCJoZWlnaHQiOiIxMDAlIiwid2lkdGgiOiIxMDAlIn0.dCQa5Y-BBavzBJ6RAmWzAWp2CXxTwYdN9qTbGVD1kNE"
	});
</script>
</body>
</html>