FR = {
	UI: {}, changesSaved: true,
	init: function() {

		this.charsetSelector = new Ext.form.ComboBox({
			width: 160, height: 24, emptyText: FR.T('Charset for saving'),
			mode: 'local', triggerAction: 'all', editable: false,
			store: new Ext.data.ArrayStore({
				id: 0,
				fields: ['text'],
				data: charsets
			}),
			valueField: 'text',
			displayField: 'text', value: (charset || 'UTF-8'),
			listeners: {
				'select': function() {
					new Ext.ux.prompt({
						text: FR.T('Would you like to reload the file using the selected charset? Any unsaved changes will be lost.'),
						confirmHandler: function() {FR.changeCharset(this.getValue());},
						scope: this
					});
				}
			}
		});

		this.viewport = new Ext.Viewport({
			layout: 'fit',
			items: {
				layout: 'fit',
				html: '<div id="editor" style="position: absolute;top: 0;right: 0;bottom: 0;left: 0;"></div>',
				tbar: [{
					text: FR.T("Save"), cls: 'fr-btn-primary',
					handler: function(){this.save(false);}, scope: this
					},
					{
					text: FR.T("Save and close"), cls: 'fr-btn-primary', style: 'margin-left:5px',  hidden: !windowId,
					handler: function(){this.save(true);}, scope: this
					},
					{
						text: FR.T("Close"), style: 'margin-left:5px', hidden: !windowId,
						handler: function(){FR.closeWindow();}
					},
					{
						xtype: 'tbtext', id: 'status', text: '', style: 'margin-left:10px;'
					},
					'->',
					{
						xtype: 'button',
						enableToggle: true,
						text: FR.T('Word wrap'),
						toggleHandler: function(b, pressed) {
							FR.editor.getSession().setUseWrapMode(pressed);
						}
					},' ',
					this.charsetSelector, '&nbsp;'
				]
			},
			listeners: {
				'afterrender': function() {
					FR.editor = ace.edit("editor");
					var modelist = ace.require('ace/ext/modelist');
					var mode = modelist.getModeForPath(filename).mode;
					if (!mode) {mode = 'ace/mode/html'}
					FR.editor.$blockScrolling = 'Infinity';
					if (theme == 'dark') {
						FR.editor.setTheme("ace/theme/twilight");
					} else {
						FR.editor.setTheme("ace/theme/eclipse");
					}
					FR.editor.getSession().setMode(mode);
					FR.editor.getSession().setUseWrapMode(false);
					FR.editor.getSession().setValue(FR.getText());
					FR.editor.on('change', function() {
						FR.changesSaved = false;
						Ext.getCmp('status').setText('<span class="colorRed">'+FR.T('Unsaved changes')+'</span>');
					});
					FR.editor.commands.addCommand({
						name: 'Save',
						bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
						exec: function(editor) {FR.save();}
					});
				}
			}
		});
		window.onbeforeunload = function() {
			if (!FR.changesSaved) return FR.T('Discard the changes made?');
		};

	},
	changeCharset: function(charset) {
		var frm = document.createElement('FORM');
		frm.action = URLRoot+'/?module=custom_actions&action=code_editor&_popup_id='+windowId;
		frm.method = 'POST';
		var postArgs = [
			{name: 'path', value: path},
			{name: 'filename', value: filename},
			{name: 'charset', value: charset}
		];
		Ext.each(postArgs, function(param) {
			inpt = document.createElement('INPUT');
			inpt.type = 'hidden';
			inpt.name = param.name;
			inpt.value = param.value;
			frm.appendChild(inpt);
		});
		Ext.get('theBODY').appendChild(frm);
		frm.submit();
	},
	getText: function() {
		return Ext.get('textContents').dom.value;
	},
	closeWindow: function() {
		if (!FR.changesSaved) {
			new Ext.ux.prompt({text: FR.T('Discard the changes made?'),
				confirmHandler: function() {
					window.parent.FR.UI.popups[windowId].close();
				}});
			return false;
		}
		window.parent.FR.UI.popups[windowId].close();
	},
	save: function(close) {
		this.closeAfterSave = close;
		this.viewport.getEl().mask(FR.T('Saving...'));
		Ext.Ajax.request({
			url: URLRoot+'/?module=custom_actions&action=code_editor&method=saveChanges',
			params: {
				path: path,
				filename: filename,
				charset: this.charsetSelector.getValue(),
				textContents: FR.editor.getSession().getValue()
			},
			success: function(req) {
				this.viewport.getEl().unmask();
				try {
					var rs = Ext.util.JSON.decode(req.responseText);
				} catch (er){return false;}

				if (rs.success) {
					FR.changesSaved = true;
					Ext.getCmp('status').setText('');
				} else {
					FR.changesSaved = false;
				}
				if (rs.msg) {
					if (windowId) {
						window.parent.FR.UI.feedback(rs.msg);
					} else {
						Ext.getCmp('status').setText(rs.msg);
					}
				}
				if (rs.success && this.closeAfterSave) {
					this.closeWindow();
				}
			},
			scope: this
		});
	}
};