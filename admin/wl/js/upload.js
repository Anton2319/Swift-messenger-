FR = {
	showEl: function(id) {
		document.getElementById(id).style.display='flex';
	},
	hideEl: function(id) {
		document.getElementById(id).style.display='none';
	},
	initUploader: function () {
		this.status = document.getElementById('upStatus');
		FR.showEl('choose');
		if (!UploadChunkSize) {UploadChunkSize = 2086912;}
		this.flow = new Flow({
			target: URLRoot+'/?module=weblinks&section=public&page=upload', progressCallbacksInterval: 100,
			startOnSubmit: false, maxChunkRetries: 3, resumeLargerThan: 10485760, maxSimultaneous: 1, entireFolder: true,
			chunkSize: UploadChunkSize, query: function() {
				var params = {
					id: WebLinkId,
					pass: WebLinkPass,
					path: UploadToPath
				};
				if (isFileRequest) {params.senderName = document.getElementById('senderName').value;}
				return params;
			},
			validateGetOffsetResponse: function(file, status, message) {
				if (status == 200) {
					try {var rs = eval('(function(){return'+message+';})()');} catch (er){return false;}
					if (rs && rs.success) {
						if (rs.offset) {
							rs.offset = parseInt(rs.offset);
							if (!isNaN(rs.offset) && isFinite(rs.offset)) {
								file.offset = rs.offset;
							}
						}
						return true;
					}
				}
			}, validateGetOffsetResponseScope: this,
			validateChunkResponse: function(status, message) {
				if (status != '200') {return 'retry';}
				try {var rs = eval('(function(){return'+message+';})()');} catch (er){return 'retry';}
				if (rs) {if (rs.success) {return 'success';} else {return 'error';}}
			}, validateChunkResponseScope: this
		});
		this.flow.on('filesSubmitted', function() {
			FR.hideEl('choose');
			if (isFileRequest) {
				if (document.getElementById('senderName').value == '') {
					FR.showEl('giveName');
					document.getElementById('senderName').focus();
					return false;
				}
			}
			FR.startUpload();
		});
		this.flow.on('uploadStart', function() {
			FR.status.innerHTML = 'Upload starting...';
		});
		this.flow.on('progress', function(flow) {
			var percent = Math.floor(flow.getProgress()*100);
			FR.status.innerHTML = 'Uploading...'+percent+'%';
		});
		this.flow.on('fileSuccess', function(file, message) {
			try {var rs =  eval('(function(){return'+message+';})()');} catch (er) {
				FR.status.innerHTML = 'Unexpected server reply: ' + message;
			}
		});
		this.flow.on('fileError', function(file, message) {
			try {var rs = eval('(function(){return'+message+';})()');} catch (er){
				FR.status.innerHTML = 'Unexpected server reply: '+message;
			}
			if (rs && rs.msg) {FR.status.innerHTML = rs.msg;}
		});
		this.flow.on('complete', function() {
			FR.status.innerHTML = '';
			FR.hideEl('upStatus');
			if (isFileRequest) {
				FR.hideEl('giveName');
			}
			FR.showEl('success');
		});
		FlowUtils.DropZoneManager.add({
			domNode: document.body, findTarget: function(e) {return {el: document.body};}, overClass: 'dragged-over',
			onDrop: this.flow.onDrop, scope: this
		});
	},
	selectFiles: function() {
		this.flow.removeAll();
		this.flow.browseFiles();
	},
	startUpload: function() {
		if (isFileRequest) {
			var n = document.getElementById('senderName');
			if (n.value == '') {
				n.focus();
				n.classList.add('invalid');
				return false;
			}
			FR.hideEl('giveName');
		}
		FR.showEl('upStatus');
		this.flow.start();
	},
	reset: function() {
		if (isFileRequest) {
			document.getElementById('senderName').classList.remove('invalid');
		}
		FR.hideEl('success');
		FR.showEl('choose');
	}
};