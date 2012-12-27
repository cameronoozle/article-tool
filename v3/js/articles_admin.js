var articles = {
    Controller: function(){
        var spreadsheetNav = new SpreadsheetNav($("#articles_admin_snippet")[0]);
        var autocomplete = new Autocomplete();
        $("#articles_admin_snippet").resizableColumns();
        var _self = this;
        this.orderBy = "article_id";
        this.orderDir = "asc";
        this.model = new articles.Model();
        this.view = new articles.View();
        this.snippets = [];
        this.num_articles;
        this.num_rows;
        this.i = 1;
        $("th").each(function(){
            this.onselectstart = function(){return false;};
        });

        this.loadCallback = function(refresh){
            if ($("#clientSelect").val() !== "")
                $(".copy_link, #importKeywords").show();
            else
                $(".copy_link, #importKeywords").hide();
            var qstr = "?month="+$("#year").val()+"-"+$("#month").val()+"-01 00:00:00&page="+(_self.snippets.length + 1)+"&order_by="+_self.orderBy+"&order_dir="+_self.orderDir;
            if ($("#clientSelect").val() !== ""){
                qstr += "&client_id="+$("#clientSelect").val();
            }
            _self.model.getSnippet(
                window.root+"/Content/articles_admin_snippet"+qstr,
                function(data){
                    _self.i++;
                    if (refresh)
                        $("#articles_admin_snippet tbody").html("");
                    $("#articles_admin_snippet tbody").append(data);
                    _self.snippets.push("string");
                    console.log(data.length > 0, $("#articles_admin_snippet tbody tr").length > _self.num_rows, $("#articles_admin_snippet tbody tr").length < _self.num_articles);
                    if (data.length > 0 && $("#articles_admin_snippet tbody tr").length > _self.num_rows && $("#articles_admin_snippet tbody tr").length < _self.num_articles){
                        _self.num_rows = $("#articles_admin_snippet tbody tr").length;
                        _self.timeout = setTimeout(_self.loadSnippets,50);
                    } else {
                        window.clearTimeout(_self.timeout);
                    }
                    $("td[name='word_count']").each(function(){_self.calcCost(this);});
                }
            );
        }
        
        this.loadSnippets = function(refresh){
            if (refresh){
                window.clearTimeout(_self.timeout);
                var dObj = {month:$("#year").val()+"-"+$("#month").val()+"-01 00:00:00",admin:"1"};
                if ($("#clientSelect").val() !== ""){
                    dObj.client_id = $("#clientSelect").val();
                }
                _self.num_rows = 0;
                _self.snippets = [];
                _self.model.getData(window.root+"/api/Content/Articles/stats",function(data){
                    $("#outputInfo").html($("#stats_snippet_template").tmpl(data.data));
                    if ((data.status == 'success')&&(data.data.total_articles > 0)){
                        _self.num_articles = data.data.total_articles;
                        _self.loadCallback(refresh);
                    } else {
                        obj = $("#articles_admin_snippet_template").tmpl();
                        $("#articles_admin_snippet tbody").html(obj);
                        if ($("#clientSelect").val() !== "")
                            $(obj).find("select[name='client_id']").val($("#clientSelect").val());
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
        $(document).on("click",".unassign",function(){
            $(this).closest("td").find(".ajax_circle").show();
            var el = this;
            _self.model.getData($(this).attr("href"),function(data){
                $(".ajax_circle").hide();
                $(el).closest("td").html($("#assign_admin_template").tmpl());
            });
        });
        $(document).on("click",".sortMe",function(){
            if (!this.orderDir)
                this.orderDir == "asc";
            _self.orderDir = this.orderDir;
            this.orderDir = (this.orderDir == "asc" ? "desc" : "asc");
            _self.orderBy = $(this).attr("rel");
            _self.loadSnippets(true);
        });
        $(document).on("click","input.delete",function(){
            var el = this;
            $(this).siblings(".ajax_circle").show();
            if ($(this).closest("tr").find("input[name='article_id']").val() !== "")
                _self.model.getData($(this).attr('href'),function(data){$(el).closest("tr").remove();$(el).siblings(".ajax_circle").hide();});
            else
                $(this).closest("tr").remove();
        });
        $(document).on("keyup","td[name='word_count']",function(){
            _self.calcCost(this,true);
        });
        $(document).on("change","select[name='project_id']",function(){
            $(this).siblings("input[name='asana_project_id']").val($(this).find("option:selected").attr("asana_project_id"));
        });
        $(document).on("click","span.assign",function(){
            var el = this;
            var data = $(this).closest("tr").serializeToJSON()[0];
            var article_id = $(this).closest("tr").find("input[name='article_id']").val();
            var team_member_id = $(this).siblings("select[name='team_member_id']").val();
            var team_member_name = $(this).siblings("select[name='team_member_id']").find("option:selected").html();
            var asana_project_id = $(this).closest("tr").find("input[name='asana_project_id']").val();
            $(this).siblings(".ajax_circle").show();
            _self.model.getData(window.root+"/api/Content/Articles/assign_admin",
                function(data){
                    var td = $(el).closest("td");
                    $(".ajax_circle").hide();
                    $(el).closest("td").prepend("Assigned to <span class='assigned_to_name'>"+team_member_name+"</span><br/>"+
                        "<span href='"+root+"/api/Content/Articles/unassign_admin?article_id="+article_id+"&asana_task_id="+data.data.asana_task_id+"' class='unassign link'>Unassign</span>");
                    $(el).remove();
                    $(td).append("<span class='reassign link' href='"+root+"/api/Content/Articles/reassign?article_id="+article_id+"&asana_team_member_id="+data.data.asana_team_member_id+"&asana_task_id="+data.data.asana_task_id+"'>Reassign</span>");
                    $(td).find(".ajax_circle").hide();
                },data);
        });
        $(document).on("click","span.reassign",function(){
            var el = this;
            var article_id = $(this).closest("tr").find("input[name='article_id']").val();
            var team_member_id = $(this).siblings("select[name='team_member_id']").val();
            var team_member_name = $(this).siblings("select[name='team_member_id']").find("option:selected").html();
            url = $(this).attr("href")+"&team_member_id="+team_member_id;
            $(this).siblings(".ajax_circle").show();
            _self.model.getData(url,
                function(data){
                    $(el).closest("td").find(".assigned_to_name").html(team_member_name);
                    $(el).siblings(".ajax_circle").hide();
                },data);
        });
        this.calcCost = function(el,cb){
            var calculatedCost = _self.model.calculateCost($(el).html().replace(/(<([^>]+)>)/ig,""));
            console.log(calculatedCost);
            $(el).closest("tr").find("td[name='cost']").html(calculatedCost);
            if (cb){
                var cost = 0;
                $("td[name='cost']").each(function(){cost += parseFloat($(this).html().replace(/(<([^>]+)>)/ig,""));});
                $(".total_cost").html(cost.toFixed(2));
            }
        }
        var autosave = new Autosave();
        this.copy = function(params,refresh){
            $.ajax({
                url: root+"/api/Content/Articles/copy",
                data: params,
                dataType: 'json',
                success: function(data){
                    $(".copyoutput").html(data.data.join(", "));
                    if (refresh)
                        _self.loadSnippets(true);
                },
                complete: function(a,b){
                }
            });            
        }
        
        $(document).on("click",".copytobutton",function(){
            var params = {
                client_id: $("#clientSelect").val(),
                from: $("#year").val()+"-"+$("#month").val()+"-01 00:00:00",
                to: $("#copytoyear").val()+"-"+$("#copytomonth").val()+"-01 00:00:00"
            };
            _self.copy(params);
        });
        $(document).on("click",".copyfrombutton",function(){
            var params = {
                client_id: $("#clientSelect").val(),
                to: $("#year").val()+"-"+$("#month").val()+"-01 00:00:00",
                from: $("#copyfromyear").val()+"-"+$("#copyfrommonth").val()+"-01 00:00:00"
            };
            _self.copy(params,true);
        });
        $(document).on("click","#importKeywords",function(){
            var params = {
                client_id: $("#clientSelect").val(),
                month: $("#year").val()+"-"+$("#month").val()+"-01 00:00:00"
            }
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
        $(document).on("click",".addRowsButton",function(){
            for (i=0;i<$(".addRowsUpDown").val();i++){
                var obj = $("#articles_admin_snippet_template").tmpl();
                $("#articles_admin_snippet").append(obj);
                if ($("#clientSelect").val() !== "")
                    $(obj).find("select[name='client_id']").val($("#clientSelect").val());
            }
            
        });
    },
    Model: function(){
        this.getSnippet = function(url,cb){
            console.log(url);
            $("#ajaxLoading").show();
            $.ajax({
                url:url,
                success:function(data){
                    cb(data);
                },
                complete:function(a,b){
                    console.log(a,b);
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
                },
                complete:function(a,b){
                    console.log(a,b);
                }
            });
        }
        this.calculateCost = function(num){
            num = num * .02;
            num = isNaN(num) || num === '' || num === null ? 0.00 : num;
            return parseFloat(num).toFixed(2);
        }
    },
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
$(document).ready(function(){
    var contr = new articles.Controller();
});