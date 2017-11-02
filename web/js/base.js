$(document).ready(function() {

	$('[data-toggle=offcanvas]').click(function() {
		$('.row-offcanvas').toggleClass('active');
	});

/*
	var slideout = new Slideout({
        'panel': $('#panel'),
        'menu': $('#menu'),
        'padding': 256,
        'tolerance': 70
	  });

	$('[data-toggle=offcanvas]').click(function() {
		slideout.toggle();
	});	  

      // Toggle button
      document.querySelector('.toggle-button').addEventListener('click', function() {
        slideout.toggle();
    });
*/
	$('.footable').footable();

	$('a[data-elas-group-id]').click(function() {

		var ajax_loader = $('img.ajax-loader');
		ajax_loader.css('display', 'inherit');

		var group_id = $(this).data('elas-group-id');
		var session_params = $('body').data('session-params');

		var params = {"group_id": group_id};

		$.extend(params, session_params);

		$.get('./ajax/elas_group_login.php?' + $.param(params), function(data){

			ajax_loader.css('display', 'none');

			if (data.error) {
				alert(data.error);
			} else if (data.login_url) {
				window.open(data.login_url);
			} else {
				alert('De pagina kon niet geopend worden.');
			}
		});
	});
});
