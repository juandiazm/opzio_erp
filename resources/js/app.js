require('./bootstrap');
import 'chart.js';
window.Swal = require('sweetalert2');
window.Toastify = require('toastify-js');

$(document).on('click', ".image-container", change_image_action);
$(document).on('click', ".image-plus-icon", function(){
    $(this).parent().find('.image-container').click();
});
$(document).on('click', ".image-container .image-input", function(e){e.stopPropagation();});
$(document).on('click', ".toggle-container .toggle-value", toggleInputEvent);
$(document).on('click', ".copy-action", copyContentOnClipBoard);
$(document).on('dblclick', '.input-password', toggleInputPassword);
function change_image_action(e){
    e.preventDefault();
    e.stopPropagation();
    $(this).find('.image-input').click();
}
$(document).on('click', '.dropdown .dropdown-menu .dropdown-item-p', setDropDownValue);
$(document).ready(function(){
    $('.toggle-container').each(function(){
        toggleInputAction($(this));
    });
});
function setDropDownValue(){
    $(this).parent().parent().attr('value',$(this).attr('value'));
    if($(this).parent().parent().has('.dropdown-btn-input').length>0)
    {
        $(this).parent().parent().find('.dropdown-btn-input').val($(this).text());
    }else{
        $(this).parent().parent().find('.dropdown-btn-text').text($(this).text());
    }
}
function toggleInputEvent(){
    var container = $(this).parent();
    var input = $(this);
    container.attr('value',input.attr('value'));
    toggleInputAction(container);
}
function toggleInputAction(container){
    var value = container.attr('value');
    container.find('.toggle-value').removeClass('active').addClass('inactive');
    container.find('.toggle-value[value="'+value+'"]').addClass('active').removeClass('inactive');
}
function copyContentOnClipBoard(){
    var content = $(this).attr('data-clipboard-text');
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(content).select();
    document.execCommand("copy");
    $temp.remove();
    swallMessage(
        'Copiado al portapapeles'
        , content
        , 'success'
        , null
        , null
        , 3000
        , null
        , null
    );
}
function toggleInputPassword(){
	let input = $(this);
	if(input.get(0).type == 'password'){
		input.get(0).type = 'text';
	}else{
		input.get(0).type = 'password';
	}
	setTimeout(() => {
		//set cursor to end of text
		input = input[0];
		// Set the selection start and end to the length of the input's value
		input.select();
		// Focus on the input field
		input.focus();
	}, 20);
}