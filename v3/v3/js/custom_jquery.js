//jQuery Custom Functions

(function($){$.selectedText = function(){
var txt = '';
if (window.getSelection)
txt = window.getSelection();
else if (document.getSelection)
txt = document.getSelection();
else if (document.selection)
txt = document.selection.createRange().text;
return $(txt);
}})(jQuery);

if (!console){
	var funcs = ["log","dir","warn","debug","info","error","timeEnd","trace","group","groupEnd","dirxml"];
	var console = {};
	for (i=0;i<funcs.length;i++){
		console[funcs[i]] = function(){};
	}
}
if (!String.prototype.stripTags)
	String.prototype.stripTags=function(){return this.replace(/<[^<>]+>/, '');};
(function($){$.fn.hval = function(str){
	if (typeof str !== 'undefined'){
		this.each(function(){
			(/input|select/i.test(this.nodeName.toLowerCase()) ? $(this).val(str) : $(this).html(str));
		});
	}
	return (/input|select|textarea/i.test(this[this.length - 1].nodeName) ? $(this[this.length - 1]).val() : $(this[this.length - 1]).html().replace(/(<([^>]+)>)/ig,""));
}})(jQuery);

(function($){$.fn.elementIndex = function(parentSelector){
	var par = (parentSelector ? $(this.closest(parentSelector)[0]) : this.closest()[0]);
	var thisObj = (this.tagName ? this : this[0]);
	return $(par).find(thisObj.nodeName.toLowerCase()).index(thisObj);
}})(jQuery);

(function($){$.fn.hasElementIndex = function(parentSelector,index){
	var toOutput = [];
	var par = (parentSelector ? $(this.parents(parentSelector)[0]) : this.parent());
	this.each(function(){
		if ($(this).elementIndex(parentSelector) == index){
			toOutput.push(this);
		}
	});
	return toOutput;
}})(jQuery);


(function($) { $.fn.serializeAnything = function() {
	var toReturn    = [];
	var els = $(this).find(':input').get();
	$.each(els, function() {
		if (this.name && !this.disabled && (this.checked || /select|textarea/i.test(this.nodeName) || /text|hidden|password/i.test(this.type))) { var val = $(this).val(); toReturn.push( encodeURIComponent(this.name) + "=" + encodeURIComponent( val ) );}}); return toReturn.join("&").replace(/%20/g, "+");}})(jQuery);

(function ($){$.fn.attrRegEx = function(regex){
	var attr;
	this.each(function(){
		var elAttrs = this.attributes;
		for (i=0;i<elAttrs.length;i++){
			if (regex.test(elAttrs[i].name)){
				attr = $(this).attr(elAttrs[i].name);
				return false;
			}
		}
	});
	return attr;
}})(jQuery);

function isValidInput(el){
	return (typeof $(el).attr("name") !== 'undefined' &&
			$(el).attr("disabled") !== "true" &&
			$(el).attr("noserialize") !== "true" &&
			$(el).hval() !== $(el).attr("orig") &&
				(el.checked ||
				 /select|textarea/i.test(el.nodeName) ||
				 /text|hidden|password|submit/i.test(el.type) ||
				 $(el).attrRegEx(/contenteditable/i) == "true")
			);
}

(function($){$.fn.serializeToJSON = function(){
	var toReturn = [];
	this.each(function(){
		var elObject = {};
		var els = $(this).find("input,select,textarea,[contenteditable='true']");
		$(els).each(function(){
			if (isValidInput(this)){
				elObject[$(this).attr("name")] = $(this).hval();
			}
		});
		if (!jQuery.isEmptyObject(elObject)) toReturn.push(elObject);
	});
	return toReturn;
}})(jQuery);

(function($){ $.fn.getEmptyRow = function(){
	var newrow = document.createElement("tr");
	$(newrow).html(this.find("tr:last").html());
	$(newrow).find("input").val("");
	$(newrow).find("input[type='checkbox']").removeAttr("checked");
	$(newrow).find("li:has(input)").remove();
	return newrow;
}})(jQuery);

function inRange(checkValue,targetValue,range){
	if (range == 0)
		return (checkValue == range);
	else
		return ((checkValue < (targetValue + range))&&(checkValue > (targetValue - range)));
}

(function($){$.fn.hasOffsetLeft = function(x,leway,returnCount){
	if (!leway) leway = 0;
	var toOutput = [];
	var i=0;
	this.each(function(){
		if (inRange($(this).offset().left,x,leway)){
			toOutput.push(this);
			if ((returnCount)&&(i >= returnCount))
				return false;
		}
	});
	return toOutput;
}})(jQuery);

(function($){$.fn.hasOffsetRight = function(x,leway,returnCount){
	if (!leway) leway = 0;
	var toOutput = [];
	var i=0;
	this.each(function(){
		if (inRange(($(this).offset().left + $(this).outerWidth()),x,leway))
			toOutput.push(this);
			if ((returnCount)&&(i >= returnCount))
				return false;
	});
	return toOutput;
}})(jQuery);

function resizableColumnsExist(x,y,parentTable){
	return (
			(y>parentTable.offset().top)&&
			(y<(parentTable.offset().top + parentTable.outerHeight()))&&
			((parentTable.find("th, td").hasOffsetRight(x,4,1).length > 0))
			);
}

function preventCrush(lh,rh,x,tw){
	return (((x - $(lh).offset().left) > 0)&&((tw - (x - $(lh).offset().left)) > 0));
}

(function($){$.fn.resizableColumns = function(){
	var moveable;
	var parentTable = this;
	var leftHandCols;
	var rightHandCols;
	var totalWidth;
	this.parent().on("mousedown",function(e){
		moveable = resizableColumnsExist(e.pageX,e.pageY,parentTable);
		leftHandCols = parentTable.find("th,td").hasOffsetRight(e.pageX,4);
		totalWidth = $(leftHandCols).outerWidth()+$(rightHandCols).outerWidth();
		document.onselect = function(){return false;}
	});
	this.parent().on("mouseup",function(){
		moveable = false;
		totalWidth = null;
		document.onselect = function(){return true;}
	});
	this.parent().on("mousemove",function(e){
		$(this).css("cursor",(resizableColumnsExist(e.pageX,e.pageY,parentTable) ? "e-resize" : "default"));
		if (moveable){
			$(leftHandCols).trigger("columnResized");
			var colWidth = (e.pageX - $(leftHandCols).offset().left) - ($(leftHandCols).css("padding") ? (parseInt($(leftHandCols).css("padding"))*2) : 0);
			var rowWidth = 0;
			$(parentTable).find("tr:first th").each(function(){
				rowWidth += $(this).outerWidth();
			});
			rowWidth = rowWidth - $(leftHandCols).outerWidth() + colWidth;
			$(leftHandCols).each(function(){
				$(this).css("width",colWidth + "px");
			});
			$(parentTable).css("width",rowWidth + "px");
		}
	});
	return this;
}})(jQuery);

(function($){$.fn.textareaResize = function(){
	this.attr("cols",Math.floor(this.width()/12));
}})(jQuery);

/*(function($){$.fn.columnsSortable = function(){
	var orders = [];
	$(this.find("tr")[0]).find("th").each(function(){
		orders[$(this).elementIndex("tr")] = "asc";
	});
	var targetTable = this;
	$(document).on("click",".sortMe",function(e){
		var myIndex = $($(this).parents("th")[0]).elementIndex("tr");
		targetTable.sortByColumn(myIndex,orders[myIndex]);
		orders[myIndex] = (orders[myIndex] == "asc" ? "desc" : "asc");
	});
}})(jQuery);

(function($){$.fn.sortByColumn = function(colIndex,order,callback){
	var toOutput = [];
	this.find("tr").each(function(){
		if ($(this).find("td")[colIndex]){
			toOutput.push($(this).find("td")[colIndex]);
		}
	});
	if (callback){
		toOutput.sort(callback);
	} else {
		toOutput.sort(function(a,b){
			var ainput = $(a).find("input[type='text'], input[type='checkbox']");
			var binput = $(b).find("input[type='text'], input[type='checkbox']");
			if ((ainput.length > 0)&&(binput.length > 0)){
				var first = ainput.val().toLowerCase();
				var second = binput.val().toLowerCase();
				switch ($(ainput).attr("type")){
					case "text":
						var myfirst = (first !== second ? (first > second ? 1 : -1) : 0);
						return myfirst;
						break;
					case "checkbox":
						if (($(ainput).is(":checked"))&&(!$(binput).is(":checked")))
							return 1;
						else if ((!$(ainput).is(":checked"))&&($(binput).is(":checked")))
							return -1;
						else
							return 0;
						break;
					default:
						return 0;
				}
				return 0;
			} else {
				if ((ainput.length > 0)&&(binput.length == 0)) return 1;
				if ((binput.length > 0)&&(ainput.length == 0)) return -1;
				return 0;
			}
		});
	}
	for (i=0;i<toOutput.length;i++){
		if (order == "asc"){
			this.children("tbody").append($(toOutput[i]).parents("tr")[0]);
		} else {
			this.children("tbody").prepend($(toOutput[i]).parents("tr")[0]);
		}
	}
	return true;
}})(jQuery);*/
function numbers(n1,n2){
    return (!isNaN(n1)&&!isNaN(n2));
}
function val(obj,ind){
    var td = $($(obj).find("td")[ind]);
    var input = td.find("input");
    if (input.length == 0) return td.html();
    switch (input.attr("type")){
	case "checkbox":
	    return (input.is(":checked") ? 1 : 0);
	    break;
	case "text":
	    return input.val();
	    break;
	default:
	    return 0;
	    break;
    }
}
(function($){$.fn.sortByColumn = function(colIndex,order){
    var rows = [];
    this.find("tbody tr").each(function(){
	rows.push(this);
    });
    rows.sort(function(a,b){
	a = val(a,colIndex);
	b = val(b,colIndex);
	if (numbers(a,b)){
	    if (parseInt(a) == parseInt(b))
		return 0;
	    else if (parseInt(a) > parseInt(b))
		return 1;
	    else
		return -1;
	} else {
	    if (a == b)
		return 0;
	    else if (a > b)
		return 1;
	    else
		return -1;
	}
    });
    for (i=0;i<rows.length;i++){
	if (order == "asc")
	    $(this).append(rows[i]);
	else
	    $(this).prepend(rows[i]);
    }
}})(jQuery);
(function($){$.fn.columnsSortable = function(){
    this.find(".sortMe").each(function(){
	this.sortOrder = "asc";
    });
    $(this).on("click",".sortMe",function(){
	var sortme = this;
	$(this).closest("table").sortTable($(this).closest("th").elementIndex("tr"),sortme.sortOrder);
	this.sortOrder = (this.sortOrder == "asc" ? "desc" : "asc");
    });
}})(jQuery);




(function($){$.fn.jsonifyText = function(){
	var splitted = $("#input").val().split(/\n/);
	for (z=0;z<splitted.length;z++){
		if (splitted[z].split(/\t/).length > 0){
			var titles = splitted[z].split(/\t/);
			break;
		}
	}
	var toOutput = [];
	for (i=z+1;i<splitted.length;i++){
		var rowObj = {};
		var subarray = splitted[i].split(/\t/);
		if (subarray.length >= titles.length){
			for (x=0;x<titles.length;x++){
				rowObj[titles[x]] = subarray[x];
			}
			toOutput.push(rowObj);
		}
	}
	return toOutput;
}})(jQuery);

function hasChildren(obj){
	return (obj.childNodes.length > 0);
}

(function($){$.fn.unfilteredFind = function(){
	var toOutput = [];
	this.children().each(function(){
		toOutput.push(this);
		if (hasChildren(this)){
			var kids = $(this).unfilteredFind();
			for (i=0;i<kids.length;i++){
				toOutput.push(kids[i]);
			}
		}		
	});
	return toOutput;
}})(jQuery);

(function($){$.fn.right = function(){
	return this.offset().left + this.width();
}})(jQuery);
(function($){$.fn.bottom = function(){
	return this.offset().top + this.height();
}})(jQuery);

(function($){$.fn.scrollOverflow = function(){
	var _self = this, xscroll = false, yscroll = false;
	var kids = this.unfilteredFind();
	for (i=0;i<kids.length;i++){
		var kid = kids[i];
		jQuery.each({right:"x",bottom:"y"},function(n,v){
			if ($(kid)[n]() > $(_self)[n]()){
				_self.css("overflow-"+v,"scroll");
				_self[v+"scroll"] = true;
			}
		});
		if (_self.xscroll && _self.yscroll){
			return _self;
		}
	}
	if (!xscroll) _self.css("overflow-x","auto");
	if (!yscroll) _self.css("overflow-y","auto");
	return this;
}})(jQuery);
(function($){$.fn.spreadsheetNav = function(){
	var deadlyCodes = [13,37,38,39,40,9];
	var isEditing = false;
	var autocomplete = false;
	function target(obj){
		var myTarget = ($(obj)[0].nodeName.toLowerCase() == "td" ?
				(
					$(obj).attr("contentEditable") == "true" ?
						$(obj) :
						$(obj).find("input")
				) :
				$($(obj).parents("td")[0])
			);
		return myTarget;
	}
	function td(obj){
		return ($(obj)[0].nodeName.toLowerCase() == "td" ? $(obj) : $($(obj).parents("td")[0]));
	}
	$(document).on("autocompleted",function(){
		autocomplete = true;
	});
	$(document).on("autocompleteEmpty",function(){
		autocomplete = true;
	});
	this.on("keydown","td,input",function(e){
		var thisObj = td(this);
		if (deadlyCodes.indexOf(e.keyCode) !== -1){
			jQuery.each({"next":"13,40","prev":"38"},function(n,v){
				codes = v.split(",");
				for (i=0;i<codes.length;i++){
					if ((e.keyCode == codes[i])&&(autocomplete == false)){
						try {
							var tarEl = $(thisObj).closest("tr")[n]()[0].childNodes[$(thisObj).elementIndex("tr")];
							e.preventDefault();
							isEditing = false; autocomplete = false;
							target(tarEl).focus();
							return false;
						} catch (err) {}
					}
				}
			});
			jQuery.each({"next":"39","prev":"37"},function(n,v){
				if ((e.keyCode == v)&&(!isEditing)){
					autocomplete = false;
					e.preventDefault();
					var nextEls = $(thisObj)[n]();
					var trEls = $(thisObj).closest("tr")[n]().children("td");
					var tarEl = (nextEls.length > 0 ? nextEls[0] : (n == "next" ? trEls[0] : trEls[trEls.length - 1]));
					if (typeof tarEl !== 'undefined') target(tarEl).focus();
					return false;
				}
			});
		} else {
			if ($(this).attr("type") !== "checkbox")
				isEditing = true;
			else
				return false;
		}
	});
	$(this).on("keydown","input[type='checkbox']",function(e){
		if (e.keyCode == 89){
			(!$(this).is(":checked") ? $(this).attr("checked","checked") : $(this).removeAttr("checked"));
		} else if (deadlyCodes.indexOf(e.keyCode) == -1){
			$(this).removeAttr("checked");
		}
	});
}})(jQuery);

function clear(kids,par){
	var els = new Array();
	for (i=0;i<kids.length;i++){
		if (typeof kids[i] !== 'undefined'){
			if (kids[i].nodeType == 3){
				if (kids[i].nodeValue.trim() == ""){
					par.removeChild(kids[i]);
				}
				else{
					kids[i].nodeValue == kids[i].nodeValue.trim();
				}
			} else if (kids[i].childNodes.length > 0){
				els.push(kids[i]);
			} else {
				return;
			}
		}
	}
	return;
}

(function($){$.fn.clearEmptyNodes = function(){
	this.each(function(){
		clear(this.childNodes,this);
	});
	return this;
}})(jQuery);
(function($){
	$.fn.toggleVal = function(){
		$(this).each(function(){
			switch (this.nodeName.toLowerCase()){
				case "input":
					$(this).attr("orig",$(this).attr("value"));
					break;
				case "span":
					$(this).attr("orig",$(this).html());
					break;
			}
		});
		$(this).blur(function(){
			switch(this.nodeName.toLowerCase()){
				case "input":
					$(this).val($(this).val() == "" ? $(this).attr("orig") : $(this).val());
					break;
				case "span":
					$(this).html($(this).html() == "" ? $(this).attr("orig") : $(this).html());
			}
		});
		$(this).focus(function(){
			switch(this.nodeName.toLowerCase()){
				case "input":
					$(this).val($(this).val() == $(this).attr("orig") ? "" : $(this).val());
					break;
				case "span":
					$(this).html($(this).html() == $(this).attr("orig") ? "" : $(this).html());
			}
		});
	}
})(jQuery);

/*(function($){
    jQuery.each({"Clear":"","Fill":""},function(v,c){
		$.fn["toggle"+v] = function(){
			this.filter(function(){return (typeof $(this).attr("orig") == 'undefined');}).each(function(){$(this).attr("orig",$(this).attr("value"));});
			this.each(function(){
				$(this).val(
					v == "Clear" ?
					($(this).val() == $(this).attr("orig") ? "" : $(this).val()) :
					($(this).val() == "" ? $(this).attr("orig") : $(this).val())
				)
			});
        }
    })
})(jQuery);*/
