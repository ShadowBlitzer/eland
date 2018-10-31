$(document).ready(function(){
	var now = Math.floor(new Date().getTime() / 1000);
	var $data_text_inputs = $('form input[type="text"][data-typeahead]');
	var $data_options = $('form select option[data-typeahead]');
	var $target_text_inputs = $('form input[type="text"][data-typeahead-source-id]');

	var session_params = $('body').data('session-params');
	var $data_sources = $data_text_inputs.add($data_options);

	$data_sources.each(function(){

		var datasets = [];
		var data = $(this).data('typeahead');
		var newuserdays = $(this).data('newuserdays');
		var process = $(this).data('typeahead-process');

		var treshold = now - (newuserdays * 86400);

		for(var i = 0; i < data.length; i++){

			var rec = data[i];

			if (process === 'user'){

				console.log('process is user');

				var filter = function(users){
					return $.map(users, function(user){

						var cl = (user.a && (user.a > treshold)) ? ' class="success"' : '';

						switch (user.t){
							case 'post-active':
								cl = ' class="inactive"';
								break;
							case 'leaving':
								cl = ' class="danger"';
								break;
							case 'pre-active':
								cl = ' class="info"';
								break;
							case 'interlets':
								cl = ' class="warning"';
								break;
							default:
								break;
						}

						return {
							value: user.c + ' ' + user.n,
							tokens : [ user.c, user.n ],
							letscode: user.c,
							name: user.n,
							class: cl,
							type: user.t,
							postcode: user.p,
							balance: user.b,
							min: user.min,
							max: user.max
						};
					});
				}

				var tokenizer = function(d) {
					return Bloodhound.tokenizers.whitespace(d.value);
				};

				var templates = {
					suggestion: function(data) {
						return '<p' + data.class + '><strong>' + data.letscode +
							'</strong> ' + data.name + '</p>';
					}
				};

				var displayKey =  function(user){
					return user.value;
				};

			} else {
				filter = false;
				tokenizer = Bloodhound.tokenizers.whitespace;
				templates = {};
				displayKey = false;
			}

			datasets.push({data: new Bloodhound({
					prefetch: {
						url: rec.path,
						cache: true,
						ttl: 604800000,	//1 week
						thumbprint: rec.thumbprint,
						filter: filter
					},
					datumTokenizer: tokenizer,
					queryTokenizer: Bloodhound.tokenizers.whitespace
				}),
				templates: templates,
				displayKey: displayKey
			});
		}

		var args = [{
			HighLight: true
		}];

		for (i = 0; i < datasets.length; i++){

			args.push({
				displayKey: datasets[i].displayKey,
				source: datasets[i].data.ttAdapter(),
				templates: datasets[i].templates
			});
		}

		$(this).data('typeahead-args', args);

		if ($(this).prop('tagName').toLowerCase() == 'input'){
			$(this).typeahead.apply($(this), args);
		}
	});

	$target_text_inputs.each(function(){

		args = $data_sources.filter('#' + $(this).data('typeahead-source-id')).data('typeahead-args');

		if (args){
			$(this).typeahead.apply($(this), args);
		} else {

			$select = $('form select').filter('#' + $(this).data('typeahead-source-id'));

			$source = $select.find('option:selected');

			if ($source){

				$(this).typeahead.apply($(this), $source.data('typeahead-args'));

				var target = $(this);

				$select.change(function(){

					target.typeahead('val', '');
					target.typeahead('destroy');
					target.typeahead.apply(target, $select.find('option:selected').data('typeahead-args'));
				});
			}
		}
	});
});
