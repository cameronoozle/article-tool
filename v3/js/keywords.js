var keywords = {
    Controller: function(){
        var _self = this;
        $(document).on("change","#clientSelect",function(){
            window.location = window.root+"/SEO/keywords?client_id="+$(this).val();
        });
    },
    view: {},
    model: {}
}
$(document).ready(function(){
    var contr = new keywords.Controller();
});