function SpreadsheetNav(table){
	var _self = this;
	this.table = table;
	this.deadlyCodes = [13,37,38,39,40,9];
	this.autocompleteFull = false;
	this.isEditing = false;
	this.listeners = new Array();
	$(this.table).clearEmptyNodes();
	
	this.onAutocompleteFull = function(){
		//Disable up, down, and enter navigation.
		this.autocompleteFull = true;
	}
	this.onAutocompleteEmpty = function(){
		//Enable all navigation.
		this.autocompleteFull = false;
	}
	$(document).on("focus","td,input",function(){
		_self.autocompleteFull = false;
		_self.isEditing = false;
	});
	this.addListener = function(obj){
		this.listeners.push(obj);
	}
	this.fireEvent = function(e){
		for (i=0;i<this.listeners.length;i++){
			if (typeof this.listeners[i]["on"+e] == 'function')
			this.listeners[i]["on"+e]();
		}
	}



	this.target = function(obj){		//This function figures out which element we should focus.
		var myTarget = ($(obj)[0].nodeName.toLowerCase() == "td" ?	//If the object in question is a TD.
				(
					$(obj).attr("contentEditable") == "true" ?
						$(obj) :									//If the TD is content editable, return it.
						($(obj).find("input,textarea").length > 0 ? $(obj).find("input,textarea") : $(obj).children())	//Otherwise, return the input or image children of the TD.
				) :
				$($(obj).parents("td")[0])							//If the object is not a TD, return its TD parent.
			);
		return myTarget;	
	}
	this.td = function(obj){
		//Similar to target. If the input element is a TD, return it. Otherwise return its parent TD.
		return ($(obj)[0].nodeName.toLowerCase() == "td" ? $(obj) : $($(obj).parents("td")[0]));
	}
	
	$(this.table).on("keydown","td,input,select",function(e){
		//If the keyed-down element is not a TD, focus on its parent TD.
		var thisObj = _self.td(this);
		//If the key pressed is up, down, left, right, enter, or tab.
		if (_self.deadlyCodes.indexOf(e.keyCode) !== -1){
			if (!_self.autocompleteFull){
			if (((e.keyCode == 13)||(e.keyCode == 40))&&(!_self.autocompleteFull)){
				_self.fireEvent("NavKeyPressed");
				console.log($(thisObj).closest("tr").next());
				if ($(thisObj).closest("tr").next().length > 0){
					var tarEl = $(thisObj).closest("tr").next()[0].childNodes[$(thisObj).elementIndex("tr")];
					e.preventDefault();
					_self.isEditing = false;
					console.log(_self.target(tarEl));
					_self.target(tarEl).focus();
					return false;
				}
			} else if (e.keyCode == 38){
				_self.fireEvent("NavKeyPressed");
				console.log($(thisObj).closest("tr").prev());
				if ($(thisObj).closest("tr").prev().length > 0){
					var tarEl = $(thisObj).closest("tr").prev()[0].childNodes[$(thisObj).elementIndex("tr")];
					e.preventDefault();
					_self.isEditing = false;
					console.log(_self.target(tarEl));
					_self.target(tarEl).focus();
					return false;
				}
			}			
			}			
			
			
			
			//Enter and down arrow take you next cell down. Up arrow takes you one cell up.
/*			jQuery.each({"next":"13,40","prev":"38"},function(n,v){
				codes = v.split(",");
				for (i=0;i<codes.length;i++){
					//With up, down, and enter keys, we care whether the autocomplete is full, but not whether they're editing. Editing or no,
					//it's time to start navigating.
					if ((e.keyCode == codes[i])&&(!_self.autocompleteFull)){
						_self.fireEvent("NavKeyPressed");
						console.log($(thisObj).closest("tr")[n]());
						try {
							if ($(thisObj).closest("tr")[n]().length > 0){
								var tarEl = $(thisObj).closest("tr")[n]()[0].childNodes[$(thisObj).elementIndex("tr")];
								e.preventDefault();
								_self.isEditing = false;
								console.log(_self.target(tarEl));
								_self.target(tarEl).focus();
								return false;
							}
						} catch (err) {}
					}
				}
			});*/
			//Left and right take you to the cells to the left or right of the focused cell.
			jQuery.each({"next":"39","prev":"37"},function(n,v){
				//With right and left keys, we care whether the user is editing, but not whether the autocomplete is full (since these keys have nothing
				//to do with autocomplete functionality). 
				if ((e.keyCode == v)&&(!_self.isEditing)){
					_self.fireEvent("NavKeyPressed");
					e.preventDefault();
					var nextEls = $(thisObj)[n]();		//If we're not at the end of the row, nextEls will include more than zero cells.
					var trEls = $(thisObj).closest("tr")[n]().children("td");	//If we are at the end of the row, we'll need to have the cells of the next row in hand.
					var tarEl = (nextEls.length > 0 ? nextEls[0] : (n == "next" ? trEls[0] : trEls[trEls.length - 1]));
					console.log(_self.target(tarEl));
					if (typeof tarEl !== 'undefined') _self.target(tarEl).focus();
					return false;
				}
			});
		} else {
			//If the key pressed is not relevant to navigation, the user is editing the cell, so we want to shut off navigation.
			if ($(this).attr("type") !== "checkbox")
				_self.isEditing = true;
			else
				return false;
		}
	});
	//This is just kind of handy and not really relevant to navigation. If you're focused on a checkbox, pressing "y" (as in "yes") will toggle the checkbox
	//checked and unchecked.
	$(_self.table).on("keydown","input[type='checkbox']",function(e){
		if (e.keyCode == 89){
			(!$(this).is(":checked") ? $(this).attr("checked","checked") : $(this).removeAttr("checked"));
		} else if (_self.deadlyCodes.indexOf(e.keyCode) == -1){
			$(this).removeAttr("checked");
		}
	});
}