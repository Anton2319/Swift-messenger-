Ext.namespace('Ext.ux.form');
Ext.ux.form.TagsField = Ext.extend(Ext.ux.form.SuperBoxSelect, {
	mode: 'remote', minChars: 3, multiSelectMode: false,
	pinList: false,
	removeValuesFromStore: false, supressClearValueRemoveEvents: true,
	preventMultipleRemoveEvents: true,
	valueField: 'v', displayField: 'd', name: 'tags',
	initComponent: function() {
		Ext.apply(this, {
			store: new Ext.data.JsonStore({
				autoDestroy: true,
				url: URLRoot+'/?module=metadata&section=tags&page=autocomplete',
				root: 'tags',
				fields: ['v', 'd']
			}),
			listeners : {
				'newitem':function(sb, v) {
					sb.addNewItem({id: Ext.id(), v: v, d: Ext.util.Format.htmlEncode(v)});
				}
			}
		});
		Ext.ux.form.TagsField.superclass.initComponent.apply(this, arguments);
	},
	hasValue: function(val) {
		var has = false;
		this.items.each(function(item){
			if(item.value.toLowerCase() == val.toLowerCase()){
				has = true;
				return false;
			}
		},this);
		return has;
	}
});
Ext.reg('tagsfield', Ext.ux.form.TagsField);