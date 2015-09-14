<?php
ob_start();
$rootpath = '../';
$role = 'admin';
require_once $rootpath . 'includes/inc_default.php';
require_once $rootpath . 'includes/inc_passwords.php';
require_once $rootpath . 'includes/inc_form.php';
require_once $rootpath . 'includes/inc_mailfunctions.php';

$mode = $_GET['mode'];
$id = $_GET['id'];
$user = $contact = array();

if ($_POST['zend'])
{
	$user = array(
		'name'			=> $_POST['name'],
		'fullname'		=> $_POST['fullname'],
		'letscode'		=> $_POST['letscode'],
		'postcode'		=> $_POST['postcode'],
		'birthday'		=> $_POST['birthday'],
		'hobbies'		=> $_POST['hobbies'],
		'comments'		=> $_POST['comments'],
		'login'			=> $_POST['login'],
		'accountrole'	=> $_POST['accountrole'],
		'status'		=> $_POST['status'],
		'admincomment'	=> $_POST['admincomment'],
		'minlimit'		=> $_POST['minlimit'],
		'maxlimit'		=> $_POST['maxlimit'],
		'presharedkey'	=> $_POST['presharedkey'],
		'cron_saldo'	=> ($_POST['cron_saldo']) ? 't' : 'f',
		'lang'			=> 'nl',
	);

	$contact = $_POST['contact'];
	$notify = $_POST['notify'];
	$password = $_POST['pw'];

	foreach ($contact as $c)
	{
		if ($c['abbrev'] == 'mail' && $c['main_mail'])
		{
			$mail = $c['value'];
			break;
		}
	}

	if (!isset($mail))
	{
		$errors['mail'] = 'Geen mail adres ingevuld.';
	}
	else if (!filter_var($mail, FILTER_VALIDATE_EMAIL))
	{
		$errors['mail'] = 'Geen geldig email adres.';
	}
	else if ($mode == 'new')
	{
		if ($db->fetchColumn('select c.value
			from contact c, type_contact tc
			where c.id_type_contact = tc.id
				and tc.abbrev = \'mail\'
				and c.value = ?', array($mail)))
		{
			$errors['mail'] = 'Het mailadres is al in gebruik.';
		}
		else
		{
			$errors = validate_input($user);
		}
	}
	else
	{
		if ($db->fetchColumn('SELECT c.value
			FROM contact c, type_contact tc
			WHERE c.id_user <> ' . $id . '
				AND c.id_type_contact = tc.id
				AND tc.abbrev = \'mail\'
				AND c.value = ?', array($mail)))
		{
			$errors['mail'] = 'Het email adres is al in gebruik.';
		}
	}

	if ($mode == 'new')
	{
		if (!$password)
		{
			$errors[] = 'Gelieve een paswoord in te vullen.';
		}
		else if (!password_strength($password))
		{
			$errors[] = 'Het paswoord is niet sterk genoeg.';
		}
	}

	if (!count($errors))
	{
		$contact_types = $db->fetchAll('SELECT abbrev, id FROM type_contact');

		assoc($contact_types);

		if ($mode == 'new')
		{
			$user['creator'] = $s_id;
			$user['cdate'] = date('Y-m-d H:i:s');

			if ($user['status'] == 1)
			{
				$user['adate'] = date('Y-m-d H:i:s');
			}

			$user['password'] = hash('sha512', $password);

			if ($db->AutoExecute('users', $user, 'INSERT'))
			{
				$alert->success('Gebruiker opgeslagen.');

				$id = $db->insert_ID();
				readuser($id, true);

				foreach ($contact as $value)
				{
					if (!$value['value'])
					{
						continue;
					}

					$insert = array(
						'value'				=> $value['value'],
						'flag_public'		=> ($value['flag_public']) ? 1 : 0,
						'id_type_contact'	=> $contact_types[$value['abbrev']],
						'id_user'			=> $id,
					);
					
					$db->AutoExecute('contact', $insert, 'INSERT');
				}

				$mailenabled = readconfigfromdb('mailenabled');

				if($notify && !empty($mail) && $mailenabled && $user['status'] == 1)
				{
					$user['mail'] = $mail;
					sendactivationmail($password, $user);
					sendadminmail($user);
					$alert->success('Mail met paswoord naar de gebruiker verstuurd.');
				}
				else
				{
					$alert->warning('Geen mail met paswoord naar de gebruiker verstuurd.');
				}

				header('Location: ' . $rootpath . 'users/view.php?id=' . $id);
				exit;

				if (!$mailenabled)
				{
					$alert->warning('Mailfuncties zijn uitgeschakeld.');
				}
			}
			else
			{
				$alert->error('Gebruiker niet opgeslagen.');
			}
		}
		else if ($id)
		{
			$user_stored = readuser($id);

			$user['mdate'] = date('Y-m-d H:i:s');

			if (!$user_stored['adate'] && $user['status'] == 1)
			{
				$user['adate'] = date('Y-m-d H:i:s');
			}

			if($db->AutoExecute('users', $user, 'UPDATE', 'id = ' . $id))
			{
				$alert->success('Gebruiker aangepast.');

				$stored_contacts = $db->fetchAll('SELECT c.id, tc.abbrev, c.value, c.flag_public
					FROM type_contact tc, contact c
					WHERE tc.id = c.id_type_contact
						AND c.id_user = ' . $id);

				assoc($stored_contacts);

				foreach ($contact as $value)
				{
					$stored_contact = $stored_contacts[$value['id']];

					$value['flag_public'] = ($value['flag_public']) ? 1 : 0;

					if (!$value['value'])
					{
						if ($stored_contact && !$value['main_mail'])
						{
							$db->Execute('DELETE FROM contact
								WHERE id_user = ' . $id . '
									AND id = ' . $value['id']);
						}
						continue;
					}

					if ($stored_contact['abbrev'] == $value['abbrev']
						&& $stored_contact['value'] == $value['value']
						&& $stored_contact['flag_public'] == $value['flag_public'])
					{
						continue;
					}

					if (!isset($stored_contact))
					{
						$insert = array(
							'id_type_contact'	=> $contact_types[$value['abbrev']],						
							'value'				=> $value['value'],
							'flag_public'		=> ($value['flag_public']) ? 1 : 0,
							'id_user'			=> $id,
						);
						$db->AutoExecute('contact', $insert, 'INSERT');
						continue;
					}

					$contact_update = $value;
					unset($contact_update['id'], $contact_update['abbrev'],
						$contact_update['name'], $contact_update['main_mail']);

					$db->AutoExecute('contact', $contact_update, 'UPDATE',
						'id = ' . $value['id'] . ' AND id_user = ' . $id);
				}

				header('Location: ' . $rootpath . 'users/view.php?id=' . $id);
				exit;
			}
			else
			{
				$alert->error('Gebruiker niet aangepast.');
			}
		}
		else
		{
			$alert->error('Update niet mogelijk zonder id.');
		}
	}
	else
	{
		$alert->error('Fout in formulier: ' . implode(' | ', $errors));
	}
}
else
{
	$contact = $db->fetchAll('select name, abbrev, \'\' as value, 0 as flag_public, 0 as id
		from type_contact
		where abbrev in (\'mail\', \'adr\', \'tel\', \'gsm\')');

	if ($mode == 'edit')
	{
		$contact_keys = array();

		foreach ($contact as $key => $c)
		{
			$contact_keys[$c['abbrev']] = $key;
		}

		$user = $db->fetchAssoc('SELECT * FROM users WHERE id = ?', array($id));

		$rs = $db->Execute('SELECT tc.abbrev, c.value, tc.name, c.flag_public, c.id
			FROM type_contact tc, contact c
			WHERE tc.id = c.id_type_contact
				AND c.id_user = ' . $id);

		while ($row = $rs->FetchRow())
		{
			if (isset($contact_keys[$row['abbrev']]))
			{
				$contact[$contact_keys[$row['abbrev']]] = $row;				
				unset($contact_keys[$row['abbrev']]);
				continue;
			}

			$contact[] = $row;
		}
	}
	else
	{
		$user = array(
			'minlimit'		=> readconfigfromdb('minlimit'),
			'maxlimit'		=> readconfigfromdb('maxlimit'),
			'accountrole'	=> 'user',
			'status'		=> '1',
			'cron_saldo'	=> 't',
		);
	}
}

array_walk($user, function(&$value, $key){ $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); });
array_walk($contact, function(&$value, $key){ $value['value'] = htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8'); });

$includejs = '
	<script src="' . $cdn_datepicker . '"></script>
	<script src="' . $cdn_datepicker_nl . '"></script>
	<script src="' . $rootpath . 'js/generate_password.js"></script>
	<script src="' . $rootpath . 'js/generate_password_onload.js"></script>';

$includecss = '<link rel="stylesheet" type="text/css" href="' . $cdn_datepicker_css . '" />';

$h1 = 'Gebruiker ' . (($mode == 'new') ? 'toevoegen' : 'aanpassen');
$fa = 'user';

include $rootpath . 'includes/inc_header.php';

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';

echo '<form method="post" class="form-horizontal">';

echo '<div class="form-group">';
echo '<label for="name" class="col-sm-2 control-label">Naam</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="name" name="name" ';
echo 'value="' . $user['name'] . '" required>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="fullname" class="col-sm-2 control-label">Volledige naam (Voornaam en Achternaam)</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="fullname" name="fullname" ';
echo 'value="' . $user['fullname'] . '" required>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="letscode" class="col-sm-2 control-label">Letscode</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="letscode" name="letscode" ';
echo 'value="' . $user['letscode'] . '" required>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="postcode" class="col-sm-2 control-label">Postcode</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="postcode" name="postcode" ';
echo 'value="' . $user['postcode'] . '">';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="birthday" class="col-sm-2 control-label">Geboortedatum (jjjj-mm-dd)</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="birthday" name="birthday" ';
echo 'value="' . $user['birthday'] . '" required ';
echo 'data-provide="datepicker" data-date-format="yyyy-mm-dd" ';
echo 'data-date-default-view="2" ';
echo 'data-date-end-date="' . date('Y-m-d') . '" ';
echo 'data-date-language="nl" ';
echo 'data-date-start-view="2" ';
echo 'data-date-today-highlight="true" ';
echo 'data-date-autoclose="true" ';
echo 'data-date-immediate-updates="true" ';
echo '>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="hobbies" class="col-sm-2 control-label">Hobbies, interesses</label>';
echo '<div class="col-sm-10">';
echo '<textarea name="hobbies" id="hobbies" class="form-control">';
echo $user['hobbies'];
echo '</textarea>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="login" class="col-sm-2 control-label">Login</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="login" name="login" ';
echo 'value="' . $user['login'] . '">';
echo '</div>';
echo '</div>';

$role_ary = array(
	'admin'		=> 'Admin',
	'user'		=> 'User',
	'guest'		=> 'Guest',
	'interlets'	=> 'Interlets',
);

echo '<div class="form-group">';
echo '<label for="accountrole" class="col-sm-2 control-label">Rechten</label>';
echo '<div class="col-sm-10">';
echo '<select id="accountrole" name="accountrole" class="form-control">';
render_select_options($role_ary, $user['accountrole']);
echo '</select>';
echo '</div>';
echo '</div>';

$status_ary = array(
	0	=> 'Inactief',
	1	=> 'Actief',
	2	=> 'Uitstapper',	
	5	=> 'Info-pakket',
	6	=> 'Info-moment',
	7	=> 'Extern',
);

echo '<div class="form-group">';
echo '<label for="status" class="col-sm-2 control-label">Status</label>';
echo '<div class="col-sm-10">';
echo '<select id="status" name="status" class="form-control">';
render_select_options($status_ary, $user['status']);
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="admincomment" class="col-sm-2 control-label">Commentaar van de admin</label>';
echo '<div class="col-sm-10">';
echo '<textarea name="admincomment" id="admincomment" class="form-control">';
echo $user['admincomment'];
echo '</textarea>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="minlimit" class="col-sm-2 control-label">Minimum limiet saldo</label>';
echo '<div class="col-sm-10">';
echo '<input type="number" class="form-control" id="minlimit" name="minlimit" ';
echo 'value="' . $user['minlimit'] . '">';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="maxlimit" class="col-sm-2 control-label">Maximum limiet saldo</label>';
echo '<div class="col-sm-10">';
echo '<input type="number" class="form-control" id="maxlimit" name="maxlimit" ';
echo 'value="' . $user['maxlimit'] . '">';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="presharedkey" class="col-sm-2 control-label">Preshared key</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="presharedkey" name="presharedkey" ';
echo 'value="' . $user['presharedkey'] . '">';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="cron_saldo" class="col-sm-2 control-label">Periodieke saldo mail met recent vraag en aanbod</label>';
echo '<div class="col-sm-10">';
echo '<input type="checkbox" name="cron_saldo" id="cron_saldo"';
echo ($user['cron_saldo'] == 't') ? ' checked="checked"' : '';
echo '>';
echo '</div>';
echo '</div>';

echo '<div class="bg-warning">';
echo '<h2><i class="fa fa-map-marker"></i> Contacten</h2>';

$already_one_mail_input = false;

foreach ($contact as $key => $c)
{
	$name = 'contact[' . $key . '][value]';
	$public = 'contact[' . $key . '][flag_public]';

	echo '<div class="form-group">';
	echo '<label for="' . $name . '" class="col-sm-2 control-label">' . $c['abbrev'] . '</label>';
	echo '<div class="col-sm-10">';
	echo '<input class="form-control" id="' . $name . '" name="' . $name . '" ';
	echo 'value="' . $c['value'] . '"';
	echo ($c['abbrev'] == 'mail' && !$already_one_mail_input) ? ' required="required"' : '';
	echo ($c['abbrev'] == 'mail') ? ' type="email"' : ' type="text"';
	echo '>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="' . $public . '" class="col-sm-2 control-label">Zichtbaar</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="checkbox" id="' . $public . '" name="' . $public . '" ';
	echo 'value="1"';
	echo  ($c['flag_public']) ? ' checked="checked"' : '';
	echo '>';
	echo '</div>';
	echo '</div>';

	if ($c['abbrev'] == 'mail' && !$already_one_mail_input)
	{
		echo '<input type="hidden" name="contact['. $key . '][main_mail]" value="1">';
	}
	echo '<input type="hidden" name="contact['. $key . '][id]" value="' . $c['id'] . '">';
	echo '<input type="hidden" name="contact['. $key . '][name]" value="' . $c['name'] . '">';
	echo '<input type="hidden" name="contact['. $key . '][abbrev]" value="' . $c['abbrev'] . '">';

	$already_one_mail_input = ($c['abbrev'] == 'mail') ? true : $already_one_mail_input;
}

echo '</div>';

if ($mode == 'new')
{
	echo '<button class="btn btn-default" id="generate">Genereer automatisch ander paswoord</button>';
	echo '<br><br>';
	
	echo '<div class="form-group">';
	echo '<label for="pw" class="col-sm-2 control-label">Paswoord</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="pw" name="pw" ';
	echo 'value="' . $password . '" required>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="notify" class="col-sm-2 control-label">Zend mail met paswoord naar gebruiker (enkel wanneer account actief is.)</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="checkbox" name="notify" id="notify"';
	echo ' checked="checked"';
	echo '>';
	echo '</div>';
	echo '</div>';
}

$cancel_red = ($id) ? 'view.php?id=' . $id : 'overview.php';
$btn = ($mode == 'edit') ? 'primary' : 'success';
echo '<a href="' . $rootpath . 'users/' . $cancel_red . '" class="btn btn-default">Annuleren</a>&nbsp;';
echo '<input type="submit" name="zend" value="Opslaan" class="btn btn-' . $btn . '">';

echo '</form>';

echo '</div>';
echo '</div>';

include $rootpath . 'includes/inc_footer.php';

function validate_input($posted_list)
{
	global $db;	
	$error_list = array();
	if (!isset($posted_list["name"])|| $posted_list["name"]=="")
	{
		$error_list["name"]="<font color='#F56DB5'>Vul <strong>naam</strong> in!</font>";
	}

	if ($db->fetchColumn('select letscode from users where letscode = ?', array($posted_list['letscode'])))
	{
		$error_list['letscode']= 'Letscode bestaat al!';
	}

	if (!empty($posted_list['login'])
		&& $db->fetchColumn('select login from users where login = ?', array($posted_list['login'])))
	{
		$error_list['login'] = 'Login bestaat al!';
	}

	if (!filter_var($posted_list['minlimit'] ,FILTER_VALIDATE_INT))
	{
		$error_list['minlimit'] = 'Geef getal op voor de minimum limiet.';
	}

	if (!filter_var($posted_list['maxlimit'] ,FILTER_VALIDATE_INT))
	{
		$error_list['maxlimit'] = 'Geef getal op voor de maximum limiet.';
	}

	return $error_list;
}

function sendadminmail($user)
{
	$mailfrom = trim(readconfigfromdb("from_address"));
	$mailto = trim(readconfigfromdb("admin"));
	$systemtag = readconfigfromdb("systemtag");

	$mailsubject = "[eLAS-";
	$mailsubject .= readconfigfromdb("systemtag");
	$mailsubject .= "] eLAS account activatie";

	$mailcontent  = "*** Dit is een automatische mail van het eLAS systeem van ";
	$mailcontent .= $systemtag;
	$mailcontent .= " ***\r\n\n";
	$mailcontent .= "De account ";
	$mailcontent .= $user["login"];
	$mailcontent .= ' ( ' . $user['letscode'] . ' ) ';
	$mailcontent .= " werd geactiveerd met een nieuw passwoord.\n";
	if ($user['mail'])
	{
		$mailcontent .= "Er werd een mail verstuurd naar de gebruiker op ";
		$mailcontent .= $user["mail"];
		$mailcontent .= ".\n\n";
	}
	else
	{
		$mailcontent .= "Er werd GEEN mail verstuurd omdat er geen E-mail adres bekend is voor de gebruiker.\n\n";
	}

	$mailcontent .= "OPMERKING: Vergeet niet om de gebruiker eventueel toe te voegen aan andere LETS programma's zoals mailing lists.\n\n";
	$mailcontent .= "Met vriendelijke groeten\n\nDe eLAS account robot\n";

	$mailcontent .= "\r\n";
	$mailcontent .= "         \,,,/\r\n";
	$mailcontent .= "         (o o)\r\n";
	$mailcontent .= "-----oOOo-(_)-oOOo-----\r\n\r\n\r\n";

	sendemail($mailfrom,$mailto,$mailsubject,$mailcontent);
}
