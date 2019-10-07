(function (factory) {
  if (typeof define === 'function' && define.amd) {
    define(['jquery'],factory)
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory(require('jquery'));
  } else {
    factory(window.jQuery)
  }
}
(function ($) {
  $.extend($.summernote.keyMap, {
    pc: {
      'CTRL+S': 'Save'
    },
    mac: {
      'CMD+S': 'Save'
    }
  });
  $.extend($.summernote.plugins, {
    'save':function (context) {
      this.events = {
        'summernote.change':function (we, e) {
	        FR.changesSaved = false;
	        Ext.getCmp('status').setText('<span class="colorRed">'+FR.T('Unsaved changes')+'</span>');
        },
        'summernote.keydown':function (we, e) {
          if(e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
            e.preventDefault();
            FR.save();
          }
        }
      };
      $(window).bind('beforeunload',function () {
        if (!FR.changesSaved) return 'Changes you made may not be saved.';
      });
    }
  });
}));