
var GeneralDateFormat = {day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'};
$(document).on('click', '.input_image',SeleccionarImagen);
$(document).on('click', '.input-color',function(event){event.stopPropagation()});
$(document).on('click', '.input_image_just_click',SeleccionarImagen);
$(document).on('click', '.image-container',AgregarFoto);
$(document).on('click', '.color-container',AddColor);
$(document).on('change', '.input_image',CargarFoto);
$(document).on('change', '.input-color',loadColor);
$(document).on('change', '.CategoryColor',DefinirRGB);
$(document).on('click', '#RandomProductsContainer .RandomProductSubContainer .RandomProductsCenteredContainer', RandomProductOutClick);
$(document).on('click', '#BlurLayer #BlurLayerContainer #BlurLayer_CloseButton',CloseBlurLayout);
$(document).on('click', '.password-input .input-group-append',togglePasswordVisibility);

var GeneralDateFormat = {day: '2-digit', month: '2-digit', year: 'numeric'};
document.addEventListener('gesturestart', function (e) {
    e.preventDefault();
});
function ReLoadImages(){
    $('img[data-lazysrc]').each( function(){
        //* set the img src from data-src
        $( this ).attr( 'src', $( this ).attr( 'data-lazysrc' ) ).css('visibility', 'visible');
        }
    );
}
$(document).ready(function(){
	ReLoadImages();
	//$('#LoadContainer').fadeOut(350);
	token = $("meta[name='csrf-token']").attr("content"); 
	//MENÚ DESPLEGABLE
	$('.DropDownList .DropDownListItem .DropDownItemHeader .DropDownHeaderClicked').click(DropDownListEvent);
	$('#MessageContainer #MessageContainerAceptButton').click(CloseMessageBanner);
	setTimeout(function(){
		$('.OpacityTransition').fadeIn(500);
	}, 2000);
	if($(window).width()>700){
		var scrollEventHandler = function()
		{
		  window.scroll(0, window.pageYOffset)
		}
		window.addEventListener("scroll", scrollEventHandler, false);
	}
	//dobule click
	$(document).on('dblclick', '.input-group-append',function(event){event.stopPropagation()});
});


jQuery(window).on("load", function () {
	//$('#LoadContainer').fadeOut(350);
});
//IMAGEN
function AgregarFoto(event){
	event.preventDefault();
	event.stopPropagation();
	$(this).children('.input_image').click();
	$(this).children('.input_image_just_click').click();
}
function AddColor(event){
	event.preventDefault();
	event.stopPropagation();
	$(this).children('.input-color').click();
}
function SeleccionarImagen(event){
	event.stopPropagation();
}
function CargarFoto(event){
	var foto = this;
	$(foto).parent().children('.image_preview').css('display', 'none');
	$(foto).parent().children('.image-icon').css('display', 'none');
	if (foto.files && foto.files[0] && foto.files[0].type.indexOf("image")>=0) {
	    var reader = new FileReader();
	    reader.onload = function(e) {
			if($(foto).parent().find('.image_preview').length>0){
				if($(foto).parent().children('.image_preview').get(0).tagName == 'IMG'){
					$(foto).parent().children('.image_preview').attr('src', e.target.result).fadeIn(500);
				}else{
					$(foto).parent().children('.image_preview').css('background-image', 'url(' +e.target.result+ ')').fadeIn(500);
				}
			}else{
				$(foto).parent().css('background-image', 'url(' +e.target.result+ ')').fadeIn(500);
			}
	    }
	    reader.readAsDataURL(foto.files[0]);
	    $(foto).parent().css('background-color','transparent');
	  }
}
function loadColor(){
	var color = $(this).val();
	var container = $(this).parent();
	container.children('.color-icon').css('display', 'none');
	container.css('background-color', color).css('border-color', color);
}
//COLOR
function DefinirRGB(event){
	var rgb = hexToRgb($(this).val().replace('#',''));
	$(this).parent().children('.Categoryrgb').val(rgb).attr('value', rgb);
}
function hexToRgb(hex) {
	if(hex.includes('#')){
		hex = hex.replace('#', '');
	}
    var bigint = parseInt(hex, 16);
    var r = (bigint >> 16) & 255;
    var g = (bigint >> 8) & 255;
    var b = bigint & 255;
    return r + "," + g + "," + b;
}
function padZero(str, len) {
    len = len || 2;
    var zeros = new Array(len).join('0');
    return (zeros + str).slice(-len);
}
function invertColor(hex) {
    if (hex.indexOf('#') === 0) {
        hex = hex.slice(1);
    }
    // convert 3-digit hex to 6-digits.
    if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    if (hex.length !== 6) {
        throw new Error('Invalid HEX color.');
    }
    // invert color components
    var r = (255 - parseInt(hex.slice(0, 2), 16)).toString(16),
        g = (255 - parseInt(hex.slice(2, 4), 16)).toString(16),
        b = (255 - parseInt(hex.slice(4, 6), 16)).toString(16);
    // pad each with zeros and return
    return '#' + padZero(r) + padZero(g) + padZero(b);
}
//IMPRIMIR
function ImagetoPrint(source)
{
    return "<html><head><script>function step1(){\n" +
            "setTimeout('step2()', 10);}\n" +
            "function step2(){window.print();window.close()}\n" +
            "</scri" + "pt></head><body onload='step1()'>\n" +
            "<img src='" + source + "' style='display: block; margin:0; padding:0; max-widht:19.94cm !important; max-height:23.94cm !important;'/></body></html>";
}

function PrintImage(source, page_name)
{
    Pagelink = page_name;
    var pwa = window.open(Pagelink, "_new");
    pwa.document.open();
    pwa.document.write(ImagetoPrint(source));
    pwa.document.close();
}
//MENU DESPLEGABLE
function DropDownListEvent(event){
	event.preventDefault();
	var speed = 500;
	if ($(this).text() == '+') {
		$(this).animate({
			opacity: 0
		},speed, function() {
			$(this).text('-');
			$(this).animate({
				opacity: 1
			},speed);
		});
		$(this).removeClass('DropDown').addClass('DropedDown');
		$(this).parent().parent().find('.DropDownItemContainer').slideDown(speed);
	}else{
		$(this).animate({
			opacity: 0
		},speed, function() {
			$(this).text('+');
			$(this).animate({
				opacity: 1
			},speed);
		});
		$(this).removeClass('DropedDown').addClass('DropDown');
		$(this).parent().parent().find('.DropDownItemContainer').slideUp(speed);
	}
}
function CloseMessageBanner(event){
	$('#MessageContainer').fadeOut(200);
}
function validateForm(Form) {
  var isValid = true;
  Form.find('.form-mandatory').each(function() {
    if ( $(this).val() === '' )
        isValid = false;
  });
  return isValid;
}
//////
function getRandomColor() {
  var letters = '0123456789ABCDEF';
  var color = '#';
  for (var i = 0; i < 6; i++) {
    color += letters[Math.floor(Math.random() * 16)];
  }
  return color;
}
/////////////////////////////////////////////////////
function GetMethodFunction(URL, SuccesfullMessage, SuccessFunction, FailFunction){
	$('#loader-icon').removeClass('d-none');
	$.get(URL, function(result){
		if(SuccesfullMessage != null){
			alertSuccess(SuccesfullMessage);
		}
		if(SuccessFunction != null){
			SuccessFunction(result); 1000
		}
	}).fail(function (jqXHR, textStatus, error) {
    	alertWarning(jqXHR.responseJSON.message, 3000);
    	if(FailFunction != null){
			FailFunction();
		}
	}).always(function(){
		$('#loader-icon').addClass('d-none');
	});
}
function PostMethodFunctionWhitOutLoader(URL, DataSend, SuccesfullMessage, SuccessFunction, FailFunction){
	DataSend['_token'] = token;
	$.post(URL,DataSend, function(result){
		if(SuccesfullMessage != null){
			alertSuccess(SuccesfullMessage);
		}
		if(SuccessFunction != null){
			SuccessFunction(result);
		}
	}).fail(function (jqXHR, textStatus, error) {
		if (jqXHR.responseJSON.message) {
			alertWarning(jqXHR.responseJSON.message, 3000);
		}
    	if(FailFunction != null){
			FailFunction();
		}
	}).always(function(){
	});
}
function PostMethodFunction(URL, DataSend, SuccesfullMessage, SuccessFunction, FailFunction, ReturnData){
	$('#loader-icon').removeClass('d-none');
	DataSend['_token'] = token;
	$.post(URL,DataSend, function(result){
		if(SuccesfullMessage != null){
			alertSuccess(SuccesfullMessage);
		}
		if(SuccessFunction != null){
			SuccessFunction(result, ReturnData);
		}
	}).fail(function (jqXHR, textStatus, error) {
		
		if (jqXHR.responseJSON.message) {
			alertWarning(jqXHR.responseJSON.message, 3000);
		}
    	if(FailFunction != null){
			FailFunction();
		}
	}).always(function(){
		$('#loader-icon').addClass('d-none');
	});
}
function PostMethodFunctionSync(URL, DataSend, SuccesfullMessage, SuccessFunction, FailFunction, ReturnData){
	$.ajax({
		type: "POST",
		url: URL,
		headers: {
			'X-CSRF-TOKEN': token
		},
		data: DataSend,
		cache: false,
		timeout: 30000,
		async: false,
		success: function (result) {
			if(SuccesfullMessage != null){
				alertSuccess(SuccesfullMessage);
			}
			if(SuccessFunction != null){
				SuccessFunction(result);
			}
		},
		error: function (e) {
			if (e.responseJSON.message) {
				alertWarning(e.responseJSON.message, 3000);
			}
			if(FailFunction != null){
				FailFunction();
			}
		},
		complete: function() {
		}
	});
}
function PostMethodMultimediaFunction(URL, Form, SuccesfullMessage, SuccessFunction, FailFunction){
	//check if the Form contains files
	var fileInputs = Form.find('input[type="file"]');
	let flagSize = true;
	$.each(fileInputs, function(index, value){
		if(flagSize && value.files.length > 0){
			flagSize = checkFileSize(value, 10);
		}
	});
	if(flagSize && validateForm(Form)){
		var form = Form[0];
		var data = new FormData(form);
		$('#loader-icon').removeClass('d-none');;
		$.ajax({
			type: "POST",
			enctype: 'multipart/form-data',
			url: URL,
			data: data,
			processData: false,
			contentType: false,
			cache: false,
			timeout: 30000,
			success: function (result) {
				if(SuccesfullMessage != null){
					alertSuccess(SuccesfullMessage);
				}
				if(SuccessFunction != null){
					SuccessFunction(result);
				}
			},
			error: function (e) {
				if (e.responseJSON.message) {
					alertWarning(e.responseJSON.message, 3000);
				}
				if(FailFunction != null){
					FailFunction();
				}
			},
			complete: function() {
				$('#loader-icon').addClass('d-none');
			}
		});
	}else{
		FailFunction();
	}
}
function PostMethodMultimediaFunctionData(URL, data, SuccesfullMessage, SuccessFunction, FailFunction){
		$('#loader-icon').removeClass('d-none');;
		$.ajax({
			type: "POST",
			enctype: 'multipart/form-data',
			url: URL,
			data: data,
			processData: false,
			contentType: false,
			cache: false,
			timeout: 30000,
			success: function (result) {
				if(SuccesfullMessage != null){
					Toastify({text:SuccesfullMessage,duration: 3000, stopOnFocus: true}).showToast();
				}
				if(SuccessFunction != null){
					SuccessFunction(result);
				}
			},
			error: function (e) {
				if (e.responseJSON.message) Toastify({text:'Debes ingresaros todos los datos **'
				,duration: 3000
				, stopOnFocus: true
				, style: {
					background: "#A21F1F",
					color: "#FFFFFF"
				}}
				).showToast();
				if(FailFunction != null){
					FailFunction();
				}
			},
			complete: function() {
				$('#loader-icon').addClass('d-none');
			}
		});
	
}
function RandomProductOutClick(event){
    event.preventDefault();
    window.open('/Product/'+$(this).attr('value'), "_blank");
}
$.fn.enterKey = function (fnc) {
    return this.each(function () {
        $(this).keypress(function (ev) {
            var keycode = (ev.keyCode ? ev.keyCode : ev.which);
            if (keycode == '13') {
                fnc.call(this, ev);
            }
        })
    })
}
function DateTimeToString(now, parameters) {
	year = "" + now.getFullYear();
	month = "" + (now.getMonth() + 1); if (month.length == 1) { month = "0" + month; }
	day = "" + now.getDate(); if (day.length == 1) { day = "0" + day; }
	hour = "" + now.getHours(); if (hour.length == 1) { hour = "0" + hour; }
	minute = "" + now.getMinutes(); if (minute.length == 1) { minute = "0" + minute; }
	second = "" + now.getSeconds(); if (second.length == 1) { second = "0" + second; }
	millisecond = "" + now.getMilliseconds(); if (millisecond.length == 1) { millisecond = "0" + millisecond; }
	var returnString = '';
	if(parameters.includes("Y")){
		returnString += year;
	}
	if(parameters.includes("M")){
	  if(returnString == ''){
		  returnString += month;	
	  }else{
		  returnString += '-'+month;
	  }
	}
	if(parameters.includes("d")){
	  if(returnString == ''){
		  returnString += day;	
	  }else{
		  returnString += '-'+day;
	  }
	}
	if(parameters.includes("H")){
	  if(returnString == ''){
		  returnString += hour;	
	  }else{
		  returnString += ' '+hour;
	  }
	}
	if(parameters.includes("m")){
	  if(returnString == ''){
		  returnString += minute;	
	  }else{
		  returnString += ':'+minute;
	  }
	}
	if(parameters.includes("s")){
	  if(returnString == ''){
		  returnString += second;	
	  }else{
		  returnString += ':'+second;
	  }
	}
	if(parameters.includes("z")){
	  if(returnString == ''){
		  returnString += millisecond;	
	  }else{
		  returnString += '.'+millisecond;
	  }
	}
	return returnString;
  }
  function joinDates(t, a, s) {
	  function format(m) {
		 let f = new Intl.DateTimeFormat('es', m);
		 return f.format(t);
	  }
	  return a.map(format).join(s);
   }
function CounterDownFunction(ToDate, DaysParagraph, HoursParagraph, MinutesParagraph, SecondsParagraph){
	// Set the date we're counting down to
	var countDownDate = new Date(ToDate).getTime();

	// Update the count down every 1 second
	var x = setInterval(function() {

		// Get today's date and time
		var now = new Date().getTime();
		// Find the distance between now and the count down date
		var distance = countDownDate - now;

		if (distance < 0) {
			clearInterval(x);
			var days = 0;
			var hours = 0;
			var minutes = 0;
			var seconds = 0;
		}else{
			// Time calculations for days, hours, minutes and seconds
			var days = Math.floor(distance / (1000 * 60 * 60 * 24));
			var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
			var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
			var seconds = Math.floor((distance % (1000 * 60)) / 1000);
		}
	
		$('#'+DaysParagraph).text(("0" + days).slice(-2));
		$('#'+HoursParagraph).text(("0" + hours).slice(-2));
		$('#'+MinutesParagraph).text(("0" + minutes).slice(-2));
		$('#'+SecondsParagraph).text(("0" + seconds).slice(-2));
		
	}, 1000);
}
const generateRandomNumber = (min, max) =>  {
	return Math.floor(Math.random() * (max - min) + min);
};
function CloseBlurLayout(){
	$('#BlurLayer').fadeOut(200);
}
function OpenBlurLayout(title, content){
	$('#BlurLayer #BlurLayerContainer #BlurLayer_Title').text(title);
	$('#BlurLayer #BlurLayerContainer #BlurLayerSubContainer').empty().append(content);
	$('#BlurLayer').fadeIn(200);
}
function alertSuccess(text, duration = 3000){
	Toastify({text:text
		,duration: duration
		, stopOnFocus: true
		,gravity: "bottom"
		, style: {
			background: "#FFFFFF",
			color: "#220245"
		}}
		).showToast();
}
function alertNormal(text, duration = 3000){
	Toastify({text:text
				,duration: duration
				, stopOnFocus: true
				,gravity: "bottom"
				, style: {
					background: "#FFFFFF",
					color: "#00057B"
				}}
				).showToast();
}
function alertWarning(text, duration = 3000){
	Toastify({text:text
				,duration: duration
				, stopOnFocus: true
				,gravity: "bottom"
				, style: {
					background: "#FFFFFF",
					color: "#C90D0D"
				}}
				).showToast();
}
function swallMessage(title, text, icon, confirmMessage=null, cancelMessage=null, timer=null, confirmCallback=null, cancelCallback=null) {
	let confirmButtonColor = '#220245';
	if(icon == 'error'){
		confirmButtonColor = '#C90D0D';
	}
	//get window width
	Swal.fire({
        title: '<span style="color:#484848 !important;">'+title+'</span>',
        html: text,
        icon: icon,
		iconColor:confirmButtonColor,
        showConfirmButton: confirmMessage!=null,
        confirmButtonText: confirmMessage,
        confirmButtonColor: confirmButtonColor,
        showCancelButton: cancelMessage!=null,
        cancelButtonColor: '#C4C4C4',
        cancelButtonText: cancelMessage,
		reverseButtons: true,
        timer: timer,
		width: ((window.innerWidth > 768) ? '768px' : '90%'),
    }).then((result) => {
		if (result.isConfirmed) {
			if(confirmCallback!=null) confirmCallback();
        }else if(result.isDismissed){
			if(cancelCallback!=null) cancelCallback();
        }else if(confirmCallback!=null){
			confirmCallback();
		}
    });
}
function togglePasswordVisibility(event){
	event.stopPropagation();
	let icon = $(this).find('i');
	let input = $(this).parent().find('input');
	if(icon.hasClass('fa-eye')){
		icon.removeClass('fa-eye').addClass('fa-eye-slash');
		input.get(0).type = 'password';
	}else{
		icon.removeClass('fa-eye-slash').addClass('fa-eye');
		input.get(0).type = 'text';
	}
}
function showLoadedImage(imgInput, imgPreview, background = false){
	if (imgInput.files && imgInput.files[0] && imgInput.files[0].type.indexOf("image")>=0) {
	    var reader = new FileReader();
	    reader.onload = function(e) {
			if(!background){
				imgPreview.attr('src', e.target.result).fadeIn(500);
			}else{
				imgPreview.css('background-image', 'url(' +e.target.result+ ')').fadeIn(500);
			}
	    }
	    reader.readAsDataURL(imgInput.files[0]);
	}
}
function validateEmail(email) {
	var re = /\S+@\S+\.\S+/;
	return re.test(email);
}
function checkFileSize(file, maxSize){
	maxSizeinKB = maxSize * 1024 * 1024;
	if(file.files[0].size > maxSizeinKB){
		swallMessage(
			'Error'
			, 'El archivo excede el tamaño máximo permitido ('+maxSize+'MB)<br><br>'+file.files[0].name
			, 'error'
			, null
			, null
			, 3000
			, null
			, null
		);
		return false;
	}
	return true;
}