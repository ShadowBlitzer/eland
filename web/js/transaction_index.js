$(document).ready(function(){
	$('form[name="f"]').submit(function(){
		if (!$(this).find('#f_from_user').val() && !$(this).find('#f_to_user').val()){
			$(this).find('#f_andor').remove();
		}
	});
});
