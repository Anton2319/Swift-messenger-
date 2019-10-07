function compareAlphaNum(a, b) {
	var aa = a.split(/(\d+)/);
	var bb = b.split(/(\d+)/);
	for (var x = 0; x < Math.max(aa.length, bb.length); x++) {
		if (aa[x] != bb[x]) {
			var cmp1 = (isNaN(parseInt(aa[x], 10))) ? aa[x] : parseInt(aa[x], 10);
			var cmp2 = (isNaN(parseInt(bb[x], 10))) ? bb[x] : parseInt(bb[x], 10);
			if (cmp1 == undefined || cmp2 == undefined) {
				return aa.length - bb.length;
			} else {
				if (typeof cmp1 === 'string') {
					return cmp1.localeCompare(cmp2);
				}
				return (cmp1 < cmp2) ? -1 : 1;
			}
		}
	}
	return 0;
}
if (!String.prototype.repeat) {
	String.prototype.repeat = function( num ){
		return new Array( num + 1 ).join( this );
	}
}