<?php

$page_access = 'admin';
require_once __DIR__ . '/include/web.php';

$setting = $_GET['edit'] ?? false;
$submit = isset($_POST['zend']) ? true : false;

$active_tab = 'balance';
$active_tab = $_GET['active_tab'] ?? $active_tab;
$active_tab = $_POST['active_tab'] ?? $active_tab;

$register_link = $app['base_url'] . '/register.php';
$register_link_explain = 'Het registratieformulier kan je terugvinden op <a href="' . $register_link;
$register_link_explain .= '">' . $register_link . '</a>. Plaats deze link op je website.';
$register_link_explain .= '<br>Bij inschrijving wordt een nieuwe gebruiker zonder letscode aangemaakt met status info-pakket.';
$register_link_explain .= '<br>De admin krijgt een notificatie-email bij elke inschrijving.';

$register_success_explain = 'Hier kan je aan de gebruiker uitleggen wat er verder gaat gebeuren. <br>';
$register_success_explain .= 'Als je groep een website heeft, is het nuttig om een link op te nemen ';
$register_success_explain .= 'om de gebruiker terug te voeren.';

$contact_link = $app['base_url'] . '/contact.php';
$contact_link_explain = 'Het contactformulier kan je terugvinden op <a href="' . $contact_link;
$contact_link_explain .= '">' . $contact_link . '</a>.';

$map_template_vars = [
	'voornaam' 			=> 'first_name',
	'achternaam'		=> 'last_name',
	'postcode'			=> 'postcode',
];

$periodic_mail_item_show_options = $periodic_mail_item_show_options_not_all = [
	'all'		=> 'Alle',
	'recent'	=> 'Recente',
	'none'		=> 'Geen',
];

$periodic_mail_template = [
	'messages_top'	=> 'Vraag en aanbod bovenaan',
	'news_top'		=> 'Nieuws bovenaan',
];

$landing_page_options = [
	'messages'		=> 'Vraag en aanbod',
	'users'			=> 'Leden',
	'transactions'	=> 'Transacties',
	'news'			=> 'Nieuws',
];

unset($periodic_mail_item_show_options_not_all['all']);

$currency = readconfigfromdb('currency');

$tab_panes = [

	'balance'		=> [
		'lbl'		=> 'Saldo',
		'inputs'	=> [
			'minlimit'	=> [
				'addon'	=> $currency,
				'lbl'	=> 'Minimum groepslimiet',
				'type'	=> 'number',
				'explain'	=> 'Minimum limiet die geldt voor alle accounts, behalve voor die accounts waarbij een individuele minimum limiet ingesteld is. Kan leeg gelaten worden.',
			],
			'maxlimit'	=> [
				'addon'	=> $currency,
				'lbl'	=> 'Maximum groepslimiet',
				'type'	=> 'number',
				'explain'	=> 'Maximum limiet die geldt voor alle accounts, behalve voor die accounts waarbij een individuele maximum limiet ingesteld is. Kan leeg gelaten worden.',
			],
			'preset_minlimit'	=> [
				'addon'	=> $currency,
				'lbl'	=> 'Preset individuele minimum limiet',
				'type'	=> 'number',
				'explain'	=> 'Bij aanmaak van een nieuwe gebruiker wordt deze individuele minimum limiet vooraf ingevuld in het aanmaakformulier. Dit heeft enkel zin wanneer instappende leden een afwijkende individuele minimum limiet hebben van de minimum groepslimiet. Deze instelling is ook nuttig wanneer de automatische minimum limiet gebruikt wordt. Dit veld kan leeg gelaten worden.',
			],
			'preset_maxlimit'	=> [
				'addon'	=> $currency,
				'lbl'	=> 'Preset individuele maximum limiet',
				'type'	=> 'number',
				'explain'	=> 'Bij aanmaak van een nieuwe gebruiker wordt deze individuele maximum limiet vooraf ingevuld in het aanmaakformulier. Dit heeft enkel zin wanneer instappende leden een afwijkende individuele maximum limiet hebben van de maximum groepslimiet. Dit veld kan leeg gelaten worden.',

			],
			'balance_equilibrium'	=> [
				'addon'		=> $currency,
				'lbl'		=> 'Het uitstapsaldo voor actieve leden. ',
				'type'		=> 'number',
				'required'	=> true,
				'explain' 	=> 'Het saldo van leden met status uitstapper kan enkel bewegen in de richting van deze instelling.'
			],

		],
	],

	'messages'		=> [
		'lbl'		=> 'Vraag en aanbod',
		'inputs'	=> [

			'msgs_days_default'	=> [
				'addon'	=> 'dagen',
				'lbl'	=> 'Standaard geldigheidsduur',
				'explain' => 'Bij aanmaak van nieuw vraag of aanbod wordt deze waarde standaard ingevuld in het formulier.',
				'type'	=> 'number',
				'attr'	=> ['min' => 1, 'max' => 1460],
			],

			'li_1'	=> [
				'inline' => '%1$s Ruim vervallen vraag en aanbod op na %2$s dagen.',
				'inputs' => [
					'msgcleanupenabled'	=> [
						'type'	=> 'checkbox',
					],
					'msgexpcleanupdays'	=> [
						'type'	=> 'number',
						'attr'	=> ['min' => 1, 'max' => 365],
					],
				],
			],

			'li_2'	=> [
				'inline' => '%1$s Mail een notificatie naar de eigenaar van een vraag of aanbod bericht op het moment dat het vervalt.',
				'inputs'	=> [
					'msgexpwarnenabled'	=> [
						'type'	=> 'checkbox',
					],
				],
			],
		],
	],

	'systemname'	=> [
		'lbl'	=> 'Groepsnaam',
		'inputs' => [
			'systemname' => [
				'lbl'		=> 'Groepsnaam',
				'required'	=> true,
			],
			'systemtag' => [
				'lbl'		=> 'Tag (hoofding voor emails)',
				'required'	=> true,
				'attr'		=> ['maxlength' => 30],
			],
		],
	],

	'currency'		=> [
		'lbl'	=> 'LETS-Eenheid',
		'inputs'	=> [
			'currency'	=> [
				'lbl'		=> 'Naam van LETS-Eenheid (meervoud)',
				'required'	=> true,
			],
			'currencyratio'	=> [
				'lbl'		=> 'Aantal per uur',
				'attr'		=> ['max' => 240, 'min' => 1],
				'type'		=> 'number',
				'explain'	=> 'Deze instelling is vereist voor eLAS/eLAND interLETS',
			],
		],
	],

	'mailaddresses'	=> [
		'lbl'		=> 'Mailadressen',
		'inputs'	=> [
			'admin'	=> [
				'lbl'	=> 'Algemeen admin/beheerder',
				'attr' 	=> ['minlength' => 7],
				'type'	=> 'email',
				'max_inputs'	=> 5,
				'add_btn_text' => 'Extra mailadres',
			],
			'newsadmin'	=> [
				'lbl'	=> 'Nieuwsbeheerder',
				'attr'	=> ['minlength' => 7],
				'type'	=> 'email',
				'max_inputs'	=> 5,
				'add_btn_text'	=> 'Extra mailadres',
			],
			'support'	=> [
				'lbl'	=> 'Support / Helpdesk',
				'attr'	=> ['minlength' => 7],
				'type'	=> 'email',
				'max_inputs'	=> 5,
				'add_btn_text'	=> 'Extra mailadres',
			],
		]
	],

	'saldomail'		=> [
		'lbl'	=> 'Overzichtsmail',
		'lbl_pane'	=> 'Overzichtsmail met recent vraag en aanbod',
		'inputs' => [
			'li_1'	=> [
				'inline' => 'Verstuur de overzichtsmail met recent vraag en aanbod om de %1$s dagen',
				'inputs' => [
					'saldofreqdays'	=> [
						'type'		=> 'number',
						'attr'		=> ['class' => 'sm-size', 'min' => 1, 'max' => 120],
						'required'	=> true,
					],
				],
				'explain' => 'Noot: Leden kunnen steeds ontvangst van de overzichtsmail aan- of afzetten in hun profielinstellingen.',
			],

			'weekly_mail_show_interlets'	=> [
				'lbl'		=> 'Toon interlets vraag en aanbod',
				'type'		=> 'select',
				'options'	=> $periodic_mail_item_show_options_not_all,
				'explain'	=> 'Deze instelling heeft enkel invloed wanneer interlets groepen ingesteld zijn.',
			],

			'weekly_mail_show_news'	=> [
				'lbl'		=> 'Toon nieuwsberichten',
				'type'		=> 'select',
				'options'	=> $periodic_mail_item_show_options,
			],

			'weekly_mail_show_docs'	=> [
				'lbl'		=> 'Toon documenten',
				'type'		=> 'select',
				'options'	=> $periodic_mail_item_show_options_not_all,
			],

			'weekly_mail_show_forum'	=> [
				'lbl'		=> 'Toon forumberichten',
				'type'		=> 'select',
				'explain'	=> 'Deze instelling heeft enkel invloed wanneer de forumpagina geactiveerd is.',
				'options'	=> $periodic_mail_item_show_options_not_all,
			],

			'weekly_mail_show_transactions'	=> [
				'lbl'		=> 'Toon transacties',
				'type'		=> 'select',
				'options'	=> $periodic_mail_item_show_options_not_all,
			],

			'weekly_mail_show_new_users'	=> [
				'lbl'		=> 'Toon Instappers',
				'type'		=> 'select',
				'options'	=> $periodic_mail_item_show_options,
			],

			'weekly_mail_show_leaving_users'	=> [
				'lbl'		=> 'Toon Uitstappers',
				'type'		=> 'select',
				'options'	=> $periodic_mail_item_show_options,
			],

			'weekly_mail_template'	=> [
				'lbl'		=> 'Template',
				'type'		=> 'select',
				'options'	=> $periodic_mail_template,
			],

		],
	],

	'contact'	=> [
		'lbl'	=> 'Contact',
		'lbl_pane'	=> 'Contactformulier',
		'inputs'	=> [
			'li_1'	=> [
				'inline' => '%1$s contactformulier aan.',
				'inputs' => [
					'contact_form_en' => [
						'type' => 'checkbox',
					],
				],
				'explain' => $contact_link_explain,
			],
			'contact_form_top_text' => [
				'lbl'	=> 'Tekst boven het contactformulier',
				'type'	=> 'textarea',
				'rich_edit'	=> true,
			],
			'contact_form_bottom_text' => [
				'lbl'		=> 'Tekst onder het contactformulier',
				'type'		=> 'textarea',
				'rich_edit'	=> true,
			],
		],
	],

	'registration'	=> [
		'lbl'	=> 'Inschrijven',
		'lbl_pane'	=> 'Inschrijvingsformulier',
		'inputs'	=> [
			'li_1'	=> [
				'inline' => '%1$s inschrijvingsformulier aan.',
				'inputs' => [
					'registration_en' => [
						'type' => 'checkbox',
					],
				],
				'explain' => $register_link_explain,
			],
			'registration_top_text' => [
				'lbl'	=> 'Tekst boven het inschrijvingsformulier',
				'type'	=> 'textarea',
				'rich_edit'	=> true,
				'explain' => 'Geschikt bijvoorbeeld om nadere uitleg bij de inschrijving te geven.',
			],
			'registration_bottom_text' => [
				'lbl'		=> 'Tekst onder het inschrijvingsformulier',
				'type'		=> 'textarea',
				'rich_edit'	=> true,
				'explain'	=> 'Geschikt bijvoorbeeld om privacybeleid toe te lichten.',
			],
			'registration_success_text'	=> [
				'lbl'	=> 'Tekst na succesvol indienen formulier.',
				'type'	=> 'textarea',
				'rich_edit'	=> true,
				'explain'	=> $register_success_explain,
			],
			'registration_success_mail'	=> [
				'lbl'		=> 'Mail naar gebruiker bij succesvol indienen formulier',
				'type'		=> 'textarea',
				'rich_edit'	=> true,
				'attr'		=> ['data-template-vars' => implode(',', array_keys($map_template_vars))],
			],
		],
	],

	'forum'	=> [
		'lbl'	=> 'Forum',
		'inputs'	=> [
			'li_1'	=> [
				'inline' => '%1$s Forum aan.',
				'inputs' => [
					'forum_en'	=> [
						'type'	=> 'checkbox',
					],
				]
			]
		],
	],

	'users'	=> [
		'lbl'	=> 'Leden',
		'inputs'	=> [
			'newuserdays' => [
				'addon'		=> 'dagen',
				'lbl'		=> 'Periode dat een nieuw lid als instapper getoond wordt.',
				'type'		=> 'number',
				'attr'		=> ['min' => 0, 'max' => 365],
				'required'	=> true,
			],
			'li_2' => [
				'inline' => '%1$s Leden kunnen zelf hun gebruikersnaam aanpassen.',
				'inputs' => [
					'users_can_edit_username' => [
						'type'	=> 'checkbox',
					],
				],
			],
			'li_3' => [
				'inline' => '%1$s Leden kunnen zelf hun volledige naam aanpassen.',
				'inputs' => [
					'users_can_edit_fullname' => [
						'type'	=> 'checkbox',
					],
				],
			],
		],
	],

	'system'	=> [
		'lbl'		=> 'Systeem',
		'inputs'	=> [
			'li_1'	=> [
				'inline'	=> '%1$s Mail functionaliteit aan: het systeem verstuurt mails.',
				'inputs'	=> [
					'mailenabled'	=> [
						'type'	=> 'checkbox',
					],
				],
			],
			'li_2' => [
				'inline' => '%1$s Onderhoudsmodus: alleen admins kunnen inloggen.',
				'inputs' => [
					'maintenance'	=> [
						'type'	=> 'checkbox',
					],
				],
			],
			'default_landing_page'	=> [
				'lbl'		=> 'Standaard landingspagina',
				'type'		=> 'select',
				'options'	=> $landing_page_options,
				'required'	=> true,
			],
			'homepage_url'	=> [
				'lbl'		=> 'Website url',
				'type'		=> 'url',
				'explain'	=> 'Titel en logo in de navigatiebalk linken naar deze url.',
			],
			'date_format'	=> [
				'lbl'		=> 'Datum- en tijdsweergave',
				'type'		=> 'select',
				'options'	=> $app['date_format']->get_options(),
			],

			'css'	=> [
				'lbl'		=> 'Stijl (css)',
				'type' 		=> 'url',
				'explain'	=> 'Url van extra stijlblad (css-bestand)',
				'attr'		=> ['maxlength'	=> 100],
			],
		],
	],
];

$config = [];

foreach ($tab_panes as $pane)
{
	if (isset($pane['inputs']))
	{
		foreach ($pane['inputs'] as $name => $input)
		{
			if (isset($input['inputs']))
			{
				foreach ($input['inputs'] as $sub_name => $sub_input)
				{
					$config[$sub_name] = readconfigfromdb($sub_name);
				}

				continue;
			}

			$config[$name] = readconfigfromdb($name);
		}
	}
}

if ($post)
{
	if (!isset($_POST[$active_tab . '_submit']))
	{
		$errors[] = 'Form submit error';
	}

	if ($error_token = $app['form_token']->get_error())
	{
		$errors[] = $error_token;
	}

	$posted_configs = $validators = [];

	foreach ($tab_panes[$active_tab]['inputs'] as $name => $input)
	{
		if (isset($input['inputs']))
		{
			foreach ($input['inputs'] as $sub_name => $sub_input)
			{
				$posted_configs[$sub_name] = trim($_POST[$sub_name]);

				$validators[$sub_name]['type'] = $sub_input['type'] ?? 'text';
				$validators[$sub_name]['attr'] = $sub_input['attr'] ?? [];
				$validators[$sub_name]['required'] = isset($sub_input['required']) ? true : false;
				$validators[$sub_name]['max_inputs'] = $sub_input['max_inputs'] ?? 1;
			}

			continue;
		}

		$posted_configs[$name] = trim($_POST[$name]);

		$validators[$name]['type'] = $input['type'] ?? 'text';
		$validators[$name]['attr'] = $input['attr'] ?? [];
		$validators[$name]['required'] = isset($input['required']) ? true : false;
		$validators[$name]['max_inputs'] = $input['max_inputs'] ?? 1;

	}

	foreach ($posted_configs as $name => $value)
	{
		$validator = $validators[$name];

		$err_n = ' (' . $name . ')';

		if ($validator['required'] && $value === '')
		{
			$errors[] = 'Het veld is verplicht in te vullen.' . $err_n;
			continue;
		}

		if ($validator['type'] == 'text' || $validator['type'] == 'textarea')
		{
			$config_htmlpurifier = HTMLPurifier_Config::createDefault();
			$config_htmlpurifier->set('Cache.DefinitionImpl', null);
			$htmlpurifier = new HTMLPurifier($config_htmlpurifier);
			$value = $htmlpurifier->purify($value);
		}

		$value = (strip_tags($value) !== '') ? $value : '';

		if ($validator['type'] == 'checkbox')
		{
			$value = ($value) ? '1' : '0';
		}

		if ($value === $config[$name])
		{
			unset($posted_configs[$name]);
			continue;
		}

		if ($name == 'date_format')
		{
			$error = $app['date_format']->get_error($value);

			if ($error)
			{
				$errors[] = $error . $err_n;
			}

			continue;
		}

		if ($validator['type'] == 'text')
		{
			$posted_configs[$name] = $value;

			if (isset($validator['attr']['maxlength']) && strlen($value) > $validator['attr']['maxlength'])
			{
				$errors[] = 'Fout: de waarde mag maximaal ' . $validator['attr']['maxlength'] . ' tekens lang zijn.' . $err_n;
			}

			if (isset($validator['attr']['minlength']) && strlen($value) < $validator['attr']['minlength'])
			{
				$errors[] = 'Fout: de waarde moet minimaal ' . $validator['attr']['minlength'] . ' tekens lang zijn.' . $err_n;
			}

			continue;
		}

		if ($validator['type'] == 'number')
		{
			if ($value === '' && !$validator['required'])
			{
				continue;
			}

			if (!filter_var($value, FILTER_VALIDATE_INT))
			{
				$errors[] = 'Fout: de waarde moet een getal zijn.' . $err_n;
			}

			if (isset($validator['attr']['max']) && $value > $validator['attr']['max'])
			{
				$errors[] = 'Fout: de waarde mag maximaal ' . $validator['attr']['max'] . ' bedragen.' . $err_n;
			}

			if (isset($validator['attr']['min']) && $value < $validator['attr']['min'])
			{
				$errors[] = 'Fout: de waarde moet minimaal ' . $validator['attr']['min'] . ' bedragen.' . $err_n;
			}

			continue;
		}

		if ($validator['type'] == 'checkbox')
		{
			$posted_configs[$name] = $value;

			continue;
		}

		if ($validator['type'] == 'email')
		{
			if (isset($validator['max_inputs']))
			{
				$mail_ary = explode(',', $value);

				if (count($mail_ary) > $validator['max_inputs'])
				{
					$errors[] = 'Maximaal ' . $validator['max_inputs'] . ' mailadressen mogen ingegeven worden.' . $err_n;
				}

				foreach ($mail_ary as $m)
				{
					$m = trim($m);

					if (!filter_var($m, FILTER_VALIDATE_EMAIL))
					{
						$errors[] =  $m . ' is geen geldig email adres.' . $err_n;
					}
				}

				continue;
			}

			if (!filter_var($value, FILTER_VALIDATE_EMAIL))
			{
				$errors[] =  $value . ' is geen geldig email adres.' . $err_n;
			}

			continue;
		}

		if ($validator['type'] == 'url')
		{
			if ($value != '')
			{
				if (!filter_var($value, FILTER_VALIDATE_URL))
				{
					$errors[] =  $value . ' is geen geldig url adres.' . $err_n;
				}
			}

			continue;
		}

		if ($validator['type'] == 'textarea')
		{
			$posted_configs[$name] = $value;

			if (isset($validator['attr']['maxlength']) && strlen($value) > $validator['attr']['maxlength'])
			{
				$errors[] = 'Fout: de waarde mag maximaal ' . $validator['attr']['maxlength'] . ' tekens lang zijn.' . $err_n;
			}

			if (isset($validator['attr']['minlength']) && strlen($value) < $validator['attr']['minlength'])
			{
				$errors[] = 'Fout: de waarde moet minimaal ' . $validator['attr']['minlength'] . ' tekens lang zijn.' . $err_n;
			}
		}
	}

	if (!count($posted_configs))
	{
		$app['alert']->warning('Geen gewijzigde waarden.');
		cancel();
	}

	if (count($errors))
	{
		$app['alert']->error($errors);
		cancel();
	}

	foreach ($posted_configs as $name => $value)
	{
		$app['xdb']->set('setting', $name, ['value' => $value]);

		$app['predis']->del($app['this_group']->get_schema() . '_config_' . $name);

		// prevent string too long error for eLAS database

		if ($validators[$name]['max_inputs'] > 1)
		{
			list($value) = explode(',', $value);
			$value = trim($value);
		}

		$value = substr($value, 0, 60);

		if ($app['db']->fetchColumn('select setting from config where setting = ?', [$name]))
		{
			$app['db']->update('config', ['value' => $value, '"default"' => 'f'], ['setting' => $name]);
		}
	}

	if (count($posted_configs) > 1)
	{
		$app['alert']->success('De instellingen zijn aangepast.');
	}
	else
	{
		$app['alert']->success('De instelling is aangepast.');
	}

	cancel();
}

$app['assets']->add(['summernote', 'rich_edit.js', 'config.js']);

$h1 = 'Instellingen';
$fa = 'gears';

include __DIR__ . '/include/header.php';

echo '<div>';
echo '<ul class="nav nav-pills" role="tablist">';

foreach ($tab_panes as $id => $pane)
{
	echo '<li role="presentation"';
	echo ($id == $active_tab) ? ' class="active"' : '';
	echo '>';
	echo '<a href="#' . $id . '" aria-controls="' . $id . '" role="tab" data-toggle="tab">';
	echo $pane['lbl'];
	echo '</a>';
	echo '</li>';
}

echo '</ul>';

echo '<div class="tab-content">';

///

foreach ($tab_panes as $id => $pane)
{
	$active = ($id == $active_tab) ? ' active' : '';

	echo '<div role="tabpanel" class="tab-pane' . $active . '" id="' . $id . '">';

	echo '<form method="post" class="form form-horizontal">';

	echo '<div class="panel panel-default">';
	echo '<div class="panel-heading"><h4>';
	echo $pane['lbl_pane'] ?? $pane['lbl'];
	echo '</h4></div>';

	echo '<ul class="list-group">';

	foreach ($pane['inputs'] as $name => $input)
	{
		echo '<li class="list-group-item">';

		if (isset($input['max_inputs']) && $input['max_inputs'] > 1)
		{
			echo '<input type="hidden" value="' . $config[$name] . '" ';
			echo 'data-max-inputs="' . $input['max_inputs'] . '" ';
			echo 'name="' . $name . '">';

			$name_suffix = '_0';
		}
		else
		{
			$name_suffix = '';
		}

		if (isset($input['inline']))
		{
			$input_ary = [];

			if (isset($input['inputs']))
			{
				foreach ($input['inputs'] as $inline_name => $inline_input)
				{
					$str = '<input type="';
					$str .= $inline_input['type'] ?? 'text';
					$str .= '" name="' . $inline_name . '"';

					if ($inline_input['type'] == 'checkbox')
					{
						$str .= ' value="1"';
						$str .= $config[$inline_name] ? ' checked="checked"' : '';
					}
					else
					{
						$str .= ' class="sm-size"';
						$str .= ' value="' . $config[$inline_name] . '"';
					}

					if (isset($inline_input['attr']))
					{
						foreach ($inline_input['attr'] as $attr_name => $attr_value)
						{
							$str .= ' ' . $attr_name . '="' . $attr_value . '"';
						}
					}

					$str .= isset($inline_input['required']) ? ' required' : '';

					$str .= '>';

					$input_ary[] = $str;
				}
			}

			echo '<p>' . vsprintf($input['inline'], $input_ary) . '</p>';
		}
		else
		{
			echo '<div class="form-group">';

			if (isset($input['lbl']))
			{
				echo '<label class="col-sm-3 control-label">';
				echo $input['lbl'];
				echo '</label>';
				echo '<div class="col-sm-9">';
			}
			else
			{
				echo '<div class="col-sm-12">';
			}

			if (isset($input['addon']))
			{
				echo '<div class="input-group margin-bottom">';
				echo '<span class="input-group-addon">';
				echo $input['addon'];
				echo '</span>';
			}

			if (isset($input['type']) && $input['type'] == 'select')
			{
				echo '<select class="form-control" name="' . $name . '"';
				echo isset($input['required']) ? ' required' : '';
				echo '>';

				render_select_options($input['options'], $config[$name]);

				echo '</select>';
			}
			else if (isset($input['type']) && $input['type'] == 'textarea')
			{
				echo '<textarea name="' . $name . '" id="' . $name . '" class="form-control';
				echo isset($input['rich_edit']) ? ' rich-edit' : '';
				echo '" rows="4"';

				echo isset($input['attr']['maxlength']) ? '' : ' maxlength="2000"';
				echo isset($input['attr']['minlength']) ? '' : ' minlength="1"';
				echo isset($input['required']) ? ' required' : '';

				if (isset($input['attr']))
				{
					foreach ($input['attr'] as $attr_name => $attr_value)
					{
						echo ' ' . $attr_name . '="' . $attr_value . '"';
					}
				}

				echo '>';
				echo $config[$name];
				echo '</textarea>';
			}
			else
			{
				echo '<input type="';
				echo $input['type'] ?? 'text';
				echo '" class="form-control" ';
				echo 'name="' . $name . $name_suffix . '" ';
				echo 'id="' . $name . $name_suffix . '" ';
				echo 'value="' . $config[$name] . '"';

				echo isset($input['attr']['maxlength']) ? '' : ' maxlength="60"';
				echo isset($input['attr']['minlength']) ? '' : ' minlength="1"';
				echo isset($input['required']) ? ' required' : '';

				if (isset($input['attr']))
				{
					foreach ($input['attr'] as $attr_name => $attr_value)
					{
						echo ' ' . $attr_name . '="' . $attr_value . '"';
					}
				}

				echo '>';
			}

			echo isset($input['addon']) ? '</div>' : '';
			echo '</div>';
			echo '</div>';
		}

		if (isset($input['max_inputs']) && $input['max_inputs'] > 1)
		{
			echo '<div class="form-group hidden add-input">';
			echo '<div class="extra-field col-sm-9 col-sm-offset-3">';
			echo '<br>';
			echo '<span class="btn btn-default"><i class="fa fa-plus" ></i> ';
			echo $input['add_btn_text'] ?? 'Extra';
			echo '</span>';
			echo '</div>';
			echo '</div>';
		}

		if (isset($input['explain']))
		{
			echo '<p><small>' . $input['explain'] . '</small></p>';
		}

		echo '</li>';
	}

	echo '</ul>';

	echo '<div class="panel-footer">';

	echo '<input type="hidden" name="active_tab" value="' . $id . '">';
	echo '<input type="submit" class="btn btn-primary" value="Aanpassen" name="' . $id . '_submit">';
	$app['form_token']->generate();

	echo '</div>';

	echo '</div>';

	echo '</form>';

	echo '</div>';
}

echo '</div>';
echo '</div>';

include __DIR__ . '/include/footer.php';

function cancel()
{
	global $active_tab;

	header('Location: ' . generate_url('config', ['active_tab' => $active_tab]));
	exit;
}
