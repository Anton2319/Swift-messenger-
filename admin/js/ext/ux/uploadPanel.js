FR.components.uploadPanel = Ext.extend(Ext.Panel, {
	initComponent: function() {
		this.initialPanelTitle = this.title;
		this.initialDocumentTitle = document.title;
		this.filesToBeLoaded = [];
		this.lastServerError = false;
		this.initFlow();
		this.btns = {
			addFile: new Ext.Action({
				text: FR.T('Add Files'),
				handler: function(){
					this.flow.browseFiles();
				},
				scope: this, scale: 'medium',
				iconCls: 'fa fa-fw fa-upload'
			}),
			addFolderSep: new Ext.Toolbar.Separator({hidden: true}),
			addFolder: new Ext.Action({
				text: FR.T('Add Folder'),
				handler: function(){
					this.flow.browseFiles(true);
				},
				scope: this, hidden: true, scale: 'medium',
				iconCls: 'fa fa-fw fa-upload'
			}),
			removeFile: new Ext.Action({
				text: FR.T('Remove from queue'),
				handler: this.removeFile,
				scope: this,
				iconCls: 'fa fa-fw fa-remove'
			}),
			cancel: new Ext.Action({
				text: FR.T('Cancel all'),
				handler: this.cancel, scope: this, style: 'margin-left:15px',
				iconCls: 'fa fa-fw fa-remove', cls: 'fr-btn-default'
			}),
			pauseFile: new Ext.Action({
				text: FR.T('Skip file'),
				handler: this.pauseFileToggle, scope: this,
				iconCls: 'fa fa-fw fa-step-forward'
			}),
			pauseToggle: new Ext.Button({
				text: FR.T('Pause all'),
				handler: this.pauseToggle, scope: this,
				iconCls: 'fa fa-fw fa-pause', cls: 'fr-btn-default'
			})
		};
		this.statusText = new Ext.Toolbar.TextItem({style: 'color:gray'});
		this.pbar = new Ext.ProgressBar({animate: true, width:150});
		this.tbar = [
			this.btns.addFile,'-',this.btns.addFolder, '->', this.statusText, '-', this.pbar
		];
		this.buttons = [
			this.btns.pauseToggle,'-',this.btns.cancel
		];
		this.grid = {};
		this.grid.contextMenu = new Ext.menu.Menu({
			items: [this.btns.pauseFile, this.btns.removeFile]
		});
		this.grid.store = new Ext.data.ArrayStore({
			autoDestroy: true, idIndex: 0,
			fields: [
				'id', 'name', 'size', 'progress', 'speed', 'remaining',
				'status', 'completedBytes', 'lastServerReply', 'file'
			]
		});
		this.grid.panel = new Ext.grid.GridPanel({
			border: false, layout: 'fit',
			store: this.grid.store, autoScroll: true,
			enableDragDrop: true, ddGroup: 'dd',
			viewConfig: {
				headersDisabled: true, forceFit: true, scrollOffset: 35
			},
			sm: new Ext.grid.RowSelectionModel({singleSelect: true}),
			colModel: new Ext.grid.ColumnModel({
				defaults: {width: 120, sortable: false},
				columns: [
					{header: FR.T('File name'), width: 200, renderer: function(v) {
						var ext = FR.utils.getFileExtension(v);
						return '<img class="itemIcon" src="images/fico/ext2ico.php?theme='+Settings.ui_theme+'&ext='+ext+'" width="16" height="16" border="0" /> '+v;
					}, dataIndex: 'name'},
					{header: FR.T('Size'), width: 50, renderer: function(s){return Ext.util.Format.fileSize(s);}, dataIndex: 'size'},
					{header: FR.T('Uploaded'), width: 80, dataIndex: 'completedBytes'},
					new Ext.ux.ProgressColumn({
						header: FR.T('Progress'), dataIndex: 'progress',
						width: 130, align: 'center',
						renderer: function(v, meta, record) {
							if (record.data.status == 'uploading') {
								if (v == 1) {
									return FR.T('Waiting (100%)');
								}
								return false;
							} else if (record.data.status == 'paused') {
								if (v == 0) {
									return '<span style="font-style:oblique">'+FR.T('Paused')+'</span>';
								} else if (v == 1) {
									return false;
								} else {
									return '<span style="font-style:oblique">'+FR.T('Paused (%1%)').replace('%1', Math.floor(v*100))+'</span>';
								}
							}
							return FR.T(record.data.status);
						}
					}),
					{header: FR.T('Speed'), width: 80, dataIndex: 'speed'},
					{header: FR.T('Time remaining'), width: 100, dataIndex: 'remaining'}
				]
			}),
			listeners: {
				scope: this,
				'rowclick': function (grid, rowIndex, e) {
					var clicked = this.grid.store.getAt(rowIndex);
					var r = grid.getSelectionModel().getSelected();
					if (!r) {
						this.grid.panel.getSelectionModel().selectRow(rowIndex);
					}
					this.showContextMenu(clicked, e);
					//console.log(clicked.data.file);
				},
				'rowcontextmenu': function (grid, rowIndex, e) {
					var clicked = this.grid.store.getAt(rowIndex);
					var r = grid.getSelectionModel().getSelected();
					if (r !== clicked) {
						this.grid.panel.getSelectionModel().selectRow(rowIndex);
					}
					this.showContextMenu(clicked, e);
				},
				'render': function(grid) {
					if (this.triggerSelection == 'files') {
						this.flow.browseFiles();
					} else if (this.triggerSelection == 'folder') {
						this.flow.browseFiles(true);
					} else {
						if (this.files) {
							this.flow.addFiles(this.files);
						} else {
							if (this.dropEvent) {
								this.flow.onDrop(this.dropEvent);
							}
						}
					}
					FlowUtils.DropZoneManager.add({
						domNode: this.grid.panel.getEl().dom, overClass: 'dragged-over',
						findTarget: function(e) {return {el: this.grid.panel.getEl()};},
						onDrop: this.flow.onDrop, scope: this
					});
				}
			}
		});
		Ext.apply(this, {
			layout: 'fit',
			items: this.grid.panel
		});
		FR.components.uploadPanel.superclass.initComponent.apply(this, arguments);
	},
	initFlow: function() {
		this.flow = new Flow({
			target: '?module=fileman&section=do&page=up',
			chunkSize: Settings.upload_chunk_size, progressCallbacksInterval: 100,
			maxChunkRetries: 3, resumeLargerThan: 10485760, maxSimultaneous: Settings.upload_max_simultaneous,
			validateChunkResponse: function(status, message) {
				if (status != '200') {return 'retry';}
				try {var rs = Ext.util.JSON.decode(message);} catch (er){return 'retry';}
				if (rs) {if (rs.success) {return 'success';} else {return 'error';}}
			}, validateChunkResponseScope: this,
			validateGetOffsetResponse: function(file, status, message) {
				if (status == 200) {
					try {
						var rs = Ext.util.JSON.decode(message);
					} catch (er){return false;}
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
			query: {path: this.targetPath}
		});

		this.flow.on('fileAdded', this.onFileAdded.createDelegate(this));
		this.flow.on('filesSubmitted', this.onFilesSubmitted.createDelegate(this));
		this.flow.on('fileProgress', this.onFileProgress.createDelegate(this));
		this.flow.on('fileError', this.onFileError.createDelegate(this));
		this.flow.on('fileSuccess', this.onFileSuccess.createDelegate(this));
		this.flow.on('progress', this.onProgress.createDelegate(this));
		this.flow.on('complete', this.onComplete.createDelegate(this));
	},
	start: function() {
		this.btns.pauseToggle.setIconClass('fa fa-fw fa-pause');
		this.btns.pauseToggle.setText(FR.T('Pause all'));
		this.flow.start();
	},
	cancel: function() {
		document.title = this.initialDocumentTitle;
		if (this.targetPath == FR.currentPath) {
			FR.UI.gridPanel.load(FR.currentPath);
		}
		this.flow.removeAll();
		if (this.errPrompt) {
			this.errPrompt.close();
		}
		this.destroy();
		if (this.window) {this.window.close();}
	},
	pauseToggle: function(btn) {
		if (this.flow.paused) {
			btn.setIconClass('fa fa-fw fa-pause');
			btn.setText(FR.T('Pause all'));
			this.flow.start();
			this.setTitle(this.initialPanelTitle);
		} else {
			btn.setIconClass('fa fa-fw fa-play');
			btn.setText(FR.T('Resume'));
			this.setTitle('Upload paused');
			this.flow.pause();
		}
	},
	pauseFileToggle: function() {
		var r = this.grid.panel.getSelectionModel().getSelected();
		if (r) {
			if (r.data.file.queuePaused) {
				r.data.file.start();
			} else {
				r.data.file.pause(true);
			}
		}
	},
	removeFile: function() {
		var record = this.grid.panel.getSelectionModel().getSelected();
		this.flow.removeFile(record.data.file);
		if (this.flow.files.length > 0) {
			this.grid.store.remove(record);
			this.onProgress(this.flow);
		}
	},
	onFileAdded: function(file, event) {
		var name = file.name;
		if (Settings.upload_blocked_types.length > 0 && Settings.upload_blocked_types.indexOf(FR.utils.getFileExtension(file.name)) != -1) {
			FR.UI.feedback(FR.T('You are not allowed to upload the file "%1"').replace('%1', file.name));
			return false;
		}
		if (file.relativePath) {
			name = file.relativePath+name;
		}
		this.filesToBeLoaded.push(new Ext.data.Record(
			{
				id: file.uniqueIdentifier,
				name: name,
				size: file.size,
				progress: 0,
				status: FR.T('Queued'),
				file: file
			},
			file.uniqueIdentifier
		));
	},
	onFilesSubmitted: function() {
		if (!this.flow.files.length) {return false;}
		this.grid.store.suspendEvents();
		Ext.each(this.filesToBeLoaded, function(r) {
			this.grid.store.add(r);
		}, this);
		this.grid.store.resumeEvents();
		this.grid.store.fireEvent('datachanged', this.grid.store);
		this.filesToBeLoaded = [];
		if (!this.flow.paused) {
			this.start();
		}
		this.onProgress(this.flow);
	},
	onComplete: function() {
		document.title = this.initialDocumentTitle;
		if (this.flow.completedFiles > 0) {
			if (this.targetPath == FR.currentPath) {
				FR.UI.gridPanel.load(FR.currentPath);
				if (FR.UI.tree.currentSelectedNode.loading == false && FR.UI.tree.currentSelectedNode.loaded == true) {
					FR.UI.tree.reloadNode(FR.UI.tree.currentSelectedNode);
				}
			}
			FR.UI.reloadStatusBar();
		}
		if (this.errPrompt) {
			this.errPrompt.close();
		}
		this.destroy();
		if (this.window) {this.window.close();}
	},
	onProgress: function(flow) {
		var completed = Ext.util.Format.fileSize(flow.completedBytes);
		var total = Ext.util.Format.fileSize(flow.size);
		var countFiles = flow.files.length;
		var percent = flow.getProgress();
		var status = '';
		var remainingFiles = countFiles-flow.completedFiles;
		if (countFiles > 1 && remainingFiles > 0) {
			status += FR.T('%1 files left').replace('%1', remainingFiles)+', ';
		}
		status += FR.T('%1 of %2').replace('%1', completed).replace('%2', total);
		this.statusText.update(status);
		var nicePerc = Math.floor(percent*100)+'%';
		this.pbar.updateProgress(percent, nicePerc);
		this.setTitle('Uploading.. %1'.replace('%1', nicePerc));
		document.title = '['+nicePerc+'] ' + this.initialDocumentTitle;
	},
	onFileProgress: function(file) {
		var r = this.grid.store.getById(file.uniqueIdentifier);
		if (!r) {return false;}
		if (file.paused) {
			if (file.queuePaused) {
				r.data['status'] = '<span style="font-style:oblique">'+FR.T('[Skipped]')+'</span>';
			} else {
				r.data['status'] = 'paused';
			}
			r.data['speed'] = '';
		} else {
			if (file.uploadingChunk && file.uploadingChunk.retries > 0) {
				r.data['status'] = FR.T('Uploading (Retry #%1)...').replace('%1', file.uploadingChunk.retries);
			} else {
				r.data['status'] = 'uploading';
			}
			r.data['remaining'] = this.formatTime(file.timeRemaining());
		}
		r.data['progress'] = file.progress;
		if (file.completedBytes > 0) {
			if (file.averageSpeed >= 1) {
				r.data['speed'] = Ext.util.Format.fileSize(file.averageSpeed)+'/s';
			} else {
				r.data['speed'] = '';
			}
			r.data['completedBytes'] = Ext.util.Format.fileSize(file.completedBytes);
		} else {
			r.data['speed'] = '';
			r.data['completedBytes'] = '';
		}
		r.commit();
	},
	onFileSuccess: function(file, message) {
		var r = this.grid.store.getById(file.uniqueIdentifier);
		try {
			var rs = Ext.util.JSON.decode(message);
		} catch (er){
			r.data['status'] = '<span style="color:red">'+FR.T('Server error')+'</span>';
			this.lastServerError = message;
		}
		if (rs) {
			if (!rs.success) {
				this.lastServerError = message;
				if (rs.msg) {
					r.data['status'] = rs.msg;
					this.lastServerError = rs.msg;
				} else {
					r.data['status'] = '<span style="color:red">'+FR.T('Server error')+'</span>';
				}
			} else {
				r.data['status'] = '<span style="color:green">'+rs.msg+'</span>';
			}
		} else {
			r.data['status'] = '<span style="color:red">'+FR.T('Server error')+'</span>';
			this.lastServerError = message;
		}
		r.commit();
		var index = this.grid.store.indexOfId(file.uniqueIdentifier);
		this.grid.panel.getView().focusRow(index);
	},
	onFileError: function(file, message) {
		var r = this.grid.store.getById(file.uniqueIdentifier);
		try {
			var rs = Ext.util.JSON.decode(message);
		} catch (er){
			r.data['status'] = '<span style="color:red">'+FR.T('Server error')+'</span>';
			this.lastServerError = message;
		}
		if (rs) {
			if (!rs.success) {
				this.lastServerError = message;
			}
			r.data['status'] = '<span style="color:red">'+FR.T('Failed')+'</span>';
			if (rs.msg) {
				r.data['status'] += ': '+rs.msg;
				this.lastServerError = rs.msg;
			}
		} else {
			r.data['status'] = '<span style="color:red">'+FR.T('Server error')+'</span>';
			this.lastServerError = message;
		}
		r.commit();
		this.btns.pauseToggle.setIconClass('fa fa-fw fa-play');
		this.btns.pauseToggle.setText(FR.T('Resume'));
		this.errorPrompt(r);
	},
	showContextMenu: function(record, e) {
		if (record.data.file.queuePaused) {
			this.btns.pauseFile.setText(FR.T('Resume file'));
			this.btns.pauseFile.setIconClass('fa fa-fw fa-play');
		} else {
			this.btns.pauseFile.setText(FR.T('Skip file'));
			this.btns.pauseFile.setIconClass('fa fa-fw fa-step-forward');
		}
		this.grid.contextMenu.showAt([e.getPageX()+3, e.getPageY()+3]);
	},
	errorPrompt: function(r) {
		if (!this.errPrompt) {
			this.errPrompt = {
				tabPanel: new Ext.TabPanel({
					activeTab: 0, items: [],
					listeners: {
						'tabchange': function(tp, p) {
							if (p) {p.doLayout();}
						},
						'remove': function(tp, p) {
							if (tp.items.getCount() == 0) {
								this.errPrompt.win.hide();
							}
						}, scope: this
					}
				}),
				close: function() {this.win.close();}
			};
			this.errPrompt.win = new Ext.Window({
				title: FR.T('A problem has been encountered!'),
				width: 450, height: 240, resizable: false, layout: 'fit',
				items: this.errPrompt.tabPanel, hideBorders: true,
				buttons: [
					{
						text: FR.T('Try again'),
						iconCls: 'fa fa-fw fa-repeat', cls: 'fr-btn-default',
						handler: function() {
							this.errPrompt.tabPanel.remove(r.id, true);
							r.data.file.start();
							this.start();
						}, scope: this
					},
					{
						text: FR.T('Skip file'), cls: 'fr-btn-default', style: 'margin-left:15px',
						iconCls: 'fa fa-fw fa-step-forward', hidden: (this.grid.store.getCount() < 2),
						handler: function() {
							this.errPrompt.tabPanel.remove(r.id, true);
							r.data.file.pause(true);
							this.start();
						}, scope: this
					},
					{
						text: FR.T('Cancel all'), cls: 'fr-btn-default',
						iconCls: 'fa fa-fw fa-remove', style: 'margin-left:15px',
						handler: function() {
							this.cancel();
						}, scope: this
					}
				]
			});
		}
		this.errPrompt.win.show();
		var tab = this.errPrompt.tabPanel.getItem(r.id);
		if (!tab) {
			tab = new Ext.Panel({
				id: r.id, title: r.data.name, layout: 'fit',
				contentEl: Ext.DomHelper.append(Ext.get('theBODY'), {tag: 'IFRAME', frameborder: 0, height: '100%', width: '100%'})
			});
			this.errPrompt.tabPanel.add(tab);
		}
		tab.show();
		var messageArea = tab.contentEl;
		if (messageArea.contentDocument) {
			if (messageArea.contentDocument.document) {
				var frameDoc = messageArea.contentDocument.document;
			} else {
				var frameDoc = messageArea.contentDocument
			}
		} else {
			if (messageArea.contentWindow.document) {
				var frameDoc = messageArea.contentWindow.document
			}
		}
		frameDoc.open();
		frameDoc.write(
			'<style>' +
				'body {' +
					'background: white;' +
					'font: 13px \'Roboto\', Helvetica, sans-serif;' +
					'color: #000;' +
					'cursor: default;' +
				'}' +
			'</style>'
		);
		frameDoc.write(this.lastServerError);
		frameDoc.close();
	},
	formatTime: function(secs) {
		if (secs == Number.POSITIVE_INFINITY) {return '';}
		var hours = Math.floor(secs / (60 * 60));
		var divisor_for_minutes = secs % (60 * 60);
		var minutes = Math.floor(divisor_for_minutes / 60);
		var divisor_for_seconds = divisor_for_minutes % 60;
		var seconds = Math.ceil(divisor_for_seconds);
		var str = '';
		if (hours > 24) {
			return FR.T('too long');
		}
		if (hours > 0) {
			str += FR.T('%1 hours').replace('%1', hours);
		}
		if (minutes > 0) {
			if (str != '') {str += ', ';}
			str += FR.T('%1 minutes').replace('%1', minutes);
		}
		if (seconds > 0) {
			if (str != '') {str += ', ';}
			str += FR.T('%1 seconds').replace('%1', seconds);
		}
		return str;
	},
	onRender: function() {
		FR.components.uploadPanel.superclass.onRender.apply(this, arguments);
		if (FlowUtils.browserSupport.folders) {
			this.btns.addFolder.show();
			this.btns.addFolderSep.show();
		}
	}
});
Ext.reg('uploadPanel', FR.components.uploadPanel);