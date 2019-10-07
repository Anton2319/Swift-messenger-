FR.editSettings = {};
FR.editSettings.formPanel = new FR.components.editForm({
	title: FR.T('Interface options'),
	layout: 'form', bodyStyle: 'padding:10px;',
	labelWidth: 150, autoScroll: true,
	defaults: {width: 500},
	items: [
		{
			xtype: 'fieldset',
			defaults: {width: 250},
			title: false,
			items: [
				{
					xtype: 'combo',
					fieldLabel: FR.T('Default language'),
					name: 'settings[ui_default_language]', hiddenName: 'settings[ui_default_language]',
					autoCreate: true, mode: 'local', editable: false,
					emptyText: FR.T('Select...'),
					displayField: 'name', valueField: 'id',
					triggerAction:'all', disableKeyFilter: true,
					value: FR.settings.ui_default_language,
					store: new Ext.data.SimpleStore({fields: ['id', 'name'], data: FR.languages}),
					helpText: FR.T('Applies only to first time visitors. Your language preference has already been saved to your browser. You will not see a change if you select a different option here and logout, unless you specifically change it from the login form.')
				},
				{
					xtype: 'checkbox',
					boxLabel: FR.T('Display language menu'),
					width: 400, value: 1,
					name: 'settings[ui_display_language_menu]', checked: parseInt(FR.settings.ui_display_language_menu)
				},
				{
					xtype: 'compositefield',
					fieldLabel: FR.T('Upload language file'),
					items:[
						{xtype: 'button', text: FR.T('Upload file..'), iconCls: 'fa fa-fw fa-upload', cls: 'fr-btn-primary fr-btn-smaller fr-btn-nomargin color-white',
							listeners: {
								'afterrender': function() {
									this.flow = new Flow({
										target: '?module=cpanel&section=settings&page=interface&action=upload_translation',
										singleFile: true, startOnSubmit: true,
										validateChunkResponse: function(status, message) {
											if (status != '200') {return 'retry';}
											try {var rs = Ext.util.JSON.decode(message);} catch (er){return 'retry';}
											if (rs) {if (rs.success) {return 'success';} else {return 'error';}}
										}
									});
									this.flow.on('fileSuccess', function(file, message) {
										try {var rs = Ext.util.JSON.decode(message);} catch (er){
											FR.feedback('Unexpected server reply: '+message);
										}
										if (rs && rs.msg) {FR.feedback(rs.msg);}
									});
									this.flow.on('fileError', function(file, message) {
										try {var rs = Ext.util.JSON.decode(message);} catch (er){
											FR.feedback('Unexpected server reply: '+message);
										}
										if (rs && rs.msg) {FR.feedback(rs.msg);}
									});
								}
							},
							handler: function() {
								this.flow.removeAll();
								this.flow.browseFiles();
							}
						},{xtype: 'displayfield'}
					]
				},
				{
					xtype: 'displayfield', style: 'color:gray', value: FR.T('Download more from <a href="%1" target="_blank">here</a>.').replace('%1', 'https://docs.filerun.com/translating_filerun')
				}
			]
		},
		{
			xtype: 'fieldset',
			defaults: {width: 250},
			items: [
				{
					xtype: 'radiogroup', vertical: true, columns: 1,
					fieldLabel: FR.T('Default display mode'),
					items: [
						{boxLabel: '<i class="fa fa-fw fa-th gray"></i> '+FR.T('Thumbnails'), name: 'settings[ui_default_view]', inputValue: 'thumbnails', checked: (FR.settings.ui_default_view == 'thumbnails')},
						{boxLabel: '<i class="fa fa-fw fa-list gray"></i> '+FR.T('Detailed list'), name: 'settings[ui_default_view]', inputValue: 'list', checked: (FR.settings.ui_default_view == 'list')},
						{boxLabel: '<i class="fa fa-fw fa-picture-o gray"></i> '+FR.T('Photos'), name: 'settings[ui_default_view]', inputValue: 'photos', checked: (FR.settings.ui_default_view == 'photos')},
						{boxLabel: '<i class="fa fa-fw fa-music gray"></i> '+FR.T('Music'), name: 'settings[ui_default_view]', inputValue: 'music', checked: (FR.settings.ui_default_view == 'music')}
					]
				},
				{xtype: 'displayfield'},
				{
					xtype: 'textfield',
					fieldLabel: FR.T('Thumbnail size'), width: 50,
					name: 'settings[thumbnails_size]', value: FR.settings.thumbnails_size
				},
				{
					xtype: 'textfield',
					fieldLabel: FR.T('Photos thumbnail size'), width: 50,
					name: 'settings[ui_photos_thumbnail_size]', value: FR.settings.ui_photos_thumbnail_size
				},
				{
					xtype: 'checkbox',
					fieldLabel: FR.T('Detailed list'),
					boxLabel: FR.T('Show thumbnails'),
					value: 1,
					name: 'settings[ui_thumbs_in_detailed]', checked: parseInt(FR.settings.ui_thumbs_in_detailed)
				}
			]
		},
		{
			xtype: 'fieldset',
			defaults: {width: 250},
			items: [
				{
					xtype: 'checkboxgroup', vertical: true, columns: 1,
					fieldLabel: FR.T('Show media library'),
					items: [
						{boxLabel: FR.T('Photos'), name: 'settings[ui_media_folders_photos_enable]', inputValue: 1, checked: parseInt(FR.settings.ui_media_folders_photos_enable)},
						{boxLabel: FR.T('Music'), name: 'settings[ui_media_folders_music_enable]', inputValue: 1, checked: parseInt(FR.settings.ui_media_folders_music_enable)}
					]
				},
				{
					xtype: 'combo',
					fieldLabel: FR.T('Double-click files action'),
					name: 'settings[ui_double_click]', hiddenName: 'settings[ui_double_click]',
					autoCreate: true, mode: 'local', editable: false,
					emptyText: FR.T('Select...'),
					displayField: 'name', valueField: 'id',
					triggerAction:'all', disableKeyFilter: true,
					value: FR.settings.ui_double_click,
					store: new Ext.data.SimpleStore({fields: ['id', 'name'], data: [
							['preview', FR.T('open preview')],
							['downloadb', FR.T('open in browser')],
							['download', FR.T('prompt to save')],
							['showmenu', FR.T('display contextual menu')]
						]})
				},
				{
					xtype: 'checkbox',
					fieldLabel: FR.T('Enable download cart'),
					value: 1,
					name: 'settings[ui_enable_download_cart]', checked: parseInt(FR.settings.ui_enable_download_cart)
				},
				{
					xtype: 'checkbox',
					fieldLabel: FR.T('Enable file rating'),
					value: 1,
					name: 'settings[ui_enable_rating]', checked: parseInt(FR.settings.ui_enable_rating)
				}
			]
		}
	],
	tbar: [
		{
			text: FR.T('Save changes'), cls: 'fr-btn-primary',
			ref: 'saveBtn',
			handler: function() {
				var editForm = this.ownerCt.ownerCt;
				editForm.submitForm({
					url: FR.URLRoot+'/?module=cpanel&section=settings&action=save',
					maskText: 'Saving changes...',
					params: editForm.form.getFieldValues()
				});
			}
		}
	]
});
Ext.getCmp('appTab').add(FR.editSettings.formPanel);
Ext.getCmp('appTab').doLayout();