Ext.override(Ext.form.Field, {
	afterRender: function(){
		if (this.helpText) {
			var d = this.getErrorCt().createChild({tag: 'div', cls: 'infoButton'});
			new Ext.Button({
				iconCls: 'fa fa-info-circle silver',
				renderTo: d,
				handler: function(){new Ext.ux.prompt({text: this.helpText});},
				scope: this
			});
        }
		Ext.form.Field.superclass.afterRender.call(this);
		this.initValue();
		this.initEvents();
	}
});