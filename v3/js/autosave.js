function Autosave(){
	var self = this;
	//Once an input element is no longer empty, we want to set its parent row's data attribute to true so that the autosaver will save it.
	this.dataTrue = function(el){
		$(el).parents("tr").attr("data","true");		
	}
	this.turnOffAutosave = function(){
		window.clearInterval(self.autosave);
	}
	
	this.turnOnAutosave = function(){
		this.autosave = window.setInterval(self.save,5000);
	}
	
	//Turns autosave either on or off and changes the type of the autosave button accordingly.
	this.toggleAutosave = function(button){
		if ($(button).attr("class") == "turnOffAutosaveButton"){
			self.turnOffAutosave();
			$(button).attr("class","turnOnAutosaveButton");
			$(button).val("Turn On Autosave");
		} else {
			self.turnOnAutosave();
			$(button).attr("class","turnOffAutosaveButton");
			$(button).val("Turn Off Autosave");
		}
	}
	
	this.deleteRow = function(row){
		$(row).remove();
		var article_id = $(row).find("input[name='article_id']").val();
		if (article_id !== ""){
			api({
				endpoint:"articles",
				method:"delete",
				data:"article_id="+article_id,
				success:function(data){},
				complete:function(a,b){}
			});
		}
	}
	
	//Cruises through each row that contains data and saves its contents.
	this.save = function(){
		console.log("saving");
		self.saveTags();
		var tosave = $("tr[data='true']");
		if (tosave.length > 0){
			$(tosave).attr("data","false");
			var tosend = $(tosave).serializeToJSON();
			for (i=0;i<tosend.length;i++){
				tosend[i].month = $("#year").val()+"-"+$("#month").val()+"-01 00:00:00";
				if (!tosend[i].client_id)
					tosend[i].client_id = $("#clientSelect").val();
				if (($("#outputtable > table").attr("id") == "articles")||($("#outputtable > table").attr("id") == "clients"))
					tosend[i].month = $("#year").val()+"-"+$("#month").val()+"-01 00:00:00";
				if ($("#outputtable > table").attr("id") == "clients"){
					tosend[i].seo_percentage = tosend[i].seo_percentage.replace("%","");
					tosend[i].seo_spending = tosend[i].seo_spending.replace("$","");					
				}
			}
			var ajaxObject = {
				url:window.root+"/api/Content/Articles/save",
				type:"post",
				dataType:'json',
				data:JSON.stringify(tosend),
				contentType:'application/json',
				success:function(data){
					console.log(data);
					if (data.status == "success"){
						var rows = (data.data.rows ? data.data.rows : data.data);
						for (i=0;i<rows.length;i++){
							if (rows[i].client_checklist_writing_team_pairing_id)
								$(tosave[i]).find("input[name='client_checklist_writing_team_pairing_id']").val(rows[i].client_checklist_writing_team_pairing_id);
							$(tosave[i]).find("input[name='article_id']").val(rows[i].article_id);
							$(tosave[i]).find("input[name='keyword_id']").val(rows[i].keyword_id);
							if (rows[i].budget_id)
								$(tosave[i]).find("input[name='budget_id']").val(rows[i].budget_id);
						}
					}
				},
				complete:function(a,b,c){
					console.log(a,b);
					$("#saveOutput").html("Autosave completed.");
					$("#saveOutput").fadeIn(2000,function(){$("#saveOutput").fadeOut(2000)});
				}
			}
			$.ajax(ajaxObject);
		}
	}
	
	this.saveTags = function(){
		var myRows = [];
		var tosave = $(".tag[data='true']");
		$(tosave).attr("data","false");
		$(tosave).each(function(){
			var info = $(this).serializeToJSON()[0];
			info.keyword_id = $(this).parents("tr").find("input[name='keyword_id']").val();
			myRows.push(info);
		});
		if (myRows.length > 0){
			$.ajax({
				url:"api/keywords/save_tags",
				type:"post",
				dataType:'json',
				data:JSON.stringify(myRows),
				contentType:'application/json',
				success:function(data){
					if (data.status == "success")
						for (i=0;i<data.data.length;i++){
							$(tosave[i]).find("input[name='keyword_tag_pairing_id']").val(data.data[i].keyword_tag_pairing_id);
						}
				},
				complete:function(a,b,c){
					$("#saveOutput").html("Autosave completed.");
					$("#saveOutput").fadeIn(2000,function(){$("#saveOutput").fadeOut(2000)});
				}
			});
		}
	}
	
	$(document).on('click','.turnOffAutosaveButton, .turnOnAutosaveButton',function(){
		self.toggleAutosave(this);
	});
	$(document).on('change click keyup',"input,td[contentEditable='true'][sloppy!='true'],select,textarea",function(){
		console.log("click!");
		self.dataTrue(this);
	});
	$(document).on('blur',"td[sloppy='true']",function(){
		self.dataTrue(this);
		$(this).attr("noserialize","false");
	});
	$(document).on('focus',"td[sloppy='true']",function(){
		$(this).attr("noserialize","true");
	});
	$(document).on('keyup',"input[name='tag']",function(){
		$(this).parent(".tag").attr("data","true");
	});
	$(document).on("click",".deleteRow",function(e){
		if (e.target === this)
			self.deleteRow($(this).parents("tr")[0]);
	});
	$(document).on("click","#saveButton",function(){
		self.save();
	});
	this.turnOnAutosave();
}