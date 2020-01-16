/**
 * Created by DEV on 3/5/2018.
 */

function bs_input_file() {
    $(".input-file").before(
        function() {
            if ( ! $(this).prev().hasClass('input-ghost') ) {
                var element = $("<input type='file' class='input-ghost' style='visibility:hidden; height:0'>");
                element.attr("name",$(this).attr("name"));
                element.attr("id", $(this).attr("id"));
                element.change(function(){
                    element.next(element).find('input').val((element.val()).split('\\').pop());
                    readURL(this);
                });
                $(this).find("button.btn-choose").click(function(){
                    element.click();
                });
                $(this).find("button.btn-reset").click(function(){
                    element.val(null);
                    $('#im_passport').attr('src', null);
                    $(this).parents(".input-file").find('input').val('');
                });
                $(this).find('input').css("cursor","pointer");
                $(this).find('input').mousedown(function() {
                    $(this).parents('.input-file').prev().click();
                    return false;
                });
                return element;
            }
        }
    );
}

$(function() {
    bs_input_file();

    $('iframe[name=_result]').load(function() {

        var ret = $("iframe[name=_result]").contents().find("text").html();
        var face = $("iframe[name=_result]").contents().find("face").html();
        var card = $("iframe[name=_result]").contents().find("card").html();
        $('#result_view').attr('style', 'visibility: visible;  padding-left: 50px');
        $('#result_content').html(ret);
        if (face.length < 30){
        	$('#image_view').attr('style', 'visibility: hidden;');
            $('#im_face').attr('src', "");
        }
        else{
        	$('#image_view').attr('style', 'visibility: visible; width: 300px; height: 300px;');
            $('#im_face').attr('src', face);
        }
        
        if (card.length < 30){
        	$('#card_view').attr('style', 'visibility: hidden;');
            $('#im_card').attr('src', "");
        }
        else{
        	$('#card_view').attr('style', 'visibility: visible; width: 500px; height: 500px;');
            $('#im_card').attr('src', card);
        }
    });
});

function readURL(input) {

    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
            $('#im_passport').attr('src', e.target.result);
        }

        reader.readAsDataURL(input.files[0]);
    }
}
