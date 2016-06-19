<?php

$rootpath = './';
$page_access = 'anonymous';
$allow_anonymous_post = true;

require_once $rootpath . 'includes/inc_default.php';

if(isset($_POST['zend']))
{
	$help = [
		'letscode' 			=> $_POST['letscode'],
		'mail'				=> $_POST['mail'],
		'subject' 			=> $_POST['subject'],
		'description' 		=> $_POST['description'],
		'browser'			=> $_SERVER['HTTP_USER_AGENT'],
	];

	if(empty($help['letscode']))
	{
		$errors[] = 'Vul je letscode in';
	}

	if(empty($help['mail']))
	{
		$errors[] = 'Vul een E-mail adres in';
	}

	if(!($db->fetchColumn('select c.value
		from contact c, type_contact tc
		where c.id_type_contact = tc.id
			and tc.abbrev = \'mail\'
			and c.value = ?', [$help['mail']])))
	{
		$errors[] = 'Dit mailadres is niet gekend in deze installatie';
	}

	if (!($help['user_id'] = $db->fetchColumn('select c.id_user
		from contact c, type_contact tc, users u
		where c.id_type_contact = tc.id
			and tc.abbrev = \'mail\'
			and c.value = ?
			and c.id_user = u.id
			and u.letscode = ?', [$help['mail'], $help['letscode']])))
	{
		$errors[] = 'Gebruiker niet gevonden.';
	}

	if(empty($help['subject']))
	{
        $errors[] = 'Geef een onderwerp op.';
	}

	if(empty($help['description']))
	{
		$errors[] = 'Geef een omschrijving van je probleem.';
	}

	if (!trim(readconfigfromdb('support')))
	{
		$errors[] = 'Het support email adres is niet ingesteld op deze installatie';
	}

	if ($token_error = get_error_form_token())
	{
		$errors[] = $token_error;
	}

	if(!count($errors))
	{
		$text  = "-- via het publieke formulier van de website werd het volgende probleem gemeld --\r\n";
		$text .= 'E-mail: ' . $help['mail'] . "\r\n";

		$text .= 'Gebruiker: ' . link_user($help['user_id'], false, false, true) . "\r\n";

		$text .= 'Opmerking: het is niet geheel zeker dat dit is de gebruiker zelf is. ';
		$text .= 'Iemand anders die het email adres en de letscode kent, kan dit bericht verzonden hebben.';
		$text .= "\r\n\r\n";
		$text .= "------------------------------ Bericht -----------------------------\r\n\r\n";
		$text .= $help['description'] . "\r\n\r\n";
		$text .= "--------------------------------------------------------------------\r\n\r\n";
		$text .= "User Agent:\r\n";
		$text .= $help['browser'] . "\r\n";
		$text .= "\r\n";
		$text .= 'eLAND webserver: ' . gethostname() . "\r\n";

		$return_message =  mail_q(['to' => 'support', 'subject' => $help['subject'], 'text' => $text, 'reply_to' => $help['user_id']]);

		if (!$return_message)
		{
			$alert->success('De support mail is verzonden.');
			header('Location: ' . generate_url('login'));
			exit;
		}

		$alert->error('Mail niet verstuurd. ' . $return_message);
	}
	else
	{
		$alert->error($errors);
	}
}
else
{
	$help = [
		'letscode' 			=> '',
		'mail'				=> '',
		'subject' 			=> '',
		'description' 		=> '',
	];
}

if (!readconfigfromdb('mailenabled'))
{
	$alert->warning('E-mail functies zijn uitgeschakeld door de beheerder. Je kan dit formulier niet gebruiken');
}
else if (!readconfigfromdb('support'))
{
	$alert->warning('Er is geen support mailadres ingesteld door de beheerder. Je kan dit formulier niet gebruiken.');
}

$h1 = 'Help / Probleem melden';
$fa = 'ambulance';

require_once $rootpath . 'includes/inc_header.php';

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';

echo '<form method="post" class="form-horizontal">';

echo '<div class="form-group">';
echo '<label for="letscode" class="col-sm-2 control-label">Letscode</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="letscode" name="letscode" ';
echo 'value="' . $help['letscode'] . '" required>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="mail" class="col-sm-2 control-label">Email (waarmee je in deze installatie geregistreerd bent)</label>';
echo '<div class="col-sm-10">';
echo '<input type="email" class="form-control" id="mail" name="mail" ';
echo 'value="' . $help['mail'] . '" required>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="subject" class="col-sm-2 control-label">Onderwerp</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="subject" name="subject" ';
echo 'value="' . $help['subject'] . '" required>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="description" class="col-sm-2 control-label">Omschrijving</label>';
echo '<div class="col-sm-10">';
echo '<textarea name="description" class="form-control" id="description" rows="4" required>';
echo $help['description'];
echo '</textarea>';
echo '</div>';
echo '</div>';

echo '<input type="submit" name="zend" value="Verzenden" class="btn btn-default">';
generate_form_token();

echo '</form>';

echo '</div>';
echo '</div>';

echo '<small><i>Opgelet: je kan vanuit het loginscherm zelf een nieuw paswoord aanvragen met je e-mail adres!</i></small>';

include $rootpath . 'includes/inc_footer.php';
