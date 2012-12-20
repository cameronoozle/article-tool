var permissions = {
    Controller: function(){
        var _self = this;
        this.model = new permissions.Model();
        $(document).on("click","input[name='submit']",function(){
            var data = $("tr").serializeToJSON();
            console.log(data);
            _self.model.getData(root+"/api/All/Users/set_permissions",JSON.stringify(data),function(d){
                console.log(d);
                if (d.status == 'success')
                    alert("Permissions saved!");
                else
                    alert("Something went wrong: "+d.data.slice(0,10).join(", "));
            });
        });
    },
    Model: function(){
        this.getData = function(url,data,cb){
            $.ajax({
                type:'post',
                url:url,
                data:data,
                dataType:'json',
                contentType:'application/json',
                success:function(d){
                    console.log(d);
                    cb(d);
                },
                complete:function(a,b){
                    console.log(a,b);
                }
            })
        }
    },
    View: function(){}
}
$(document).ready(function(){
    var contr = new permissions.Controller();
    console.log(contr);
});