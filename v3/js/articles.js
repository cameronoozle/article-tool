//Handles all functionality for the Articles module.
var articles = {
    Controller: function(){
        var _self = this;

        //Set up the snippet ordering in advance.
        this.orderBy;
        this.orderDir = "asc";

        //Enable the M and V of the MVC.
        this.model = new articles.Model();
        this.view = new articles.View();

        //We'll be using the snippets array to measure the number of snippets we've loaded from the server.
        //There is probably a better way of doing this (namely, just incrementing an integer named numSnippets).
        this.snippets = [];

        //The number of articles in the database that are relevant to our current selection.
        this.num_articles = 1;

        //The number of actual HTML rows in the snippet table. We'll be using this to check to see if we loaded any new rows
        //in the latest call for snippets.
        this.num_rows;

        //Make the columns resizable in the snippet table.
        $("#articles_snippet").resizableColumns();

        this.loadSnippets = function(refresh){
            //Refresh means "we're starting from scratch - empty the table completely and start the paging over from zero."
            if (refresh){
                //The parameters for the snippet request we'll be sending to the server.
                var dObj = {month:$("#year").val()+"-"+$("#month").val()+"-01 00:00:00",admin:0};
                //If the user has selected a client, add it to our request parameters.
                if ($("#clientSelect").val() !== ""){
                    dObj.client_id = $("#clientSelect").val();
                }
                //Start over from zero.
                _self.num_rows = 0;
                _self.snippets = [];
                //Prevent any rogue snippets from coming through the pipeline.
                window.clearTimeout(_self.timeout);
                //We'll first get the statistics for the user's selection. We'll load snippets based on those
                //statistics.
                _self.model.getData(window.root+"/api/Content/Articles/stats",function(data){
                    //If the statistics request was successful and there are 1 or more articles relevant to our
                    //selection, store the total number of articles so we know when to stop loading snippets
                    //and start loading snippets.
                    if ((data.status == 'success')&&(data.data.total_articles > 0)){
                        _self.num_articles = data.data.total_articles;
                        _self.loadCallback(refresh);
                    } else {
                        //If the statistics request was unsuccessful or returned 0 articles, add a single blank row
                        //to the table using a template.
                        $("#articles_snippet tbody").html($("#articles_snippet_template").tmpl());
                    }
                },dObj);
            } else {
                //If we're not starting over from scratch, bypass the other stuff and load more snippets.
                _self.loadCallback(refresh);
            }
        }
        
        this.loadCallback = function(refresh){
            //Set up the parameters object for our request for a snippet.
            var qobj = {
                month:$("#year").val()+"-"+$("#month").val()+"-01 00:00:00",
                page:(_self.snippets.length + 1),
                order_by:_self.orderBy,
                order_dir:_self.orderDir
            };

            //If the user has selected a client, add that client to our parameters.
            if ($("#clientSelect").val() !== "")
                qobj.client_id = $("#clientSelect").val()

            //Serialize our object to make ready the request.
            qstr = jQuery.param(qobj);
            console.log(qstr);
            _self.model.getSnippet(
                //The URL:
                window.root+"/Content/articles_snippet?"+qstr,
                //The Callback:
                function(data){
                    if (refresh)
                        $("#articles_snippet tbody").html("");
                    $("#articles_snippet tbody").append(data);
                    _self.snippets.push("string");
                    //If the server returned data of some kind AND
                    //the server returned more than 0 rows AND
                    //we haven't yet loaded all of the articles that our statistics request said were relevant, then...
                    if (data.length > 0 && $("#articles_snippet tbody tr").length > _self.num_rows && $("#articles_snippet tbody tr").length < _self.num_articles){
                         //Update the number of rows we've loaded.
                       _self.num_rows = $("#articles_snippet tbody tr").length;
                        //Set a timeout to request another snippet.
                        _self.timeout = setTimeout(_self.loadSnippets,50);
                    }
                }
            );
        }
        
        //Start the snippet loading process, specifying that we are starting from scratch.
        this.loadSnippets(true);
        //Whenever the user makes a new selection, reload/refresh the snippets.
        $(document).on("change","#month,#year,#clientSelect",function(){
            _self.loadSnippets(true);
        });
        $(document).on("click",".assign",function(){
            $(this).closest("tr").find(".ajax_circle").show();
            var el = this;
            _self.model.getData($(this).attr("href"),function(data){
                $(".ajax_circle").hide();
                var str = "";
                if (data.status == 'success')
                    str = "This article is assigned to you. <span article_id='"+$(el).attr("article_id")+"' class='unassign link' href='"+window.root+"/api/Content/Articles/unassign?article_id="+$(el).attr("article_id")+"&asana_task_id="+data.data.asana_task_id+"'>Unassign</span>";
                else
                    str = "Something went wrong: "+data.data.join(", ")+" Please try again later.";
                $(el).closest("td").html(str);
            },jQuery.param({asana_project_id:$(el).closest("tr").find("input[name='asana_project_id']").val()}));
        });
        //The user requests to unassign an article.
        $(document).on("click",".unassign",function(){
            //Show our loading graphic.
            $(this).closest("tr").find(".ajax_circle").show();
            var el = this;
            //Make a request to the server to unassign the article.
            _self.model.getData($(this).attr("href"),function(data){
                //Hide the loading graphic.
                $(".ajax_circle").hide();
                var str = "";

                //Update the row to allow another assignment.
                //Need to move this to a template to separate the content from the execution.
                if (data.status == 'success')
                    str = "<span article_id='"+$(el).attr("article_id")+"' class='assign link' href='"+window.root+"/api/Content/Articles/assign?article_id="+$(el).attr("article_id")+"'>Assign to me</span>";
                else
                    str = "Something went wrong: "+data.data.join(", ")+" Please try again later.";
                $(el).closest("td").html(str);
            });
        });

        //The user requests to sort using a different metric.
        $(document).on("click",".sortMe",function(){
            //Each sortMe icon has an order direction associated with it.
            //If this particular icon's order direction hasn't yet been set, set it to ascending.
            if (!this.orderDir)
                this.orderDir = "asc";
            //Set the Controller object's order direction to the icon's order direction.
            _self.orderDir = this.orderDir;
            //Reverse the icon's order direction in preparation for the next time the user clicks it.
            this.orderDir = (this.orderDir == "asc" ? "desc" : "asc");
            //The "rel" attribute contains the string name of the field we'll be sending to the server
            //to say "order by x".
            _self.orderBy = $(this).attr("rel");
            //Refresh the snippets.
            _self.loadSnippets(true);
        });

        //Make a request to the server to change an article's status (both their binary, written status and their int, string status)
        this.changeStatus = function(el){
            //Gather up all information about the article whose status has changed.
            var article_glob = $(el).closest("tr").serializeToJSON()[0];
            //If the written checkbox isn't checked, serializeToJSON won't pick it up. In that case, we'll assign it a zero.
            if (!article_glob.written) article_glob.written = "0";

            //This request executes silently - the user never knows when it begins or finishes.
            _self.model.getData(root+"/api/Content/Articles/change_status",function(data){
            },article_glob);
        }
        
        //If the user selects a new article status or clicks the 'written' checkbox, make a server call to
        //change the article's status.
        $(document).on("change","select[name='article_status_id']",function(){
            _self.changeStatus(this);
        });
        $(document).on("click","input[name='written']",function(){
            _self.changeStatus(this);
        });
        
        var autosave = new Autosave();
    },
    Model: function(){
        //Used to get HTML snippets from the server.
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
        //Used to get JSON data from the API.
        this.getData = function(url,cb,data){
            if (!data) data = {};
            $.ajax({
                data:data,
                dataType:'json',
                url:url,
                success:function(data){
                    cb(data);
                },
                complete:function(a,b){
                }
            });
        }
    },
    //The view object should be doing a lot more heavy lifting than it is. It's just sloppy
    //programming that has made it empty as it is.

    View: function(){}
}
$(document).ready(function(){
    var contr = new articles.Controller();
});