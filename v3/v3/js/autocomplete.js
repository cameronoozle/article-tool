function Autocomplete(){
	var self = this;
	this.focusedInput;
	this.focusedLI;
	this.outputDisplayed;
	this.viableResults = new Array();
	this.listeners = new Array();
	this.navigating = false;
	
	this.addListener = function(obj){
		this.listeners.push(obj);
	}
	this.fireEvent = function(e){
		for (i=0;i<this.listeners.length;i++){
			if (typeof this.listeners[i]["on"+e] == 'function')
			this.listeners[i]["on"+e]();
		}
	}

	this.onNavKeyPressed = function(){
		this.navigating = true;
	}
	$(document).on("focus","td,input,textarea",function(){
		this.navigating = false;
	});

	this.focusLI = function(el){
		$(".autocompleteResult").css("background-color","white");
		$(el).css({"background-color":"#eeeeff"});
		this.focusedLI = el;
	}

	this.populateAutoComplete = function(data){
/*		self.viableResults = [];
		$("#output").html(data);
		if (data){
			self.showOutput();
		} else {
			self.hideOutput();
		}
		$(".autoCompleteResult").each(function(){
			self.viableResults.push($(this).html());
		});
		if (data !== "No autocomplete available.")
			self.fireEvent("AutocompleteFull");
		else
			self.fireEvent("AutocompleteEmpty");*/
	}
	
	this.showOutput = function(){
		$("#output").show();
		this.outputDisplayed = true;
	}
	
	this.hideOutput = function(){
		this.focusedLI = undefined;
		$(".autocompleteResults").hide();
		this.outputDisplayed = false;
		this.fireEvent("AutocompleteEmpty");
	}

	this.next = function(el){
		if ($(el).next().length > 0){
			return $(el).next()[0];
		} else {
			return $(el).siblings(":first");
		}
	}
	
	this.prev = function(el){
		if ($(el).prev().length > 0){
			return $(el).prev()[0];
		} else {
			return $(el).siblings(":last");
		}		
	}

	$(document).on('click','.autocompleteResult',function(){
		$(self.focusedInput).hval($(this).html());
		self.hideOutput();
	});
		
	$(document).on('focus',"input, td[autocomplete='true']",function(){
		$("#output").html("");
		self.hideOutput();
		if ($(this).attr("autocomplete") == "true")
			self.focusedInput = this;		
	});
	
	$(document).on('keydown',"input[autocomplete='true'], td[autocomplete='true']",function(e){
		var navCodes = [9,13,37,38,39,40];
		var el = this;
		if ((navCodes.indexOf(e.keyCode) == -1)&&(!this.navigating)){
			var inputEl = this;
			if ($(this).hval() !== "")
				$("#output").css({"min-width":$(this).width()+"px","left":$(this).offset().left+"px","top":$(this).offset().top+$(this).height()-13+"px"});
			var inputElement = this;
			var postVars = {mh:$(inputElement).attr("name")+"s",search_term:$(inputElement).hval().stripTags()};
			if ($("#clientSelect").val() !== "")
				postVars.client_id = $("#clientSelect").val();
			$.ajax({
				url:root+"/autocomplete.php",
				data:postVars,
				dataType:'json',
				type:'post',
				success:function(data){
					if (data.status=='success'){
						var str="";
						for (i=0;i<data.data.length;i++){
							str += "<li class='autocompleteResult'>"+data.data[i]+"</li>";
						}
						self.focusedLI = undefined;
						$(".autocompleteResults").css(
							{"width":$(el).outerWidth(),
							"left":$(el).offset().left,
							"top":$(el).offset().top + $(el).outerHeight(),
							"display":"inline-block"}
						).html(str);
					}
				},
				complete: function(a,b){
				}
			});
		}
	});
	
	$(document).on('keydown',"input[autocomplete='true'], td[autocomplete='true']",function(e){
		if (!this.navigating){
//			console.log(e.keyCode);
			self.viableResults.length = 0;
			switch (e.keyCode){
				case 40: //Down key.
					e.preventDefault();
					//If there is no focused LI.
					if (typeof self.focusedLI == 'undefined'){
						//Select the first result in the list.
						var li = $(".autocompleteResult:first")[0];
					} else {
						//Select the next result in the list after the highlighted one.
						var li = self.next(self.focusedLI);//$(self.focusedLI).next()[0];
					}
					self.focusLI(li);
					break;
				case 38: //Up key.
					//If there is no focused LI...
					if (typeof self.focusedLI !== 'undefined'){
						e.preventDefault();
						//Select the LI above this one.
						var li = self.prev(self.focusedLI);//$(self.focusedLI).prev()[0];
						//Highlight the selected LI.
						self.focusLI(li);
					}
					break;
				case 13: //Enter key.
					//If there is no focused LI...
					if (typeof self.focusedLI !== 'undefined'){
						e.preventDefault();
						//Click the focused LI.
						$(self.focusedLI).click();
						$(".autocompleteResults").hide();
					}
					break;
			}
		}
	});
	$(document).click(function(e){
		if (e.target !== self.focusedLI)
			self.hideOutput();
	});
	$(document).on("blur","input[mustselect='true'], td[mustselect='true']",function(){
		if (typeof self.viableResults !== 'undefined' && self.viableResults.length > 0 && self.viableResults.indexOf($(this).hval()) == -1)
			$(this).hval("");
		self.viableResults.length = 0;
	});
	$(document).on("blur","input,td",function(e){
//		console.log(e);
//		if (!$(e.target).hasClass("autocompleteResult"))
//			$(".autocompleteResults").hide();
	});
}