var articles = {
    Controller: function(){
        var _self = this;
        this.orderBy;
        this.orderDir = "asc";
        this.model = new articles.Model();
        this.view = new articles.View();
        this.snippets = [];
        this.num_articles = 1;
        this.num_rows;
        $("#articles_snippet").resizableColumns();
        
        this.loadCallback = function(refresh){
            var qstr = "?month="+$("#year").val()+"-"+$("#month").val()+"-01 00:00:00&page="+(_self.snippets.length + 1)+"&order_by="+_self.orderBy+"&order_dir="+_self.orderDir;
            if ($("#clientSelect").val() !== ""){
                qstr += "&client_id="+$("#clientSelect").val();
            }
            _self.model.getSnippet(
                window.root+"/Content/articles_snippet"+qstr,
                function(data){
                    if (refresh)
                        $("#articles_snippet tbody").html("");
                    $("#articles_snippet tbody").append(data);
                    console.log($("tr").length);
                    _self.snippets.push("string");
                    //If the snippet wasn't empty and
                    //we loaded some new rows with the last snippet and
                    //there are fewer rows than the total articles that we need to load, then perform the operation again.
                    if (data.length > 0 && $("#articles_snippet tbody tr").length > _self.num_rows && $("#articles_snippet tbody tr").length < _self.num_articles){
                        _self.num_rows = $("#articles_snippet tbody tr").length;
                        _self.timeout = setTimeout(_self.loadSnippets,50);
                    }
                }
            );
        }
        
        this.loadSnippets = function(refresh){
            //If we're refreshing, start from scratch: 
            if (refresh){
                var dObj = {month:$("#year").val()+"-"+$("#month").val()+"-01 00:00:00",admin:0};
                if ($("#clientSelect").val() !== ""){
                    dObj.client_id = $("#clientSelect").val();
                }
                _self.num_rows = 0;
                window.clearTimeout(_self.timeout);
                _self.snippets = [];
                _self.model.getData(window.root+"/api/Content/Articles/stats",function(data){
                    if ((data.status == 'success')&&(data.data.total_articles > 0)){
                        _self.num_articles = data.data.total_articles;
                        _self.loadCallback(refresh);
                    } else {
                        console.log("Not success, no more than 0 articles");
                        $("#articles_snippet tbody").html($("#articles_snippet_template").tmpl());
                    }
                },dObj);
            } else {
                _self.loadCallback(refresh);
            }
        }
        this.loadSnippets(true);
        $(document).on("change","#month,#year,#clientSelect",function(){
            _self.loadSnippets(true);
        });
        $(document).on("click",".assign",function(){
            $(this).closest("tr").find(".ajax_circle").show();
            var el = this;
            _self.model.getData($(this).attr("href"),function(data){
                console.log(data);
                $(".ajax_circle").hide();
                var str = "";
                if (data.status == 'success')
                    str = "This article is assigned to you. <span article_id='"+$(el).attr("article_id")+"' class='unassign link' href='"+window.root+"/api/Content/Articles/unassign?article_id="+$(el).attr("article_id")+"&asana_task_id="+data.data.asana_task_id+"'>Unassign</span>";
                else
                    str = "Something went wrong: "+data.data.join(", ")+" Please try again later.";
                $(el).closest("td").html(str);
            },jQuery.param({asana_project_id:$(el).closest("tr").find("input[name='asana_project_id']").val()}));
        });
        $(document).on("click",".unassign",function(){
            $(this).closest("tr").find(".ajax_circle").show();
            var el = this;
            _self.model.getData($(this).attr("href"),function(data){
                $(".ajax_circle").hide();
                var str = "";
                if (data.status == 'success')
                    str = "<span article_id='"+$(el).attr("article_id")+"' class='assign link' href='"+window.root+"/api/Content/Articles/assign?article_id="+$(el).attr("article_id")+"'>Assign to me</span>";
                else
                    str = "Something went wrong: "+data.data.join(", ")+" Please try again later.";
                $(el).closest("td").html(str);
            });
        });
        $(document).on("click",".sortMe",function(){
            if (!this.orderDir)
                this.orderDir = "asc";
            _self.orderDir = this.orderDir;
            this.orderDir = (this.orderDir == "asc" ? "desc" : "asc");
            _self.orderBy = $(this).attr("rel");
            _self.loadSnippets(true);
        });
        this.changeStatus = function(el){
            var article_glob = $(el).closest("tr").serializeToJSON()[0];
            console.log(article_glob);
            if (!article_glob.written) article_glob.written = "0";
            _self.model.getData(root+"/api/Content/Articles/change_status",function(data){
                console.log("Called Change Status API Method: ",data);
            },article_glob);
        }
        
        $(document).on("change","select[name='article_status_id']",function(){
            _self.changeStatus(this);
        });
        
        $(document).on("click","input[name='written']",function(){
            _self.changeStatus(this);
        });
        
        var autosave = new Autosave();
    },
    Model: function(){
        this.getSnippet = function(url,cb){
            $("#ajaxLoading").show();
            $.ajax({
                url:url,
                success:function(data){
                    cb(data);
                },
                complete:function(){
                    $("#ajaxLoading").hide();
                }
            });
        }
        this.getData = function(url,cb,data){
            if (!data) data = {};
            $.ajax({
                data:data,
                dataType:'json',
                url:url,
                success:function(data){
                    cb(data);
                    console.log(data);
                },
                complete:function(a,b){
                    console.log(a,b);
                }
            });
        }
    },
    View: function(){}
}
$(document).ready(function(){
    var contr = new articles.Controller();
});