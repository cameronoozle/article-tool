var seo_clients = {
    Controller: function(){
        var _self = this;
        var autosave = new SEOClientAutosave();
        this.calculateSEOBudget = function(row){
            var budget = $(row).find("td[name='budget']").html().replace(/(<([^>]+)>)/ig,"");
            var percent = parseFloat($(row).find("td[name='seo_percentage']").html().replace(/(<([^>]+)>)/ig,""));
            var output = budget * (percent/100);
            console.log(output);
//            return output;
            return (!isNaN(output) ? output.toFixed(2) : "");
        }
        $("#clients_table tbody tr").each(function(){
            $(this).find(".seo_budget").html(_self.calculateSEOBudget(this));
        });
        $(document).on("keyup","td[name='seo_percentage']",function(){
            var row = $(this).closest("tr");
            $(this).siblings(".seo_budget").html(_self.calculateSEOBudget(row));
        });
        $(document).on("change","#month,#year",function(){
            window.location = window.root+"/SEO/clients?month="+$("#year").val()+"-"+$("#month").val()+"-01 00:00:00";
        });
    },
    view: {},
    model: {}
}
$(document).ready(function(){
    var contr = new seo_clients.Controller();
});