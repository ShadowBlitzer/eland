var users = new Bloodhound({
	prefetch: {
		url: 'get_active_users.php?letsgroup_id=' + $('#to_letscode').attr('data-letsgroup-id'),
		ttl: 4320000,	// 50 days
		thumbprint: $(this).attr('data-thumbprint'),
		filter: function(users){
			return $.map(users, function(user){
				return { 
					value: user.c + ' ' + user.n,
					tokens : [ user.c, user.n ],
					letscode: user.c,
					name: user.n,
					class: (user.s) ? ((user.s == 2) ? 'danger' : 'success') : null,
					postcode: user.p
				};
			});
		}
	},
	datumTokenizer: function(d) { 
		return Bloodhound.tokenizers.whitespace(d.value); 
	},
	queryTokenizer: Bloodhound.tokenizers.whitespace
});

users.initialize();

$('#to_letscode').typeahead({
	highLight: true
},
{
	displayKey: function(user){ 
		return user.value;
	},
	source: users.ttAdapter(),
	templates: {
		suggestion: function(data) {
			return '<p class="' + data.class + '"><strong>' + data.letscode +
				'</strong> ' + data.name + '</p>';
		}
	}
});

$('table input[type="number"]').keyup(function(){
	recalc_sum();
});

function recalc_sum(){
    var sum = 0;
    $('table input[type="number"]').each(function() {
		sum += Number($(this).val());
    });
	$('input#total').val(sum);
}

$('#to_letscode').bind('typeahead:selected', function(ev, data) {
	$('table input[data-letscode="' + data.letscode + '"]').val(0);
	recalc_sum();
});

$('#fill_in_aid').submit(function(e){

	var days = $('#percentage_balance_days').val();

	if (days > 1)
	{
		var jqxhr = $.get('weighted_balances.php', {"days" :days})
		.done(function(data){
			fill_in(data);
		})
		.fail(function(){
			alert('Data ophalen mislukt.');
		});
	}
	else
	{
		fill_in();
	}

	e.preventDefault();
});

function fill_in(data)
{
	var ignore_letscode = $('#to_letscode').val().split(' ');
	ignore_letscode = ignore_letscode[0];

	var fixed = $('#fixed').val();
	var perc = $('#percentage_balance').val() / 100;
	var base = $('#percentage_balance_base').val();

    $('table input[type="number"]').each(function() {

		var am = (typeof data == 'object') ? data[$(this).attr('data-id')] : $(this).attr('data-balance');
		am = (am >= base) ? am - base : 0;

		var amount = +fixed + Math.round(am * perc);

		amount = (amount < 0) ? 0 : amount;

		if ($(this).attr('data-letscode') != ignore_letscode)
		{
			$(this).val(amount);
		}
    });

    recalc_sum();
}
