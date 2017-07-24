/**
 * Created by Administrator on 2017/2/12.
 */


$(document).ready(function(){
    $('#kw').on('input',function(){
        for(key in data){
//            $(this).val()==key&&console.log(data[key]);//输入时获取对应国家的编号
            $(this).val()==data[key];//输入时获取对应国家的编号
        }
    });
    $(document).keydown(function(e){
        e = e || window.event;
        var keycode = e.which ? e.which : e.keyCode;
        if(keycode == 38){
            if(jQuery.trim($("#append").html())==""){
                return;
            }
            movePrev();
        }else if(keycode == 40){
            if(jQuery.trim($("#append").html())==""){
                return;
            }
            $("#kw").blur();
            if($(".item").hasClass("addbg")){
                moveNext();
            }else{
                $(".item").removeClass('addbg').eq(0).addClass('addbg');
            }

        }else if(keycode == 13){
            if(jQuery.trim($("#kw").text())!==""){
                return;
            }
            dojob();
        }
    });

    var movePrev = function(){
        $("#kw").blur();
        var index = $(".addbg").prevAll().length;
        if(index == 0){
            $(".item").removeClass('addbg').eq($(".item").length-1).addClass('addbg');
        }else{
            $(".item").removeClass('addbg').eq(index-1).addClass('addbg');
        }
    }

    var moveNext = function(){
        var index = $(".addbg").prevAll().length;
        if(index == $(".item").length-1){
            $(".item").removeClass('addbg').eq(0).addClass('addbg');
        }else{
            $(".item").removeClass('addbg').eq(index+1).addClass('addbg');
        }

    }

    var dojob = function(){
        $("#kw").blur();
        console.log($(".addbg").attr("value")); //在这里回车时获取所属国家编号
        var value = $(".addbg").text();
        if(value!==""){
            $("#kw").val(value);
            $("#append").hide().html("");
        }
        console.log(e);

    }
});
function getContent(obj){
    var kw = jQuery.trim($(obj).val());
    if(kw == ""){
        $("#append").hide().html("");
        return false;
    }
    var html = "";
    for(key in data){
        if (key.indexOf(kw) >= 0) {
            html = html + "<div class='item'  value='"+data[key]+"'  onmouseenter='getFocus(this)' onClick='getCon(this);'>" + key + "</div>"
        }
    }

    if(html != ""){
        $("#append").show().html(html);
    }else{
        $("#append").hide().html("");
    }
}
function getFocus(obj){
    $(".item").removeClass("addbg");
    $(obj).addClass("addbg");
}
function getCon(obj){
    var value = $(obj).text();
    $("#kw").val(value);
    $("#append").hide().html("");
    console.log($(obj).attr("value"));//鼠标点击时获取当前国家所在编号
}