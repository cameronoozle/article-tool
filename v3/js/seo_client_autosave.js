function SEOClientAutosave(){
    var _self = this;
    $(document).on("keyup","td,input",function(){$(this).closest("tr").attr("data","true");});
    this.save = function(){
        var els = $("tr[data='true']");
        els.attr("data","false");
        var data = els.serializeToJSON();
        if (els.length > 0){
            $.ajax({
                url:window.root+"/api/All/Clients/save",
                data:JSON.stringify(data),
                dataType:'json',
                contentType:"application/json",
                type:'post',
                success:function(data){
                    console.log(data);
                },
                complete:function(a,b){
                    console.log(a,b);
                }
            });
        }
    }
    this.turnOffAutosave = function(){
            window.clearInterval(_self.save);
    }
    
    this.turnOnAutosave = function(){
            this.save = window.setInterval(_self.save,5000);
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
    $(document).on('click','.turnOffAutosaveButton, .turnOnAutosaveButton',function(){
            self.toggleAutosave(this);
    });
    $(document).on("click","#saveButton",function(){
            self.save();
    });
    this.turnOnAutosave();
}