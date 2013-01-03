//Handles all functionality for the Articles Admin module.
var articles = {
    Controller: function(){
        var _self = this;

        $(".loaderHolder").hide();
        //Enable the HotKey for spreadsheet navigation.
        var spreadsheetNav = new SpreadsheetNav($("#articles_admin_snippet")[0]);
        
        //Enable autocompletion.
        var autocomplete = new Autocomplete();
        
        //Enable autosave.
        var autosave = new Autosave();

        //Make the columns resizable in the snippet table.
        $("#articles_admin_snippet").resizableColumns();
        
        //Set up the snippet ordering in advance.
        this.orderBy = "article_id";
        this.orderDir = "asc";
        
        //Enable the M and V of the MVC.
        this.model = new articles.Model();
        this.view = new articles.View();
        
        //We'll be using the snippets array to measure the number of snippets we've loaded from the server.
        //There is probably a better way of doing this (namely, just incrementing an integer named numSnippets).
        this.snippets = [];
        
        //The number of articles in the database that are relevant to our current selection.
        this.num_articles;
        
        //The number of actual HTML rows in the snippet table. We'll be using this to check to see if we loaded any new rows
        //in the latest call for snippets.
        this.num_rows;
        
        //We'll be looping using timeouts, so this integer keeps track of what page of the snippeting we're on.
        this.i = 1;
        
        //The columns are resizable, and text selection mucks up the resizing, so we'll turn it off.
        $("th").each(function(){
            this.onselectstart = function(){return false;};
        });

        //The main handler for all our snippet loading.
        this.loadSnippets = function(refresh){
            console.log(refresh);
            //Refresh means "we're starting from scratch - empty the table completely and start the paging over from zero."
            if (refresh){
                //Start over from zero.
                _self.num_rows = 0;
                _self.snippets = [];
                //Prevent any rogue snippets from coming through the pipeline.
                window.clearTimeout(_self.timeout);
                //The parameters for the snippet request we'll be sending to the server.
    
                console.log(_self);

                var dObj = {
                    from:$("#fromYear").val()+"-"+$("#fromMonth").val()+"-01 00:00:00",
                    to:$("#toYear").val()+"-"+$("#toMonth").val()+"-01 00:00:00",
//                    month:$("#year").val()+"-"+$("#month").val()+"-01 00:00:00",
                    admin:"1"
                };
                //If the user has selected a client, add it to our request parameters.
                if ($("#clientSelect").val() !== ""){
                    dObj.client_id = $("#clientSelect").val();
                }
                
                //We'll first get the statistics for the user's selection. We'll load snippets based on those
                //statistics.
                _self.model.getData(window.root+"/api/Content/Articles/stats",function(data){
                    console.log(data);
                    if (data.total_articles > 1000){
                        alert("This request returns more than 1000 articles. Try narrowing it down a bit.");
                    } else {
                        //Display the statistics using a template.
                        $("#outputInfo").html($("#stats_snippet_template").tmpl(data.data));
                        //If the statistics request was successful and there are 1 or more articles relevant to our
                        //selection, store the total number of articles so we know when to stop loading snippets
                        //and start loading snippets.
                        if ((data.status == 'success')&&(data.data.total_articles > 0)){
                            _self.num_articles = data.data.total_articles;
                            _self.loadCallback(refresh);
                        } else {
                            //If the statistics request was unsuccessful or returned 0 articles, add a single blank row
                            //to the table using a template.
                            obj = $("#articles_admin_snippet_template").tmpl();
                            $("#articles_admin_snippet tbody").html(obj);
    
                            //Select the currently selected client in the blank row.
                            if ($("#clientSelect").val() !== "")
                                $(obj).find("select[name='client_id']").val($("#clientSelect").val());
                        }
                    }
                },dObj);
            } else {
                //If we're not starting over from scratch, bypass the other stuff and load more snippets.
                _self.loadCallback(refresh);
            }
        }

        this.loadCallback = function(refresh){
            console.log(_self.snippets);
            //If the user has selected a client, allow them to copy and import keywords.
            if ($("#clientSelect").val() !== "")
                $(".copy_link, #importKeywords").show();
            else
                $(".copy_link, #importKeywords").hide();
            
            //Set up the parameters object for our request for a snippet.
            var qobj = {
                from:$("#fromYear").val()+"-"+$("#fromMonth").val()+"-01 00:00:00",
                to:$("#toYear").val()+"-"+$("#toMonth").val()+"-01 00:00:00",
                page:(_self.snippets.length + 1),
                order_by:_self.orderBy,
                order_dir:_self.orderDir
            };
            //If the user has selected a client, add that client to our parameters.
            if ($("#clientSelect").val() !== "")
                qobj.client_id = $("#clientSelect").val()
            //Serialize our object to make ready the request.
            qstr = jQuery.param(qobj);
            console.log(qobj,qstr);
            //Execute the request.
            _self.model.getSnippet(
                //The URL:
                window.root+"/Content/articles_admin_snippet?"+qstr,
                //The Callback:
                function(data){
                    //Increment the tracker integer for our timeout loop.
                    _self.i++;
                    if (refresh) //If we're starting from scratch, make sure the table is emptied out.
                        $("#articles_admin_snippet tbody").html("");
                    //Display the received snippet.
                    $("#articles_admin_snippet tbody").append(data);
                    //This more or less increments the number of snippets we've received.
                    _self.snippets.push("string");
                    
                    //If the server returned data of some kind AND
                    //the server returned more than 0 rows AND
                    //we haven't yet loaded all of the articles that our statistics request said were relevant, then...
                    if (data.length > 0 && $("#articles_admin_snippet tbody tr").length > _self.num_rows && $("#articles_admin_snippet tbody tr").length < _self.num_articles){
                        //Update the number of rows we've loaded.
                        _self.num_rows = $("#articles_admin_snippet tbody tr").length;
                        //Set a timeout to request another snippet.
                        _self.timeout = setTimeout(_self.loadSnippets,50);
                    } else {
                        //We're done loading snippets. Make sure that there isn't a rogue timeout to load another snippet out there.
                        //This should be unnecessary, but... better safe than sorry.
                        window.clearTimeout(_self.timeout);
                    }
                    
                    //As a last measure, display the cost of each article.
                    $("td[name='word_count']").each(function(){_self.calcCost(this);});
                }
            );
        }
        
        //Start the snippet loading process, specifying that we are starting from scratch.
        this.loadSnippets(true);
        
        //Whenever the user makes a new selection, reload/refresh the snippets.
        $(document).on("change","#fromMonth,#fromYear,#toMonth,#toYear,#clientSelect",function(){
            if ((parseInt($("#fromYear").val()) < parseInt($("#toYear").val()))||(parseInt($("#fromYear").val()) == parseInt($("#toYear").val())&&($("#fromMonth").val() <= $("#toMonth").val()))){
                $(".selectorOutput").html("");
                _self.loadSnippets(true);
            } else {
                $(".selectorOutput").html("'From' must be less than or equal to 'To'");
            }
        });
        
        //The user requests to unassign an article.
        $(document).on("click",".unassign",function(){
            //Show our loading graphic.
            $(this).closest("td").find(".ajax_circle").show();
            var el = this;
            //Make a request to the server to unassign the article.
            _self.model.getData($(this).attr("href"),function(data){
                //Hide the loading graphic.
                $(".ajax_circle").hide();
                //Use a template to update the row to allow another assignment.
                $(el).closest("td").html($("#assign_admin_template").tmpl());
            });
        });
        
        //The user requests to sort using a different metric.
        $(document).on("click",".sortMe",function(){
            //Each sortMe icon has an order direction associated with it.
            //If this particular icon's order direction hasn't yet been set, set it to ascending.
            if (!this.orderDir)
                this.orderDir == "asc";
            //Set the Controller object's order direction to the icon's order direction.
            _self.orderDir = this.orderDir;
            
            //Reverse the icon's order direction in preparation for the next time the user clicks it.
            this.orderDir = (this.orderDir == "asc" ? "desc" : "asc");
            
            //The "rel" attribute contains the string name of the field we'll be sending to the server
            //to say "order by x".
            _self.orderBy = $(this).attr("rel");

            //Refresh the snippets.
            console.log(_self);
            _self.loadSnippets(true);
        });
        //The user requests to delete an article.
        $(document).on("click","input.delete",function(){
            var el = this;
            //Show the loading graphic.
            $(this).siblings(".ajax_circle").show();
            //If this is a real article in the database (namely, it has an ID associated with it),
            //send a server request to delete it from the database.
            if ($(this).closest("tr").find("input[name='article_id']").val() !== "")
                _self.model.getData($(this).attr('href'),function(data){$(el).closest("tr").remove();$(el).siblings(".ajax_circle").hide();});
            else
                //Otherwise, it's just a templated blank row added on the client. Remove it from the snippet table.
                $(this).closest("tr").remove();
        });
        
        //Whenever the user updates the word count for an article, update its cost.
        $(document).on("keyup","td[name='word_count']",function(){
            _self.calcCost(this,true);
        });
        //All of the project options have an asana project ID associated with them.
        //When the user selects a different project option, store that project's asana ID
        //in a hidden field in the row.
        $(document).on("change","select[name='project_id']",function(){
            $(this).siblings("input[name='asana_project_id']").val($(this).find("option:selected").attr("asana_project_id"));
        });
        //The user requests to assign an article to someone.
        $(document).on("click","span.assign",function(){
            //We need to store the element upon which the event was executed for later usage.
            var el = this;
            //Prepare all of the data about the article to send to the server.
            var data = $(this).closest("tr").serializeToJSON()[0];

            //This is a little ugly. We're just pulling data out of the row in the DOM so we can use it later
            var article_id = $(this).closest("tr").find("input[name='article_id']").val();
            var team_member_id = $(this).siblings("select[name='team_member_id']").val();
            var team_member_name = $(this).siblings("select[name='team_member_id']").find("option:selected").html();
            var asana_project_id = $(this).closest("tr").find("input[name='asana_project_id']").val();

            //Show the loading graphic.
            $(this).siblings(".ajax_circle").show();
            
            //Execute the server request.
            _self.model.getData(window.root+"/api/Content/Articles/assign_admin",
                function(data){
                    console.log(data,window.root);
                    var td = $(el).closest("td");
                    //Hide the loading graphic.
                    $(".ajax_circle").hide();
                    //Add some text before the user selection that says "Assigned to x [unassign]"
                    $(el).closest("td").prepend("Assigned to <span class='assigned_to_name'>"+team_member_name+"</span><br/>"+
                        "<span href='"+root+"/api/Content/Articles/unassign_admin?article_id="+article_id+"&asana_task_id="+data.data.asana_task_id+"' class='unassign link'>Unassign</span>");
                    //Get rid of the "assign button".
                    $(el).remove();
                    //Add a link to reassign the task to the cell.
                    $(td).append("<span class='reassign link' href='"+root+"/api/Content/Articles/reassign?article_id="+article_id+"&asana_team_member_id="+data.data.asana_team_member_id+"&asana_task_id="+data.data.asana_task_id+"'>Reassign</span>");
                },data);
        });
        $(document).on("click","span.reassign",function(){
            var el = this;
            
            //Storing some info from the DOM.
            var article_id = $(this).closest("tr").find("input[name='article_id']").val();
            var team_member_id = $(this).siblings("select[name='team_member_id']").val();
            var team_member_name = $(this).siblings("select[name='team_member_id']").find("option:selected").html();

            url = $(this).attr("href")+"&team_member_id="+team_member_id;
            //Show the loading graphic.
            $(this).siblings(".ajax_circle").show();
            _self.model.getData(url,
                function(data){
                    //Change the markup to reflect the change.
                    $(el).closest("td").find(".assigned_to_name").html(team_member_name);
                    $(el).siblings(".ajax_circle").hide();
                },data);
        });
        this.calcCost = function(el,cb){
            //Store the calculated cost, as based upon the provided element with all tags stripped out.
            var calculatedCost = _self.model.calculateCost($(el).html().replace(/(<([^>]+)>)/ig,""));
            
            //Display the cost.
            $(el).closest("tr").find("td[name='cost']").html(calculatedCost);
            //CB simply means we want to display the total cost of all the articles in the outputInfo div.
            if (cb){
                //Total the cost.
                var cost = 0;
                $("td[name='cost']").each(function(){cost += parseFloat($(this).html().replace(/(<([^>]+)>)/ig,""));});
                //Display it.
                $(".total_cost").html(cost.toFixed(2));
            }
        }
        
        //Make a server call to copy an articles roadmap for a client from one month to another.
        this.copy = function(params,refresh){
            console.log(params);
            $.ajax({
                url: root+"/api/Content/Articles/copy",
                data: params,
                dataType: 'json',
                success: function(data){
                    //Display the results of the request.
                    $(".copyoutput").html(data.data.join(", "));
                    
                    //The client should specify a refresh if they are copying to the month that is currently on display.
                    //This is because the copy request will change the list of articles for the currenlty displayed month.
                    if (refresh)
                        _self.loadSnippets(true);
                },
                complete: function(a,b){
                }
            });            
        }
        
        //Handle user requests to copy roadmaps.
        $(document).on("click",".copytobutton",function(){
            //Set up the parameters - who's the client and where are we coping the roadmap to and from?
            var params = {
                client_id: $("#clientSelect").val(),
                from_start: $("#fromYear").val()+"-"+$("#fromMonth").val()+"-01 00:00:00",
                from_end: $("#toYear").val()+"-"+$("#toMonth").val()+"-01 00:00:00",
                to: $("#copytoyear").val()+"-"+$("#copytomonth").val()+"-01 00:00:00"
            };
            //Execute the request.
            _self.copy(params);
        });
        $(document).on("click",".copyfrombutton",function(){
            //Set up the parameters - who's the client and where are we coping the roadmap to and from?
            var params = {
                client_id: $("#clientSelect").val(),
                to: $("#fromYear").val()+"-"+$("#fromMonth").val()+"-01 00:00:00",
                from_start: $("#copyfromyear").val()+"-"+$("#copyfrommonth").val()+"-01 00:00:00",
                from_end: $("#copyfromyear").val()+"-"+$("#copyfrommonth").val()+"-01 00:00:00"
            };
            //Execute the request.
            _self.copy(params,true);
        });
        
        //Imports all keywords that the client has ever used into the currently displayed month.
        $(document).on("click","#importKeywords",function(){
            //Set up the parameters - who's the client and what's the month.
            var params = {
                client_id: $("#clientSelect").val(),
                month: $("#year").val()+"-"+$("#month").val()+"-01 00:00:00"
            }
            //Execute the request. When it's finished, reload the snippets - the request will dump the keywords into
            //the database, so we'll need to see what new roadmap the server has thus created for us.
            $.ajax({
                url:root+"/api/Content/Articles/import_keywords",
                dataType:'json',
                data:params,
                success:function(data){
                    _self.loadSnippets(true);
                },
                complete:function(a,b){
                }
            });
        });

        //The user wants to add a new article.
        $(document).on("click",".addRowsButton",function(){
            for (i=0;i<$(".addRowsUpDown").val();i++){
                //Add a new row from a template.
                var obj = $("#articles_admin_snippet_template").tmpl();
                $("#articles_admin_snippet").append(obj);
                //Select the currently selected client in the new row.
                if ($("#clientSelect").val() !== "")
                    $(obj).find("select[name='client_id']").val($("#clientSelect").val());
            }
            
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
    },
    Model: function(){
        //Used to get HTML snippets from the server.
        this.getSnippet = function(url,cb){
            $(".loaderHolder,#ajaxLoading").show();
            $.ajax({
                url:url,
                success:function(data){
                    cb(data);
                },
                complete:function(a,b){
                    $(".loaderHolder,#ajaxLoading").hide();
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
        //Calculates cost of an article based on word count(num is word count)
        this.calculateCost = function(num){
            num = num * .02;
            num = isNaN(num) || num === '' || num === null ? 0.00 : num;
            return parseFloat(num).toFixed(2);
        }
    },
    //The view object should be doing a lot more heavy lifting than it is. It's just sloppy
    //programming that has made it the way it is.
    //Currently, it contains the event handlers to open and close the lightbox.
    View: function(){
        var _self = this;
        this.fromWithinLightbox = false;
        $(document).on("click",".copy_link",function(){
            $(".overlay, .lightbox_container, .closeout").show();
        });
        $(document).on("click",".lightbox",function(e){
            _self.fromWithinLightbox = true;
        });
        $(document).on("click",".overlay, .lightbox_container, .closeout",function(e){
            if (!_self.fromWithinLightbox){
                $(".overlay,.lightbox_container").hide();
            } else {
                _self.fromWithinLightbox = false;                    
            }
        });
    }
}
//Initialize the articles Controller.
$(document).ready(function(){
    var contr = new articles.Controller();
});