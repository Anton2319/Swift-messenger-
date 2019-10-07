FR.utils = {
	currentFolderAllowsUpload: function () {
		if (!User.perms.upload) {return false;}
		if (!FR.UI.tree.currentSelectedNode) {return false;}
		if (FR.currentSection == 'sharedFolder') {
			return (FR.currentFolderPerms && FR.currentFolderPerms.upload);
		}
		return (FR.currentSection == 'myfiles');
	},
	canAddComments: function () {
		if (!User.perms.write_comments) {return false;}
		if (FR.UI.tree.currentSelectedNode) {
			var currentFolderPerms = FR.UI.tree.currentSelectedNode.attributes.perms;
			if (currentFolderPerms) {
				return currentFolderPerms.comment;
			}
		}
		return true;
	},
	reloadGrid: function (highlightOnDisplay) {
		if (highlightOnDisplay) {
			FR.UI.gridPanel.highlightOnDisplay = highlightOnDisplay;
		}
		FR.UI.gridPanel.load(FR.currentPath);
	},
	reloadTree: function() {
		if (['myfiles', 'sharedFolder', 'userWithShares', 'collections'].indexOf(FR.currentSection) != -1) {
			var n = FR.UI.tree.currentSelectedNode;
			if (n.loading == false && n.loaded == true) {
				FR.UI.tree.reloadNode(n);
			}
		}
	},
	pathInfo: function (path) {
		var s = path.split('/');
		return {basename: s.pop(), dirname: s.join('/')};
	},
	getFileExtension: function (filename) {
		var dotpos = filename.lastIndexOf(".");
		if (dotpos == -1) {
			return '';
		}
		return filename.substr(dotpos + 1).toLowerCase();
	},
	stripFileExtension: function (filename) {
		var dotpos = filename.lastIndexOf(".");
		if (dotpos == -1) {
			return filename;
		}
		if (dotpos == 0) {
			return '';
		}
		return filename.substr(0, dotpos);
	},
	dimExt: function (filename) {
		var dot = filename.lastIndexOf(".");
		if (dot == -1) {
			return filename;
		}
		var name = filename.substr(0, dot);
		var ext = filename.substr(dot);
		return name + '<span class="gray">' + ext + '<span>';
	},
	humanFilePath: function (str) {
		str = str.replace('/ROOT/HOME', FR.T('My Files'));
		str = str.replace('/ROOT/TRASH', FR.T('Trash'));
		return str;
	},
	inPath: function(childPath, parentPath) {
		return childPath.indexOf(parentPath) === 0;
	},
	supportsImageViewer: function(item) {
		if (item.data.isFolder) {return false;}
		return (['wvideo', 'img', 'pdf', 'raw', 'img2'].indexOf(item.data.filetype) != -1);
	},
	showPreview: function (item) {
		if (!User.perms.download) {return false;}
		if (!item) {
			item = FR.UI.gridPanel.getOneSel();
		}
		var path = item.data.path;
		FR.previewData = {path: path, filename: item.data.filename};
		if (item.data.filetype == 'mp3') {
			FR.UI.AudioPlayer.open(item);
			return true;
		}
		if (this.supportsImageViewer(item)) {
			if (!FR.UI.ImageViewer) {
				FR.UI.ImageViewer = new FR.components.ImageViewer();
			}
			FR.UI.ImageViewer.open(item);
			return true;
		}
		var opts = {
			title: item.data.filename,
			autoDestroy: true, resizable: true,
			src: FR.baseURL + '/?module=fileman&section=utils&page=file_preview',
			post: [{name: 'path', value: path}]
		};
		if (item.data.filetype == 'arch') {
			opts.width = 500;
			opts.height = 400;
		}
		FR.UI.popup(opts);
		return true;
	},
	locateSelected: function () {
		var s = FR.UI.gridPanel.getOneSel().data;
		if (s.isFolder) {
			FR.utils.locateItem(s.path);
		} else {
			FR.utils.locateItem(FR.utils.pathInfo(s.path).dirname, s.filename);
		}
	},
	locateItem: function (path, filename, callback) {
		if (filename) {
			if (path == FR.currentPath) {
				FR.UI.gridPanel.highlight(filename, callback);
				return;
			}
			FR.UI.gridPanel.highlightOnDisplay = filename;
			FR.UI.gridPanel.highlightOnLoadCallback = callback;
		}
		if (!callback) {
			callback = function (success, selNode) {
				if (success && selNode) {
					selNode.ensureVisible();
				}
			}
		}
		if (filename) {callback = false;}
		FR.UI.tree.panel.selectPath(path, 'pathname', callback);
	},
	browseToPath: function(path) {
		this.locateItem(path, false, function (success, selNode) {
			if (success && selNode) {
				selNode.ensureVisible();
			} else {
				if (FR.UI.tree.currentSelectedNode) {
					FR.UI.tree.reloadNode(FR.UI.tree.currentSelectedNode, function () {
						FR.utils.locateItem(path);
					});
				}
			}
		});
	},
	applyFileUpdates: function(path, updates) {
		var treeNode = FR.UI.tree.panel.findNodeByPath(path);
		if (treeNode) {
			if (!treeNode.attributes.custom) {treeNode.attributes.custom = {};}
			if (updates == 'remove') {
				treeNode.parentNode.removeChild(treeNode);
				if (!FR.UI.tree.currentSelectedNode) {
					if (Settings.has_home_folder) {
						FR.UI.tree.homeFolderNode.select();
					}
				}
			} else if (updates == 'reload') {
				if (!FR.utils.inPath(FR.currentPath, path)) {
					FR.UI.tree.reloadNode(treeNode);
				}
			} else {
				Ext.iterate(updates, function (k, v) {
					if (['weblink', 'star', 'share', 'notInfo', 'label'].indexOf(k) != -1) {
						treeNode.attributes.custom[k] = v;
					} else if (k == 'filename') {
						treeNode.setText(v);
						if (treeNode.attributes.objectType != 'collection') {
							treeNode.attributes.pathname = v;
							var oldPath = treeNode.getPath('pathname');
							if (oldPath == FR.currentPath) {
								FR.currentPath = treeNode.getPath('pathname');
								FR.utils.reloadGrid();
							}
						}
					}
				});
				FR.UI.tree.updateIcon(treeNode);
			}
		}
		var gridRow = FR.UI.gridPanel.getByPath(path);
		if (gridRow) {
			var store = FR.UI.gridPanel.getStore();
			if (updates == 'remove' || updates == 'removeFromGrid') {
				store.remove(store.getById(gridRow.id));
			} else {
				Ext.iterate(updates, function (k, v) {
					if (['star', 'share', 'comments', 'notInfo'].indexOf(k) != -1) {
						gridRow.data[k] = v;
					} else if (k == 'label') {
						gridRow.data[k] = store.fields.get('label').convert(v);
					} else if (k == 'weblink') {
						gridRow.data.hasWebLink = v;
					} else if (k == 'lock') {
						gridRow.data.lockInfo = v;
					} else if (k == 'filename') {
						if (gridRow.data.path) {
							if (gridRow.data.objectType != 'collection') {
								var pi = FR.utils.pathInfo(gridRow.data.path);
								gridRow.data.path = pi.dirname + '/' + v;
							}
						}
						gridRow.data.filename = v;
					}
				});
				FR.UI.gridPanel.getView().refresh();
			}
		}
		if (updates == 'reload') {
			if (path == FR.currentPath) {
				FR.utils.reloadGrid();
			}
		}
	},
	applyBatchFileUpdates: function(updates) {
		Ext.each(updates, function(u) {this.applyFileUpdates(u.path, u.updates);}, this);
	},
	elementInView: function(o, s, offset) {
		var b = s.bottom;
		if (offset) {b = b*offset;}
		return (o.top <= b && o.bottom >= s.top && o.right >= s.left && o.left <= s.right);
	},
	encodeURIComponent: function(s) {
		return encodeURIComponent(s).replace(/\-/g, "%2D").replace(/\_/g, "%5F").replace(/\./g, "%2E").replace(/\!/g, "%21").replace(/\~/g, "%7E").replace(/\*/g, "%2A").replace(/\'/g, "%27").replace(/\(/g, "%28").replace(/\)/g, "%29");
	}
};

FR.contextMenuActions = {
	refresh: function() {
		FR.utils.reloadGrid();
		FR.utils.reloadTree();
	},
	selectAll: function () {FR.UI.gridPanel.selModel.selectAll();},
	sortItems: function() {
		FR.UI.gridPanel.getView().mainHd.setStyle('display', 'block');
		FR.UI.feedback(FR.T('Use the displayed header bar to sort by the desired field.'));
	},
	locate: function() {return FR.utils.locateSelected();},
	newFolder: function(cm) {
		var path;
		if (cm) {
			path = cm.getTargetFileInfo().path;
		} else {
			path = FR.currentPath;
		}
		new Ext.ux.prompt({
			title: FR.T('Create new folder'), defaultValue: FR.T('New Folder'),
			confirmHandler: function(folderName) {
				if (!folderName) {return true;}
				FR.actions.newFolder(path, folderName);
			}
		});
	},
	newFileRequest: function() {
		new Ext.ux.prompt({
			title: FR.T('What are you requesting?'), placeHolder: FR.T('Photos, Documents, Contracts...'),
			confirmHandler: function(folderName) {
				if (!folderName) {return true;}
				FR.actions.newFolder(FR.currentPath, folderName, function(rs, opts) {
					FR.actions.WebLink(opts.params.path+'/'+opts.params.name, opts.params.name, true);
				});
			}
		});
	},
	download: function(cm) {
		var paths, zipName = FR.UI.currentFolderTitle;
		if (cm) {
			var t = cm.getTargetFileInfo();
			paths = t.paths || [t.path];
			if (t.filename) {zipName = t.filename;}
		} else {
			paths = [];
			Ext.each(FR.UI.gridPanel.getSelectedFiles(), function(item) {
				paths.push(item.path);
			});
		}
		FR.actions.download(paths, zipName);
	},
	preview: function() {FR.utils.showPreview();},
	addTags: function() {FR.actions.tagSelected();},
	addStar: function(cm) {FR.actions.star(cm, 'add');},
	removeStar: function(cm) {FR.actions.star(cm, 'remove');},
	weblink: function(cm) {
		var path, fileName;
		if (cm) {
			var t = cm.getTargetFileInfo();
			path = t.path;
			fileName = t.filename;
		} else {
			var item = FR.UI.gridPanel.getOneSel();
			path = item.data.path;
			fileName = item.data.filename;
		}
		FR.actions.WebLink(path, fileName);
	},
	unweblink: function() {FR.actions.UnWebLink();},
	addToCollection: function(cm) {
		if (!FR.UI.collectionSelector) {
			FR.UI.collectionSelector = new Ext.ux.TargetSelector({
				dataUrl: FR.baseURL + '/?module=collections&page=tree',
				noBrowse: true, defaultFolderName: FR.T('Collections'),
				emptyText: 'There are no collections',
				buttons: [
					{
						text: FR.T('Ok'),
						cls: 'fr-btn-primary', disabled: true,
						handler: function() {
							this.params.targetPath = this.getSelectedPath();
							this.hide();
							FR.actions.addToCollection(this.params);
						}
					},
					'->',
					{
						iconCls: 'fa fa-fw fa-plus',
						tooltip: FR.T('Create a new collection'),
						handler: function() {this.newCollectionPrompt('');}
					}
				],
				newCollectionPrompt: function (defaultValue) {
					new Ext.ux.prompt({
						title: FR.T('Create a new collection'), defaultValue: defaultValue,
						confirmHandler: function(name) {
							FR.actions.createCollection({
								params: {name: name},
								successCallback: Ext.createDelegate(function(rs) {
									this.addRecord({
										text: rs.collectionInfo.name,
										pathname: rs.collectionInfo.id,
										iconCls: 'fa-archive'
									});
									FR.UI.tree.collectionsNode.appendChild(new FR.components.TreeNode({
										text: rs.collectionInfo.name,
										pathname: rs.collectionInfo.id,
										section: 'collection',
										objectType: 'collection',
										nodeType: 'node',
										iconCls: 'fa-archive',
										allowDrop: true
									}));
								}, this),
								failureCallback: Ext.createDelegate(function(rs, opts) {
									this.newCollectionPrompt(opts.params.name);
								}, this)
							});
						}, scope: this
					});
				},
				onSelectionChange: function() {
					this.buttons[0].setDisabled(!this.selectedRecord);
				}
			});
		}
		var params = {};
		var t = cm.getTargetFileInfo();
		if (cm.location == 'tree') {
			params['paths[]'] = [t.path];
		}
		FR.UI.collectionSelector.params = params;
		FR.UI.collectionSelector.show().load('/ROOT/Collections');
	},
	removeFromCollection: function() {FR.actions.removeFromCollection();},
	removeCollections: function(cm) {FR.actions.removeCollections(cm);},
	shareWithUsers: function(cm) {
		if (!cm) {
			cm = FR.UI.contextMenu;
			cm.location = 'grid';
			cm.target = FR.UI.gridPanel.getSelectedFiles();
		}
		var t = cm.getTargetFileInfo();
		FR.sharing = {
			path: t.path,
			name: t.filename
		};
		if (cm.location == 'tree') {
			FR.sharing.type = 'folder';
		} else {
			FR.sharing.type = cm.target[0].isFolder ? 'folder': 'file';
		}
		FR.UI.persistentWindow({
			id: 'folderShare',
			src:FR.baseURL+'/?module=share',
			post: [{name:'path', value: FR.sharing.path}], modal: true,
			width:550, height:420, title: FR.sharing.name,
			initMsg: FR.T('Loading...')
		});
	},
	email: function(cm) {
		var items = [];
		if (cm.location == 'tree') {
			items.push({icon: 'f.png', filename: cm.target.text, path: cm.target.getPath('pathname'), isFolder: true});
		} else {
			Ext.each(cm.target, function (s) {
				var path = (s.path);
				items.push({icon: s.icon, filename: s.filename, path: path, filesize: s.filesize, isFolder: s.isFolder});
			});
		}
		FR.actions.emailFiles(items, true);
	},
	props: function(cm) {
		var params = [];
		var t = cm.getTargetFileInfo();
		params.push({name:'path', value: t.path});
		FR.UI.popup({
			src:FR.baseURL+'/?module=folder_options',
			post: params,
			width:380, height:350, title: t.filename,
			initMsg: FR.T('Loading...')
		});
	},
	comment: function() {return FR.UI.infoPanel.showComments();},
	metadata: function(cm) {
		var t = cm.getTargetFileInfo();
		FR.actions.openMetadata({title: t.filename, path: t.path});
	},
	activityLog: function(cm) {
		var t = cm.getTargetFileInfo();
		FR.UI.popup({
			loadingMsg: FR.T('Loading the file\'s activity log...'),
			src:FR.baseURL+'/?module=filelog&section=default&page=default',
			post: [{name: 'path', value: t.path}],
			title: t.filename
		});
	},
	zip: function(cm) {
		var t = cm.getTargetFileInfo();
		var paths = (t.paths || [t.path]);
		var zipName;
		if (cm.location == 'tree') {
			zipName = t.filename + '.zip';
		} else {
			if (t.path) {
				zipName = FR.utils.stripFileExtension(t.filename)+'.zip';
			} else {
				zipName = FR.T('New Archive.zip')
			}
		}
		new Ext.ux.prompt({
			title: FR.T('Add to zip'),
			text: FR.T('Please type a name for the zip file:'),
			defaultValue: zipName,
			confirmHandler: function(zipName) {
				if (zipName) {
					var target = FR.currentPath+'/'+zipName;
					FR.actions.abstractZip(target, paths);
				}
			}
		});
	},
	extract: function(cm) {return FR.actions.extractPrompt(cm);},
	lock: function() {return FR.actions.changeLocking(true);},
	unlock: function() {return FR.actions.changeLocking(false);},
	copyOrMove: function(cm) {
		var params = {}, defaultName;
		var t = cm.getTargetFileInfo();
		if (cm.location == 'tree') {
			defaultName = t.filename;
		} else {
			defaultName = FR.UI.currentFolderTitle;
		}
		params['paths[]'] = (t.paths || [t.path]);
		if (!FR.UI.moveToFolderSelector) {
			FR.UI.moveToFolderSelector = new Ext.ux.TargetSelector({
				dataUrl: FR.baseURL + '/?module=fileman&section=get&page=tree',
				allowRootSelection: true, addRequiresTarget: true,
				buttons: [
					{
						text: FR.T('Move here'), disabled: true,
						cls: 'fr-btn-primary',
						handler: function () {
							this.params.moveTo = this.getSelectedPath();
							this.hide();
							FR.actions.process({
								baseURL: FR.doBaseURL,
								url: '&page=move',
								params: this.params,
								loadMsg: 'Moving files...',
								failureCallback: function() {this.show();},
								successCallback: function() {
									FR.UI.reloadStatusBar();
								}, scope: this
							});
						}
					},
					{
						text: FR.T('Copy here'),
						cls: 'fr-btn-primary', style: 'margin-left:5px',
						handler: function () {
							this.params.copyTo = this.getSelectedPath();
							this.hide();
							FR.actions.process({
								baseURL: FR.doBaseURL,
								url: '&page=copy',
								params: this.params,
								loadMsg: 'Copying...',
								failureCallback: function() {this.show();},
								successCallback: function() {
									FR.UI.reloadStatusBar();
								}, scope: this
							});
						}
					},
					'->',
					{
						iconCls: 'fa fa-folder-plus',
						tooltip: FR.T('Create a new folder'),
						handler: function () {
							if (this.selectedRecord) {
								this.load(this.selectedRecord.get('path'));
							}
							new Ext.ux.prompt({
								title: FR.T('Create a new folder'), defaultValue: '',
								confirmHandler: function(name) {
									if (!name) {return false;}
									FR.actions.newFolder(this.currentPath, name, Ext.createDelegate(function() {
										this.addRecord({text: name});
									}, this));
								}, scope: this
							});
						}
					}
				],
				onSelectionChange: function() {
					var path;
					var mvBtn = this.buttons[0];
					var cpBtn = this.buttons[1];
					if (this.selectedRecord) {
						path = this.selectedRecord.get('path');
						cpBtn.setText(FR.T('Copy'));
						mvBtn.setText(FR.T('Move'));
					} else {
						cpBtn.setText(FR.T('Copy here'));
						mvBtn.setText(FR.T('Move here'));
						path = this.currentPath;
					}
					mvBtn.setDisabled((path == FR.currentPath || path == '/ROOT' || path == '/ROOT/SHARED'));
					cpBtn.setDisabled((path == '/ROOT' || path == '/ROOT/SHARED'));
				}
			});
		}
		FR.UI.moveToFolderSelector.params = params;
		var browsePath = '/ROOT/HOME';
		if (['myfiles', 'userWithShares', 'sharedFolder'].indexOf(FR.currentSection) != -1) {
			browsePath = FR.currentPath;
		} else {
			if (params['paths[]'].length == 1) {
				var first = params['paths[]'][0];
				var pathInfo = FR.utils.pathInfo(first);
				browsePath = pathInfo.dirname;
			}
		}
		FR.UI.moveToFolderSelector.show().load(browsePath);
	},
	rename: function(cm) {
		var t = cm.getTargetFileInfo();
		var pars = {path: t.path};
		new Ext.ux.prompt({
			title: FR.T('Rename'), defaultValue: t.filename, closable: true,
			confirmHandler: function(newValue, oldValue) {
				if (newValue && newValue != oldValue) {
					pars.newName = newValue;
					FR.actions.process({
						baseURL: FR.doBaseURL,
						url: '&page=rename',
						params: pars,
						loadMsg: 'Renaming file...'
					});
				}
			}
		});
	},
	remove: function(cm, ca, e) {
		var showPrompt = e.shiftKey;
		if (!cm) {
			cm = FR.UI.contextMenu;
			cm.location = 'grid';
			cm.target = FR.UI.gridPanel.getSelectedFiles();
		}
		var paths = [];
		FR.removeParams = {};
		var hasFolders = false;
		var promptTitle = FR.T('Delete folder?');
		if (cm.location == 'tree') {
			hasFolders = true;
			FR.removeParams['paths[]'] = [cm.target.getPath('pathname')];
			promptTitle = cm.target.text;
		} else if (cm.location == 'grid') {
			if (FR.currentSection == 'trash') {
				var ids = [];
				Ext.each(cm.target, function (i) {ids.push(i.uniqid);});
				FR.removeParams['ids[]'] = ids;
			} else {
				Ext.each(cm.target, function (s) {
					if (s.isFolder) {
						hasFolders = true;
					}
					paths.push(s.path);
				});
				FR.removeParams['paths[]'] = paths;
				if (cm.target.length == 1) {
					promptTitle = cm.target[0].filename;
				}
			}
		}
		if (hasFolders || showPrompt) {
			(new Ext.Window({
				title: promptTitle,
				width: 400, modal: true, resizable: false,
				html : '<div style="font-size:12px;margin: 10px 0">'+FR.T('The selected folder and all its contents will be deleted.')+'</div><div style="margin:10px 0;color:gray;font-size:11px"><label><input type="checkbox" value="1" class="folderDelConfWinPerm" style="vertical-align:bottom;margin-right:3px;" />'+FR.T('Permanent deletion')+'</label></div>', buttonAlign: 'left',
				buttons: [
					{
						text: FR.T('Remove'), cls: 'fr-btn-primary',
						handler: function() {
							var win = this.findParentByType('window');
							var permCheckbox = win.el.query('input.folderDelConfWinPerm')[0];
							if (permCheckbox.checked) {
								FR.removeParams.permanent = true;
							}
							win.close();
							FR.actions.remove();
						}
					},
					{
						text : FR.T('Cancel'), style: 'margin-left:10px',
						handler : function() {
							this.findParentByType('window').close();
						}
					}
				]
			})).show();
			return true;
		}
		FR.actions.remove();
	},
	restore: function() {
		FR.actions.processGridItemsByIds({
			url: '/?module=trash&section=ajax&page=restore',
			loadMsg: 'Restoring file(s)...',
			successCallback: function(rs) {
				if (rs.trashCount == 0 && Settings.has_home_folder) {
					FR.UI.tree.trashNode.getUI().hide();
					FR.UI.tree.homeFolderNode.select();
				}
			}
		});
	},
	emptyTrash: function() {return FR.actions.emptyTrash();},
	indexMetadata: function(cm) {
		FR.UI.popup({
			id: 'metadataIndex', closable: false,
			src: FR.baseURL+'/?module=metadata&section=index',
			width: 400, height: 200,
			post: {name:'path', value: cm.getTargetFileInfo().path},
			autoDestroy: true
		});
	},
	saveNotif: function(settings) {
		FR.actions.saveNotifications(FR.UI.contextMenu.getTargetFileInfo().path, settings);
	},
	customAction: function(cm, ca, e) {
		return FR.actions.customActionFromCM(cm, ca, e);
	}
};

FR.actions.openMetadata = function(opts) {
	FR.UI.popup({
		title: opts.title,
		width: (FR.isMobile ? 350 : 460), height: 400, autoDestroy: true,
		src: FR.baseURL+'/?module=metadata&path='+encodeURIComponent(opts.path),
		loadingMsg: FR.T('Loading...')
	});
};
FR.actions.remove = function() {
	FR.removeParams.csrf = User.csrf_token;
	var opts = {
		params: FR.removeParams,
		loadMsg: 'Deleting files...',
		callback: function() {
			FR.removeParams = {};
		},
		successCallback: function(rs, opts) {
			if (FR.currentSection == 'trash' && rs.trashCount == 0) {
				if (Settings.has_home_folder) {
					FR.UI.tree.trashNode.getUI().hide();
					FR.UI.tree.homeFolderNode.select();
				}
			} else {
				if (!opts.params.permanent) {
					FR.UI.tree.trashNode.getUI().show();
				}
			}
			FR.UI.reloadStatusBar();
		}
	};
	if (FR.currentSection == 'trash') {
		opts.url = '/?module=trash&section=ajax&page=delete';
	} else {
		opts.url = '&page=delete';
		opts.baseURL = FR.doBaseURL;
	}
	FR.actions.process(opts);
};
FR.actions.UnWebLink = function() {
	FR.actions.processGridItemsByPaths({
		url: '/?module=weblinks&section=ajax&page=remove_multiple'
	});
};
FR.actions.download = function(paths, archiveName) {
	if (!User.perms.download) {return false;}
	var frm = document.createElement('FORM');
	var url = FR.doBaseURL+'&page=download';
	if (archiveName) {url += '&archiveName='+encodeURIComponent(archiveName);}
	frm.action = url;
	frm.method = 'POST';
	frm.target = '_blank';
	Ext.each(paths, function(p) {
		inpt = document.createElement('INPUT');
		inpt.type = 'hidden';
		inpt.name = 'paths[]';
		inpt.value = p;
		frm.appendChild(inpt);
	});
	Ext.get('theBODY').appendChild(frm);
	frm.submit();
	Ext.get(frm).remove();
};
FR.actions.openFileInBrowser = function(path) {
	var downloadURL = FR.doBaseURL+'&page=download&open_in_browser=1&paths[]='+encodeURIComponent(path);
	FR.UI.feedback(FR.T('Opening popup window... <br>Click <a href="%1" target="_blank">here</a> if the browser prevents it from opening.').replace('%1', downloadURL));
	window.setTimeout(function() {window.open(downloadURL);}, 50);
};
FR.actions.openAccountSettings = function() {
	FR.UI.popup({
		id: 'accountSettings', title: FR.T('Account settings'),
		src: FR.baseURL+'/?module=fileman&section=profile',
		width: (FR.isMobile ? 350 : 450), height: 450, autoDestroy: true, modal: true,
		loadingMsg: FR.T('Loading...')
	});
};
FR.actions.move = function(drop, target) {
	FR.moveParams = {'paths[]': [], moveTo: target};
	var paths = [];
	var hasFolders = false;
	if (drop.data.grid) {
		Ext.each(drop.data.selections, function(s) {
			if (s.data.isFolder) {hasFolders = true;}
			FR.moveParams['paths[]'].push(s.data.path);
		});
	} else if (drop.tree) {
		hasFolders = true;
		FR.moveParams['paths[]'].push(drop.dropNode.getPath('pathname'));
	} else {
		return false;
	}
	if (hasFolders) {
		new Ext.ux.prompt({
			title: FR.T('Move folder?'),
			text: FR.T('Are you sure you want to move the folder?'),
			confirmHandler: function() {
				FR.actions.doMove();
			}
		});
		return true;
	}
	FR.actions.doMove();
};
FR.actions.doMove = function() {
	FR.actions.process({
		baseURL: FR.doBaseURL,
		url: '&page=move',
		params: FR.moveParams,
		loadMsg: 'Moving files...',
		successCallback: function() {
			FR.UI.reloadStatusBar();
		}
	});
};
FR.actions.emptyTrash = function(cnfirm) {
	if (!cnfirm) {
		new Ext.ux.prompt({
			title: FR.T('Empty trash?'),
			text: FR.T('All files and folders in your trash are about to be permanently deleted.'),
			confirmHandler: function() {FR.actions.emptyTrash(true);}
		});
		return true;
	}
	FR.actions.process({
		url: '/?module=trash&section=ajax&page=empty',
		params: FR.moveParams,
		loadMsg: 'Emptying trash...',
		successCallback: function() {
			if (FR.currentSection == 'trash') {
				if (Settings.has_home_folder) {
					FR.UI.tree.trashNode.getUI().hide();
					FR.UI.tree.homeFolderNode.select();
				} else {
					FR.utils.reloadGrid();
				}
			} else {
				FR.UI.tree.trashNode.getUI().hide();
			}
			FR.UI.reloadStatusBar();
		}
	});
};
FR.actions.newFolder = function(path, folderName, successCallback) {
	FR.actions.process({
		baseURL: FR.doBaseURL,
		url: '&page=new_folder',
		params: {path: path, name: folderName},
		loadMsg: 'Creating new folder...',
		successCallback: successCallback || function(rs, opts) {
			FR.UI.gridPanel.highlightOnDisplay = opts.params.name;
		}
	});
};
FR.actions.changeLocking = function(lock) {
	FR.actions.processGridItemsByPaths({
		url: '/?module=versioning&section=ajax&page=locking&action='+(lock?'lock':'unlock'),
		loadMsg: lock?'Locking file...':'Unlocking file...'
	});
};
FR.actions.abstractZip = function(target, paths)  {
	var pars = {target: target};
	pars['paths[]'] = paths;
	FR.actions.process({
		params: pars,
		baseURL: FR.doBaseURL,
		url: '&page=zip',
		loadMsg: 'Zipping files...',
		failureCallback: function(rs) {
			if (!rs.msg) {
				new Ext.ux.prompt({text: FR.T('An error occurred while trying to process the request.')});
			}
		},
		successCallback: function() {
			FR.utils.reloadGrid();
			FR.UI.reloadStatusBar();
		}
	});
};
FR.actions.extractPrompt = function(cm) {
	var selectedFile = cm.getTargetFileInfo();
	if (!FR.UI.extractToFolderSelector) {
		FR.UI.extractToFolderSelector = new Ext.ux.TargetSelector({
			dataUrl: FR.baseURL + '/?module=fileman&section=get&page=tree',
			allowRootSelection: true, addRequiresTarget: true,
			buttons: [
				{
					text: FR.T('Extract here'),
					cls: 'fr-btn-primary',
					handler: function () {
						this.params.extractTo = this.getSelectedPath();
						this.hide();
						FR.actions.process({
							baseURL: FR.doBaseURL,
							url: '&page=extract',
							params: this.params,
							loadMsg: 'Extracting archive contents...',
							failureCallback: function() {this.show();},
							successCallback: function() {
								FR.UI.reloadStatusBar();
							}, scope: this
						});
					}
				},
				'->',
				{
					iconCls: 'fa fa-folder-plus',
					tooltip: FR.T('Create a new folder'),
					handler: function () {
						if (this.selectedRecord) {
							this.load(this.selectedRecord.get('path'));
						}
						new Ext.ux.prompt({
							title: FR.T('Create a new folder'), defaultValue: FR.utils.stripFileExtension(selectedFile.filename),
							confirmHandler: function(name) {
								if (!name) {return false;}
								FR.actions.newFolder(this.currentPath, name, Ext.createDelegate(function() {
									this.addRecord({text: name});
								}, this));
							}, scope: this
						});
					}
				}
			],
			onSelectionChange: function() {
				var path;
				var btn = this.buttons[0];
				if (this.selectedRecord) {
					path = this.selectedRecord.get('path');
					btn.setText(FR.T('Extract'));
				} else {
					btn.setText(FR.T('Extract here'));
					path = this.currentPath;
				}
				btn.setDisabled((path == '/ROOT' || path == '/ROOT/SHARED'));
			}
		});
	}
	FR.UI.extractToFolderSelector.params = {
		path: selectedFile.path
	};
	FR.UI.extractToFolderSelector.show().load(FR.currentPath);
};

FR.actions.emailFiles = function(items, sendLinks) {
	FR.sendingByEmail = {items: items, sendLinks: sendLinks};
	FR.UI.persistentWindow({
		id: 'emailFiles',
		src: FR.baseURL+'/?module=email',
		width: (FR.isMobile ? 350 : 480), height: 470,
		title: FR.T('E-mail Files'), modal: true,
		initMsg: FR.T('Loading...')
	});
};
FR.actions.EmailFromLink = function() {
	FR.UI.popups.webLink.hide();
	var pathInfo = FR.utils.pathInfo(FR.WebLinking.path);
	FR.utils.locateItem(pathInfo.dirname, pathInfo.basename, function(success, gridNode) {
		if (!success) {return false;}
		var data = gridNode.data;
		var items = [{icon: data.icon, filename: data.filename, path: data.path, filesize: data.filesize, isFolder: data.isFolder}];
		FR.actions.emailFiles(items, true);
	});
};
FR.actions.WebLink = function(path, itemTitle, isFileRequest) {
	FR.WebLinking = {path: path, isFileRequest: isFileRequest};
	FR.UI.persistentWindow({
		id: 'webLink',
		src: FR.baseURL+'/?module=weblinks',
		width: (FR.isMobile ? 350 : 450), height: 320,
		title: itemTitle, modal: true,
		initMsg: FR.T('Loading...')
	});
};
FR.actions.openVersions = function() {
	FR.UI.popup({
		title: FR.T('File Versions'),
		width: (FR.isMobile ? 350 : 450), height: 300,
		src: FR.baseURL+'/?module=versioning',
		post: [
			{name: 'path', value: FR.UI.gridPanel.getOneSel().data.path}
		],
		autoDestroy: true
	});
};
FR.actions.setLabel = function(label) {
	var t = FR.UI.contextMenu.getTargetFileInfo();
	FR.actions.process({
		url: '/?module=labels',
		params: {
			'label': label,
			'paths[]': (t.paths || [t.path])
		}
	});
};
FR.actions.star = function(cm, action) {
	var t = cm.getTargetFileInfo();
	FR.actions.process({
		url: '/?module=stars&page=set',
		params: {
			action: action,
			'paths[]': (t.paths || [t.path])
		}
	});
};
FR.actions.customAction = function(opts, path, filename, e) {
	var url = FR.baseURL+'/?module=custom_actions&action='+opts.actionName;
	var postData;
	if (opts.popup || opts.newTab) {
		if (Ext.isArray(path)) {
			postData = [];
			Ext.each(path, function (p) {
				postData.push({name: 'paths[]', value: p});
			});
		} else {
			postData = [{name: 'path', value: path}];
		}
	}
	if (opts.newTab) {
		FR.UI.postToTarget({src: url, post: postData});
	} else if (opts.popup) {
		var popOpts = {
			title: FR.T(opts.title),
			loadingMsg: opts.loadingMsg || false,
			src: url, icon: opts.icon,
			post: postData,
			autoDestroy: true
		};
		if (filename) {popOpts.title += ': '+filename;}
		if (opts.width) {popOpts.width = opts.width;}
		if (opts.height) {popOpts.height = opts.height;}
		if (opts.external || (e && e.ctrlKey)) {
			FR.UI.openInPopup(popOpts);
		} else {
			FR.UI.popup(popOpts);
		}
	} else if (opts.ajax) {
		if (Ext.isArray(path)) {
			postData = {'paths[]': path};
		} else {
			postData = {path: path};
		}
		/* todo: use actions.process */
		FR.UI.showLoading(FR.T('Please wait...'));
		Ext.Ajax.request({
			url: url,
			method: 'post',
			params: postData,
			callback: function(opts, succ, req) {
				FR.UI.doneLoading();
				try {
					var rs = Ext.decode(req.responseText);
				} catch (er){return false;}
				if (rs.refresh) {FR.utils.reloadGrid((rs.highlight || false));}
				if (rs.msg) {FR.UI.feedback(rs.msg);}
			}
		});
	} else if (opts.handler) {opts.handler();
	} else if (opts.fn) {eval(opts.fn);}
};
FR.actions.customActionFromCM = function(cm, ca, e) {
	var path, filename;
	if (cm.location == 'tree') {
		path = cm.target.getPath('pathname');
		filename = cm.target.text;
	} else {
		if (cm.target.length == 1) {
			var s = cm.target[0];
			path = s.path;
			filename = s.filename;
		} else {
			path = [];
			Ext.each(cm.target, function(d) {
				path.push(d.path);
			});
		}
	}
	this.customAction(ca.settings, path, filename, e);
};
FR.actions.createNew = function(action, filename) {
	if (!filename) {
		filename = FR.T(action.createNew.defaultFileName);
	}
	new Ext.ux.prompt({text: FR.T('Please type a file name:'), defaultValue: filename, confirmHandler: function(fileName) {
		var path = FR.currentPath+'/'+fileName;
		FR.actions.process({
			loadMsg: 'Creating blank file...',
			url: '/?module=custom_actions&action='+action.actionName+'&method=createBlankFile',
			params: {path: path},
			successCallback: function(rs, opts) {
				FR.actions.customAction(action, opts.params.path, fileName);
				FR.utils.reloadGrid();
			}
		});
	}});
};
FR.actions.saveNotifications = function(path, settings) {
	FR.actions.process({
		url: '/?module=notifications&section=ajax&page=set',
		params: {
			path: path,
			notifyWrite: (settings[0]?1:0), notifyRead: (settings[1]?1:0)
		}
	});
};
FR.actions.setMetadata = function(opts) {
	FR.actions.process(Ext.applyIf(opts, {
		url: '/?module=metadata&section=ajax&page=set'
	}));
};
FR.actions.tagSelected = function() {
	if (!FR.UI.tagWindow) {
		FR.UI.tagWindow = new Ext.Window({
			title: FR.T('Tag files'),
			resizable: false, closeAction: 'hide',
			bodyStyle: 'padding-left:27px;padding-bottom:15px',
			items: {xtype: 'tagsfield', ref: 'tagsField', width: 300, emptyText: FR.T('Add tags..')},
			buttonAlign: 'left',
			buttons: [
				{
					cls: 'fr-btn-primary',
					text: FR.T('Tag selected'),
					handler: function () {
						var paths = FR.UI.gridPanel.getSelectedAttrs('path');
						if (!paths.length) {FR.UI.feedback(FR.T('Please select at least a file or a folder!'));return false;}
						var opts = {
							url: '/?module=metadata&section=tags&page=add',
							params: {'paths[]': paths, 'tags[]': []}
						};
						this.ownerCt.ownerCt.tagsField.items.each(function(tag) {
							opts.params['tags[]'].push(tag.value);
						});
						if (!opts.params['tags[]'].length) {FR.UI.feedback(FR.T('Please add at least one tag!'));return false;}
						FR.actions.setMetadata(opts);
					}
				},
				{
					text: FR.T("Close"), style: 'margin-left:10px',
					handler: function () {FR.UI.tagWindow.hide();}
				}
			]
		});
	}
	FR.UI.tagWindow.show();
};
FR.actions.handlePaste = function(e) {
	if (!FR.utils.currentFolderAllowsUpload()) {return false;}
	var cd = e.browserEvent.clipboardData;
	if (!cd.items || !cd.items.length) {return false;}
	var item = cd.items[0];
	if (item.type.indexOf("image") == -1) {return false;}
	var reader = new FileReader();
	reader.onload = function (event) {
		var imageObj = new Image();
		imageObj.src = event.target.result;
		imageObj.style.maxWidth = '600px';
		imageObj.style.maxHeight = '400px';
		imageObj.onload = function () {
			var w = new Ext.Window({
				title: FR.T('Uploading pasted image...'),
				contentEl: this, modal: true
			});
			w.show();
			var fileName = FR.T('Pasted image') + ' ' + Ext.util.Format.date(new Date(), 'Y-m-d H-i-s') + '.png';
			FR.pasteBlob.fileName = fileName;
			var upload = new Flow({
				target: '?module=fileman&section=do&page=up',
				validateChunkResponse: function (status, message) {
					if (status != '200') {
						return 'retry';
					}
					try {
						var rs = Ext.decode(message);
					} catch (er) {
						return 'retry';
					}
					if (rs) {
						if (rs.success) {
							return 'success';
						} else {
							return 'error';
						}
					}
				}, validateChunkResponseScope: this, startOnSubmit: true,
				query: {path: FR.currentPath}
			});
			upload.on('fileSuccess', function () {
				w.close();
				FR.UI.feedback('Pasted image successfully uploaded');
				FR.actions.WebLink(FR.currentPath + '/' + fileName, fileName);
				FR.utils.reloadGrid(fileName);
				FR.pasteBlob = false;
			});
			upload.on('progress', function (flow) {
				var percent = Math.floor(flow.getProgress() * 100);
				w.setTitle(FR.T('Uploading pasted image...') + ' ' + percent + '%');
			});
			upload.addFile(FR.pasteBlob);
		}
	};
	FR.pasteBlob = item.getAsFile();
	reader.readAsDataURL(FR.pasteBlob);
};
FR.actions.filterMeta = function(fieldId, value, mode) {
	if (mode == 'exact') {value = '"'+value+'"';}
	var params = {'meta': {}};
	params.meta[fieldId] = [value];
	if (['myfiles', 'sharedFolder'].indexOf(FR.currentSection) == -1) {
		FR.utils.locateItem('/ROOT/HOME', false, function() {
			FR.UI.searchPanel.doSearch(params);
		});
	} else {
		FR.UI.searchPanel.doSearch(params);
	}
};
FR.actions.openControlPanel = function() {
	var url = FR.baseURL+'/?module=cpanel';
	if (FR.isMobile) {document.location.href = url;return;}
	FR.UI.popup({src: url, title: FR.T('Control Panel')});
};
FR.actions.addToCollection = function(params) {
	if (!params['paths[]']) {params['paths[]'] = FR.UI.gridPanel.getSelectedAttrs('path');}
	FR.actions.process({
		url: '/?module=collections&section=items&page=add',
		params: params,
		loadMsg: 'Adding files to collection...'
	});
};
FR.actions.createCollection = function(opts) {
	FR.actions.process(Ext.apply(opts, {
		url: '/?module=collections&section=collections&page=create',
		loadMsg: 'Creating a new collection...'
	}));
};
FR.actions.removeFromCollection = function() {
	FR.actions.processGridItemsByIds({
		url: '/?module=collections&section=items&page=remove',
		loadMsg: 'Removing items from collection...'
	});//todo: if user is not currently browsing the collection, the batch updates can make it look like the items were physically removed
};
FR.actions.removeCollections = function(cm) {
	var params = {'ids[]': []};
	var title;
	if (cm.location == 'tree') {
		params['ids[]'] = [cm.target.attributes.pathname];
		title = cm.target.text;
	} else {
		params['ids[]'] = FR.UI.gridPanel.getSelectedAttrs('id');
		if (params['ids[]'].length == 1) {
			title = cm.target[0].filename;
		}
	}
	new Ext.ux.prompt({
		title: title || false,
		text: FR.T('Are you sure you want to remove the collection?'),
		confirmHandler: function() {
			FR.actions.process({
				url: '/?module=collections&section=collections&page=remove',
				params: params,
				loadMsg: 'Removing collections...'
			});
		}
	});
};
FR.actions.processGridItemsByIds = function(opts) {
	FR.actions.processGridItems(Ext.apply(opts, {paramName: 'ids[]', attr: 'uniqid'}));
};
FR.actions.processGridItemsByPaths = function(opts) {
	FR.actions.processGridItems(Ext.apply(opts, {paramName: 'paths[]', attr: 'path'}));
};
FR.actions.processGridItems = function(opts) {
	var defaultParams = {};
	defaultParams[opts.paramName] = FR.UI.gridPanel.getSelectedAttrs(opts.attr);
	opts.params = Ext.apply(opts.params || {}, defaultParams);
	FR.actions.process(opts);
};
FR.actions.process = function(opts) {
	var url = opts.baseURL || FR.baseURL;
	if (opts.url) {
		url += opts.url;
	}
	if (opts.loadMsg) {
		FR.UI.showLoading(FR.T(opts.loadMsg));
	}
	Ext.Ajax.request({
		url: url, method: 'post', params: opts.params,
		opts: opts,
		callback: function(opts, succ, req) {
			FR.UI.doneLoading();
			try {
				var rs = Ext.decode(req.responseText);
			} catch (er) {}
			if (opts.opts.callback) {
				opts.opts.callback.apply(opts.opts.scope, [rs, opts.opts]);
			}
			if (!rs || !rs.success) {
				if (opts.opts.failureCallback) {
					opts.opts.failureCallback.apply(opts.opts.scope, [rs, opts.opts]);
				}
				if (!rs) {return false;}
			} else {
				if (opts.opts.successCallback) {
					opts.opts.successCallback.apply(opts.opts.scope, [rs, opts.opts]);
				}
			}
			if (rs.updates) {FR.utils.applyBatchFileUpdates(rs.updates);}
			if (rs.msg) {FR.UI.feedback(rs.msg, !rs.success);}
		}
	});
};