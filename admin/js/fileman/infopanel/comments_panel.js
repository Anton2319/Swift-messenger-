FR.components.commentsPanel = Ext.extend(Ext.ux.ListPanel, {
	path: false,
	title: '<i class="fa fa-fw fa-comments-alt"></i>',
	layout: 'border',

	initComponent: function() {
		this.tabTip = FR.T('Comments');
		this.store = new Ext.data.JsonStore({
			url: URLRoot+'/?module=comments&section=ajax&page=load',
			root: 'comments', totalProperty: 'totalCount', id: 'id',
			fields: ['id', 'date_added', 'timer', 'uid', 'val', 'username', 'fullName', 'self', 'followup'],
			listeners: {
				'load': function(store) {
					var d = store.reader.jsonData;
					if (d.error) {
						this.listView.getTemplateTarget().update('<div class="x-list-message">'+d.error+'</div>');
					}
					FR.utils.applyFileUpdates(d.path, {comments: d.totalCount});
					this.listView.innerBody.parent().scroll('b', 10000, true);
				},
				scope: this
			}
		});

		this.btns = {
			print: new Ext.Button({
				iconCls: 'fa fa-fw fa-print',
				handler: this.print, scope: this
			})
		};
		var tpl = '<div class="comments">'+
			'<div class="x-clear"></div>' +
			'<tpl for="rows">'+
				'<div class="comment <tpl if="self">own</tpl> <tpl if="followup">followup</tpl>">'+
					'<tpl if="!uid"><div class="name">{fullName}</div></tpl>' +
					'<tpl if="uid"><div class="name">{fullName}</div></tpl>' +
					'<div class="x-clear"></div>'+
					'<tpl if="!followup"><div class="avatar" ext:qtip="{fullName}&lt;br&gt; {date_added:date("l, F jS, Y \\\\a\\\\t h:i A")}" style="background-image:url(a/?uid={uid})"></div></tpl>' +
					'<div class="text"><div class="inner">';
			if (User.perms.write_comments){
				tpl +=  '<div class="removeBtn"><a onclick="FR.UI.deleteComment(\''+this.id+'\', \'{id}\')"><i class="fa fa-close"></i></a></div>';
			}
				tpl +=  '{val}'+
					'</div></div>'+

				'</div>'+
				'<div class="x-clear"></div>'+
			'</tpl>'+
		'</div>'+
		'<div class="x-clear"></div>';

		this.listViewCfg = {
			emptyText: 'No comments available for this item',
			region: 'center',
			flex: 1, autoScroll: true, columns: [],
			tpl: tpl
		};
		this.inputBox = new Ext.form.TextArea({
			flex: 1,
			emptyText: FR.T('Write a comment...'), enableKeyEvents: true,
			listeners: {
				'render': function() {
					this.inputBox.el.set({'placeholder': FR.T('Write a comment...')});
				},
				'keydown': function(field, e) {
					if (e.getKey() == e.ENTER) {if (!e.shiftKey) {this.addComment();}}
				}, scope: this
			}
		});
		this.writePanel = new Ext.Panel({
			region: 'south', layout: 'fit', cls: 'commentField',
			height: 84, layoutConfig: { align: "stretch" },
			items: this.inputBox,
			tbar: {style: 'padding:1px 2px;', items: ['->' , this.btns.print]}
		});

		this.extraItem = this.writePanel;
		Ext.apply(this, {
			listeners: {
				'activate': function(p) {
					p.active = true;
					if (this.isSet()) {
						this.inputBox.focus();
						this.load();
					}
				},
				'deactivate': function(p) {p.active = false;}
			}
		});
		FR.components.commentsPanel.superclass.initComponent.apply(this, arguments);
	},
	isSet: function() {
		return this.path;
	},
	setItem: function(item) {
		var path = item.data.path;
		var cCount = item.data.comments;
		if (path == this.path) {return this;}
		this.store.removeAll(true);
		if (this.active) {
			this.listView.refresh();
		}
		if (FR.utils.canAddComments()) {
			this.writePanel.show();
		} else {
			this.writePanel.hide();
		}
		this.doLayout(true);
		this.setTitleNumber(cCount);
		this.path = path;
		this.store.setBaseParam('path', path);
		if (!this.collapsed) {
			this.load();
		}
		return this;
	},
	load: function() {
		if (this.active) {this.store.load();}
	},
	addComment: function() {
		var c = this.inputBox.getValue();
		if (c.length > 0) {
			this.action('add', {comment: c});
		}
	},
	print: function() {
		window.open(URLRoot+'/?module=comments&page=print&path='+encodeURIComponent(this.path));
	},
	deleteComment: function(cid) {
		new Ext.ux.prompt({
			text: FR.T('Are you sure you want to remove the comment?'),
			confirmHandler: function() {
				this.action('remove', {commentId: cid});
			}, scope: this
		});
	},
	action: function(action, params) {
		FR.actions.process({
			url: '/?module=comments&section=ajax&page='+action,
			params: Ext.apply(params, this.store.baseParams),
			loadMsg: 'Loading...',
			successCallback: function() {
				this.inputBox.reset();
				this.load();
			}, scope: this
		});
	}
});
FR.UI.deleteComment = function(panelId, cId) {Ext.getCmp(panelId).deleteComment(cId);};