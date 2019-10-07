FR.components.activityPanel = Ext.extend(Ext.ux.ListPanel, {
	paginated: true,
	locateOnSel: true,
	title: '<i class="fa fa-fw fa-globe"></i>',

	initComponent: function() {
		this.tabTip = FR.T('Folder Activity');
		this.store = new Ext.data.JsonStore({
			url: '?module=filelog&section=ajax&page=all',
			root: 'records', totalProperty: 'totalCount',
			fields: [
				{name:'date_added', type:'date'},'time_ago','path','filename', 'filename_short','isFolder','icon','thumb','details','n'
			],
			listeners: {
				'load': function() {this.updateStatus(0);},
				scope: this
			}
		});
		this.listViewCfg = {
			emptyText: 'No activity records found for this folder',
			columns: [],
			tpl: new Ext.XTemplate(
				'<tpl for="rows">' +
					'<dl>' +
						'<dt class="eventItem">' +
								'{[this.getIconHTML(values)]}' +
								'<div class="txt">' +
									'<div class="fn" ext:qtip="{filename}"><tpl if="n &gt; 0"><i class="fa fa-bolt icon-red new"></i></tpl> {filename}</div>' +
									'<div class="d">{details}</div>' +
									'<div><span class="t" title="{date_added}">{time_ago}</span></div>' +
								'</div>' +
						'</dt>' +
					'</dl>' +
				'</tpl>',
				{getIconHTML: this.getIconHTML}
			)
		};
		FR.components.activityPanel.superclass.initComponent.apply(this, arguments);
	},
	load: function() {
		this.path = FR.currentPath;
		this.store.setBaseParam('path', this.path);
		this.store.load();
	},
	updateStatus: function(newCount, add) {
		var alert;
		if (add) {newCount += this.titleNumber;}
		if (add || newCount > this.titleNumber && this.titleNumber > 0) {
			if (FR.localSettings.get('sound-notif', Settings.sound_notification ? 'enabled' : 'disabled') == 'enabled') {
				FR.audioNotification();
				alert = true;
			}
		}
		this.setTitleNumber(newCount, alert);
	},
	onRender: function() {
		this.on('activate', function() {this.load();}, this);
		FR.components.activityPanel.superclass.onRender.apply(this, arguments);
	}
});