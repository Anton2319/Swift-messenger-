FR.components.infoPanel = Ext.extend(Ext.Panel, {//if using TabPanel directly, there are layout problems
	cls: 'fr-info-panel', stateful: false,
	showTabComments: false, layout: 'fit',
	showTabActivity: false, countSel: 0,
	initComponent: function() {
		this.tabs = {
			detailsPanel: new FR.components.detailsPanel(Ext.apply({}, this.initialConfig.detailsPanelOptions)),
			activityPanel: new FR.components.activityPanel({
				path: '/ROOT/HOME',
				style:'padding:0px;'
			}),
			commentsPanel: new FR.components.commentsPanel()
		};
		Ext.apply(this, {
			title: '&nbsp;', icon: '',
			items: {
				xtype: 'tabpanel', ref: 'tabPanel', tabPosition: 'bottom', activeTab: 0,
				items: [
					this.tabs.detailsPanel,
					this.tabs.activityPanel,
					this.tabs.commentsPanel
				],
				listeners: {
					'afterrender': function() {
						this.items.each(function(i, idx) {
							if (idx != 0) {
								this.hideTabStripItem(idx);
							}
						}, this);
					}
				}
			}
		});
		FR.components.infoPanel.superclass.initComponent.apply(this, arguments);
	},
	customCollapse: function() {
		this.collapse();
		FR.localSettings.set('infoPanelState', 'collapsed');
	},
	customExpand: function() {
		this.expand();
		FR.localSettings.set('infoPanelState', 'expanded');
	},
	refresh: function() {
		if (this.collapsed) {return false;}
		this.countSel = FR.UI.gridPanel.countSel;
		if (this.countSel == 1) {
			this.setItem(FR.currentSelectedFile);
		} else {
			this.item = null;
			this.showTabComments = false;
			this.showTabActivity = true;
			if (FR.currentSection == 'sharedFolder') {
				this.showTabActivity = Settings.filelog_for_shares;
			} else if (FR.currentSection != 'myfiles') {
				this.showTabActivity = false;
			}
			this.updateTabs();
			this.tabs.detailsPanel.gridSelChange();
		}
		this.setTitle(this.getHeaderTitle(), this.getHeaderIcon());
	},
	updateTabs: function() {
		if (!User.perms.read_comments && !User.perms.write_comments) {this.showTabComments = false;}
		if (!User.perms.file_history) {this.showTabActivity = false;}

		var tp = this.tabPanel;
		if (this.showTabComments) {
			tp.unhideTabStripItem(2);
		} else {
			tp.hideTabStripItem(2);
			if (tp.getActiveTab() == this.tabs.commentsPanel) {
				tp.setActiveTab(0);
			}
		}
		if (this.showTabActivity) {
			tp.unhideTabStripItem(1);
		} else {
			tp.hideTabStripItem(1);
			if (tp.getActiveTab() == this.tabs.activityPanel) {
				tp.setActiveTab(0);
			}
		}
	},
	setItem: function(item) {
		if (this.collapsed) {return false;}
		if (item == this.item) {return false;}
		this.item = item;
		this.showTabComments = (!FR.currentSectionIsVirtual && FR.currentSection != 'trash');
		this.showTabActivity = false;
		this.updateTabs();
		this.tabs.detailsPanel.setItem(item);
		this.tabs.commentsPanel.setItem(item);
	},
	getHeaderTitle: function() {
		var title = FR.UI.currentFolderTitle;
		if (this.countSel == 1) {
			title = this.item.data.isFolder ? this.item.data.filename : FR.utils.dimExt(this.item.data.filename);
		}
		return title;
	},
	getHeaderIcon: function() {
		var html, cls = '', url, urlStyle='';
		if (this.countSel == 1) {
			if (this.item.data.isFolder) {
				cls = 'fa fa-folder';
			} else {
				url = FR.UI.getFileIconURL(this.item.data.icon);
			}
		} else {
			var treeNode = FR.UI.tree.currentSelectedNode.attributes;
			if (treeNode.iconCls == 'avatar') {
				cls = 'round';
				url = 'a/?uid='+treeNode.uid;
			} else {
				cls = 'fa ' + (treeNode.iconCls || 'fa-folder');
			}
		}
		if (url) {
			urlStyle = 'style="background-image:url(\''+url+'\')"';
		}
		html = '<i class="'+ cls +'"'+urlStyle+'></i>';
		return html;
	},
	folderChange: function() {
		this.tabs.detailsPanel.moreDetailsCache.clear();
		if (this.tabPanel.getActiveTab() == this.tabs.activityPanel) {
			this.tabs.activityPanel.load();
		}
	},
	showComments: function() {
		if (!this.isVisible()) {this.expand();}
		this.tabPanel.setActiveTab(2);
		return this;
	}
});