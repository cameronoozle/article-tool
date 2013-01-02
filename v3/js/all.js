//In reality, this object only handles the report-a-bug module.
//Handles opening and closing the lightbox and submitting the report-a-bug form.
var oozle = {
    Controller:function(){
        var _self = this;
        _self.fromWithinLightbox = false;
        $(document).on("click",".bug_report_link",function(){
            $(".bug_overlay, .bug_lightbox_container").show();
        });
        $(document).on("click",".bug_lightbox",function(e){
            _self.fromWithinLightbox = true;
        });
        $(document).on("click",".bug_overlay, .bug_lightbox_container, .closeout",function(e){
            if (!_self.fromWithinLightbox){
                $(".bug_overlay,.bug_lightbox_container").hide();
            } else {
                _self.fromWithinLightbox = false;
            }
        });
        $(document).on("submit","form[name='bugreport']",function(e){
            e.preventDefault();
            $(this).ajaxSubmit({
                dataType:'json',
                success:function(data){
                    if (data.status == 'success')
                        $(".bugdiv").append(data.data[0]);
                    else
                        $(".bugdiv").append("Something went wrong");
                    console.log(data);
                },
                complete:function(a,b){
                    console.log(a,b);
                }
            });
            return false;
        });
    },
    view:{},
    model:{}
}