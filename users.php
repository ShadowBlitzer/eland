<?php

use util\cnst;

$q = $_GET['q'] ?? '';
$status = $_GET['status'] ?? false;
$id = $_GET['id'] ?? false;
$tdays = $_GET['tdays'] ?? 365;
$del = $_GET['del'] ?? false;
$edit = $_GET['edit'] ?? false;
$add = $_GET['add'] ?? false;
$link = $_GET['link'] ?? false;
$pw = $_GET['pw'] ?? false;
$img = isset($_GET['img']) ? true : false;
$img_del = isset($_GET['img_del']) ? true : false;
$intersystem_code = $_GET['intersystem_code'] ?? false;
$password = $_POST['password'] ?? false;
$submit = isset($_POST['zend']) ? true : false;
$user_mail_submit = isset($_POST['user_mail_submit']) ? true : false;
$bulk_mail_submit = isset($_POST['bulk_mail_submit']) ? true : false;
$bulk_mail_test = isset($_POST['bulk_mail_test']) ? true : false;
$bulk_field = $_POST['bulk_field'] ?? false;
$selected_users = isset($_POST['sel']) && $_POST['sel'] != '' ? explode(',', $_POST['sel']) : [];

/*
 * general access
 */

$page_access = ($edit || $pw || $img_del || $password || $submit || $img)
	? 'user'
	: 'guest';
$page_access = ($add || $del || $bulk_mail_submit || $bulk_mail_test)
	? 'admin'
	: $page_access;
$allow_guest_post = $page_access === 'guest' && $user_mail_submit;

require_once __DIR__ . '/include/web.php';

/**
 * selectors for bulk actions
 */
$bulk_field_submit = $bulk_submit = false;

if ($app['s_admin'])
{
	$edit_fields_tabs = [
		'fullname_access'	=> [
			'lbl'				=> 'Zichtbaarheid Volledige Naam',
			'access_control'	=> true,
		],
		'adr_access'		=> [
			'lbl'		=> 'Zichtbaarheid adres',
			'access_control'	=> true,
		],
		'mail_access'		=> [
			'lbl'		=> 'Zichtbaarheid E-mail adres',
			'access_control'	=> true,
		],
		'tel_access'		=> [
			'lbl'		=> 'Zichtbaarheid telefoonnummer',
			'access_control'	=> true,
		],
		'gsm_access'		=> [
			'lbl'		=> 'Zichtbaarheid GSM-nummer',
			'access_control'	=> true,
		],
		'comments'			=> [
			'lbl'		=> 'Commentaar',
			'type'		=> 'text',
			'string'	=> true,
			'fa'		=> 'comment-o',
		],
		'accountrole'		=> [
			'lbl'		=> 'Rechten',
			'options'	=> cnst::ROLE_ARY,
			'string'	=> true,
			'fa'		=> 'hand-paper-o',
		],
		'status'			=> [
			'lbl'		=> 'Status',
			'options'	=> cnst::STATUS_ARY,
			'fa'		=> 'star-o',
		],
		'admincomment'		=> [
			'lbl'		=> 'Commentaar van de Admin',
			'type'		=> 'text',
			'string'	=> true,
			'fa'		=> 'comment-o',
		],
		'minlimit'			=> [
			'lbl'		=> 'Minimum Account Limiet',
			'type'		=> 'number',
			'fa'		=> 'arrow-down',
		],
		'maxlimit'			=> [
			'lbl'		=> 'Maximum Account Limiet',
			'type'		=> 'number',
			'fa'		=> 'arrow-up',
		],
		'cron_saldo'		=> [
			'lbl'	=> 'Periodieke Overzichts E-mail (aan/uit)',
			'type'	=> 'checkbox',
		],
	];

	if ($app['is_http_post'] && $bulk_field)
	{
		if (isset($_POST[$bulk_field . '_bulk_submit']))
		{
			$bulk_field_submit = true;
		}
	}

	$bulk_submit = $bulk_field_submit || $bulk_mail_submit || $bulk_mail_test;
}

/**
 * mail to user
 */

if ($user_mail_submit && $id && $app['is_http_post'])
{
	$user_mail_content = $_POST['user_mail_content'] ?? '';
	$user_mail_cc = $_POST['user_mail_cc'] ?? false;

	$user = $app['user_cache']->get($id, $app['tschema']);

	if (!$app['s_admin'] && !in_array($user['status'], [1, 2]))
	{
		$app['alert']->error('Je hebt geen rechten
			om een E-mail bericht naar een niet-actieve
			gebruiker te sturen');
		cancel($id);
	}

	if ($app['s_master'])
	{
		$app['alert']->error('Het master account kan
			geen E-mail berichten versturen.');
		cancel($id);
	}

	if (!$app['s_schema'])
	{
		$app['alert']->error('Je hebt onvoldoende
			rechten om een E-mail bericht te versturen.');
		cancel($id);
	}

	if (!$user_mail_content)
	{
		$app['alert']->error('Fout: leeg bericht. E-mail niet verzonden.');
		cancel($id);
	}

	$contacts = $app['db']->fetchAll('select c.value, tc.abbrev
		from ' . $app['s_schema'] . '.contact c, ' .
			$app['s_schema'] . '.type_contact tc
		where c.flag_public >= ?
			and c.id_user = ?
			and c.id_type_contact = tc.id',
			[\util\cnst::ACCESS_ARY[$user['accountrole']], $app['s_id']]);

	$vars = [
		'group'			=> $app['template_vars']->get($app['tschema']),
		'to_user'		=> link_user($user, $app['tschema'], false),
		'to_username'	=> $user['name'],
		'from_user'		=> link_user($app['session_user'], $app['s_schema'], false),
		'from_username'	=> $app['session_user']['name'],
		'to_group'		=> $app['s_group_self'] ? '' : $app['config']->get('systemname', $app['tschema']),
		'from_group'	=> $app['s_group_self'] ? '' : $app['config']->get('systemname', $app['s_schema']),
		'contacts'		=> $contacts,
		'msg_text'		=> $user_mail_content,
		'login_url'		=> $app['base_url'] . '/login.php',
		'support_url'	=> $app['base_url'] . '/support.php?src=p',
	];

	$app['queue.mail']->queue([
		'schema'	=> $app['tschema'],
		'to'		=> $app['mail_addr_user']->get($id, $app['tschema']),
		'reply_to'	=> $app['mail_addr_user']->get($app['s_id'], $app['s_schema']),
		'template'	=> 'user',
		'vars'		=> $vars,
	], 8000);

	if ($user_mail_cc)
	{
		$app['queue.mail']->queue([
			'schema'	=> $app['tschema'],
			'to' 		=> $app['mail_addr_user']->get($app['s_id'], $app['s_schema']),
			'template' 	=> 'user_copy',
			'vars'		=> $vars,
		], 8000);
	}

	$app['alert']->success('E-mail verzonden.');

	cancel($id);
}

/*
 * upload image
 */

if ($app['is_http_post'] && $img && $id )
{
	$s_owner = !$app['s_guest']
		&& $app['s_group_self']
		&& $app['s_id'] == $id
		&& $id;

	if (!($s_owner || $app['s_admin']))
	{
		echo json_encode(['error' => 'Je hebt onvoldoende rechten voor deze actie.']);
		exit;
	}

	$user = $app['user_cache']->get($id, $app['tschema']);

	$image = $_FILES['image'] ?: null;

	if (!$image)
	{
		echo json_encode(['error' => 'Afbeeldingsbestand ontbreekt.']);
		exit;
	}

	$size = $image['size'];
	$tmp_name = $image['tmp_name'];
	$type = $image['type'];

	if ($size > 400 * 1024)
	{
		echo json_encode(['error' => 'Het bestand is te groot.']);
		exit;
	}

	if ($type != 'image/jpeg')
	{
		echo json_encode(['error' => 'Ongeldig bestandstype.']);
		exit;
	}

	//

	$exif = exif_read_data($tmp_name);

	$orientation = $exif['COMPUTED']['Orientation'] ?? false;

	$tmpfile = tempnam(sys_get_temp_dir(), 'img');

	$imagine = new Imagine\Imagick\Imagine();

	$image = $imagine->open($tmp_name);

	switch ($orientation)
	{
		case 3:
		case 4:
			$image->rotate(180);
			break;
		case 5:
		case 6:
			$image->rotate(-90);
			break;
		case 7:
		case 8:
			$image->rotate(90);
			break;
		default:
			break;
	}

	$image->thumbnail(new Imagine\Image\Box(400, 400), Imagine\Image\ImageInterface::THUMBNAIL_INSET);
	$image->save($tmpfile);

	//

	$filename = $app['tschema'] . '_u_' . $id . '_';
	$filename .= sha1($filename . microtime()) . '.jpg';

	$err = $app['s3']->img_upload($filename, $tmpfile);

	if ($err)
	{
		$app['monolog']->error('pict: ' .  $err . ' -- ' .
			$filename, ['schema' => $app['tschema']]);

		$response = ['error' => 'Afbeelding opladen mislukt.'];
	}
	else
	{
		$app['db']->update($app['tschema'] . '.users', [
			'"PictureFile"'	=> $filename
		],['id' => $id]);

		$app['monolog']->info('User image ' . $filename .
			' uploaded. User: ' . $id,
			['schema' => $app['tschema']]);

		$app['user_cache']->clear($id, $app['tschema']);

		$response = ['success' => 1, 'filename' => $filename];
	}

	unlink($tmp_name);

	header('Pragma: no-cache');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Content-Disposition: inline; filename="files.json"');
	header('X-Content-Type-Options: nosniff');
	header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');
	header('Vary: Accept');

	echo json_encode($response);
	exit;
}

/**
 * delete image
 */

if ($img_del && $id)
{
	$s_owner = !$app['s_guest']
		&& $app['s_group_self']
		&& $app['s_id'] == $id
		&& $id;

	if (!($s_owner || $app['s_admin']))
	{
		$app['alert']->error('Je hebt onvoldoende rechten om de foto te verwijderen.');
		cancel($id);
	}

	$user = $app['user_cache']->get($id, $app['tschema']);

	if (!$user)
	{
		$app['alert']->error('De gebruiker bestaat niet.');
		cancel();
	}

	$file = $user['PictureFile'];

	if ($file == '' || !$file)
	{
		$app['alert']->error('De gebruiker heeft geen foto.');
		cancel($id);
	}

	if ($app['is_http_post'])
	{
		$app['db']->update($app['tschema'] . '.users',
			['"PictureFile"' => ''],
			['id' => $id]);
		$app['user_cache']->clear($id, $app['tschema']);
		$app['alert']->success('Profielfoto verwijderd.');
		cancel($id);
	}

	$h1 = 'Profielfoto ';

	if ($app['s_admin'])
	{
		$h1 .= 'van ' . link_user($id, $app['tschema']) . ' ';
	}

	$h1 .= 'verwijderen?';

	include __DIR__ . '/include/header.php';

	echo '<div class="row">';
	echo '<div class="col-xs-6">';
	echo '<div class="thumbnail">';
	echo '<img src="';
	echo $app['s3_url'] . $file;
	echo '" class="img-rounded">';
	echo '</div>';
	echo '</div>';

	echo '</div>';

	echo '<form method="post">';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo aphp('users', ['id' => $id], 'Annuleren', 'btn btn-default'). '&nbsp;';
	echo '<input type="submit" value="Verwijderen" name="zend" class="btn btn-danger">';

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';

	exit;
}

/**
 * bulk actions
 */

if ($bulk_submit && $app['is_http_post'] && $app['s_admin'])
{
	$verify = ($bulk_mail_submit || $bulk_mail_test) ? 'verify_mail' : 'verify_' . $bulk_field;
	$verify = isset($_POST[$verify]) ? true : false;

	if (!$verify)
	{
		$errors[] = 'Het controle nazichts-vakje is niet aangevinkt.';
	}

	if ($bulk_field_submit)
	{
		$value = $_POST[$bulk_field] ?? '';
	}

	if ($bulk_mail_test || $bulk_mail_submit)
	{
		$bulk_mail_subject = $_POST['bulk_mail_subject'];
		$bulk_mail_content = $_POST['bulk_mail_content'];
		$bulk_mail_cc = $_POST['bulk_mail_cc'] ?? false;

		if (!$bulk_mail_subject)
		{
			$errors[] = 'Gelieve een onderwerp in te vullen voor je E-mail.';
		}

		if (!$bulk_mail_content)
		{
			$errors[] = 'Het E-mail bericht is leeg.';
		}

		if (!$app['config']->get('mailenabled', $app['tschema']))
		{
			$errors[] = 'De E-mail functies zijn niet ingeschakeld. Zie instellingen.';
		}

		if ($app['s_master'])
		{
			$errors[] = 'Het master account kan geen E-mail berichten verzenden.';
		}
	}

	if (!count($selected_users) && !$bulk_mail_test)
	{
		$errors[] = 'Selecteer ten minste één gebruiker voor deze actie.';
	}

	if ($error_token = $app['form_token']->get_error())
	{
		$errors[] = $error_token;
	}

	if ($bulk_field && strpos($bulk_field, '_access') !== false)
	{
		$access_value = $app['access_control']->get_post_value();

		if ($access_error = $app['access_control']->get_post_error())
		{
			$errors[] = $access_error;
		}
	}

	if (count($errors))
	{
		$app['alert']->error($errors);
	}
	else
	{
		$user_ids = $selected_users;
	}

	$selected_users = array_combine($selected_users, $selected_users);
}

/**
 * bulk action: change a field for multiple users
 */

if ($app['s_admin'] && !count($errors) && $bulk_field_submit && $app['is_http_post'])
{
	$users_log = '';

	$rows = $app['db']->executeQuery('select letscode, name, id
		from ' . $app['tschema'] . '.users
		where id in (?)',
		[$user_ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

	foreach ($rows as $row)
	{
		$users_log .= ', ' . link_user($row, $app['tschema'], false, true);
	}

	$users_log = ltrim($users_log, ', ');

	if ($bulk_field == 'fullname_access')
	{
		$fullname_access_role = $app['access_control']->get_role($access_value);

		foreach ($user_ids as $user_id)
		{
			$app['xdb']->set('user_fullname_access', $user_id, [
				'fullname_access' => $fullname_access_role,
			], $app['tschema']);
			$app['predis']->del($app['tschema'] . '_user_' . $user_id);
		}

		$app['monolog']->info('bulk: Set fullname_access to ' .
			$fullname_access_role . ' for users ' .
			$users_log, ['schema' => $app['tschema']]);

		$app['alert']->success('De zichtbaarheid van de
			volledige naam werd aangepast.');

		cancel();
	}
	else if (['accountrole' => 1, 'status' => 1, 'comments' => 1,
		'admincomment' => 1, 'minlimit' => 1, 'maxlimit' => 1][$bulk_field])
	{
		if ($bulk_field == 'minlimit')
		{
			$value = $value == '' ? -999999999 : $value;
		}

		if ($bulk_field == 'maxlimit')
		{
			$value = $value == '' ? 999999999 : $value;
		}

		$type = $edit_fields_tabs[$bulk_field]['string'] ? \PDO::PARAM_STR : \PDO::PARAM_INT;

		$app['db']->executeUpdate('update ' . $app['tschema'] . '.users
			set ' . $bulk_field . ' = ? where id in (?)',
			[$value, $user_ids],
			[$type, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

		foreach ($user_ids as $user_id)
		{
			$app['predis']->del($app['tschema'] . '_user_' . $user_id);
		}

		if ($bulk_field == 'status')
		{
			delete_thumbprint('active');
			delete_thumbprint('extern');
		}

		$app['monolog']->info('bulk: Set ' . $bulk_field .
			' to ' . $value .
			' for users ' . $users_log,
			['schema' => $app['tschema']]);

		$app['interlets_groups']->clear_cache($app['s_schema']);

		$app['alert']->success('Het veld werd aangepast.');
		cancel();
	}
	else if (['adr_access' => 1, 'mail_access' => 1, 'tel_access' => 1, 'gsm_access' => 1][$bulk_field])
	{
		[$abbrev] = explode('_', $bulk_field);

		$id_type_contact = $app['db']->fetchColumn('select id
			from ' . $app['tschema'] . '.type_contact
			where abbrev = ?', [$abbrev]);

		$app['db']->executeUpdate('update ' . $app['tschema'] . '.contact
		set flag_public = ?
		where id_user in (?) and id_type_contact = ?',
			[$access_value, $user_ids, $id_type_contact],
			[\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]);

		$access_role = $app['access_control']->get_role($access_value);

		$app['monolog']->info('bulk: Set ' . $bulk_field .
			' to ' . $access_role .
			' for users ' . $users_log,
			['schema' => $app['tschema']]);
		$app['alert']->success('Het veld werd aangepast.');
		cancel();
	}
	else if ($bulk_field == 'cron_saldo')
	{
		$value = $value ? true : false;

		$app['db']->executeUpdate('update ' . $app['tschema'] . '.users
			set cron_saldo = ?
			where id in (?)',
			[$value, $user_ids],
			[\PDO::PARAM_BOOL, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

		foreach ($user_ids as $user_id)
		{
			$app['predis']->del($app['tschema'] . '_user_' . $user_id);
		}

		$value = $value ? 'on' : 'off';

		$app['monolog']->info('bulk: Set periodic mail to ' .
			$value . ' for users ' .
			$users_log,
			['schema' => $app['tschema']]);

		$app['interlets_groups']->clear_cache($app['s_schema']);

		$app['alert']->success('Het veld werd aangepast.');
		cancel();
	}
}

/**
 * bulk action: mail
 */

if ($app['s_admin'])
{
	$map_template_vars = [
		'naam' 					=> 'name',
		'volledige_naam'		=> 'fullname',
		'saldo'					=> 'saldo',
		'account_code'			=> 'letscode',
		'min_account_limiet'	=> 'minlimit',
		'max_account_limiet'	=> 'maxlimit',
	];
}

if ($app['s_admin']
	&& !count($errors)
	&& ($bulk_mail_submit || $bulk_mail_test)
	&& $app['is_http_post'])
{
	if ($bulk_mail_test)
	{
		$sel_ary = [$app['s_id'] => true];
		$user_ids = [$app['s_id']];
	}
	else
	{
		$sel_ary = $selected_users;
	}

	$count = 0;
	$users_log = $alert_msg_users = [];

	$config_htmlpurifier = HTMLPurifier_Config::createDefault();
	$config_htmlpurifier->set('Cache.DefinitionImpl', null);
	$htmlpurifier = new HTMLPurifier($config_htmlpurifier);
	$bulk_mail_content = $htmlpurifier->purify($bulk_mail_content);

	$sel_users = $app['db']->executeQuery('select u.*, c.value as mail
		from ' . $app['tschema'] . '.users u, ' .
			$app['tschema'] . '.contact c, ' .
			$app['tschema'] . '.type_contact tc
		where u.id in (?)
			and u.id = c.id_user
			and c.id_type_contact = tc.id
			and tc.abbrev = \'mail\'',
			[$user_ids], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

	foreach ($sel_users as $sel_user)
	{
		if (!isset($sel_ary[$sel_user['id']]))
		{
			// avoid duplicate send when multiple mail addresses for one user.
			continue;
		}

		unset($sel_ary[$sel_user['id']]);

		$sel_user['minlimit'] = $sel_user['minlimit'] == -999999999 ? '' : $sel_user['minlimit'];
		$sel_user['maxlimit'] = $sel_user['maxlimit'] == 999999999 ? '' : $sel_user['maxlimit'];

		$template_vars = [];

		foreach ($map_template_vars as $key => $val)
		{
			$template_vars[$key] = ($key == 'status') ? cnst::STATUS_ARY[$sel_user['status']] : $sel_user[$val];
		}

		try
		{
			$template = $app['twig']->createTemplate($bulk_mail_content);
			$html = $template->render($template_vars);
		}
		catch (Exception $e)
		{
			$app['alert']->error('Fout in E-mail template: ' . $e->getMessage());
			$sel_ary = [];

			break;
		}

		$app['queue.mail']->queue([
			'schema'	=> $app['tschema'],
			'to' 		=> $app['mail_addr_user']->get($sel_user['id'], $app['tschema']),
			'subject' 	=> $bulk_mail_subject,
			'html' 		=> $html,
			'reply_to' 	=> $app['mail_addr_user']->get($app['s_id'], $app['tschema']),
		], random_int(5000, 6000));

		$alert_msg_users[] = link_user($sel_user, $app['tschema']);

		$count++;
	}

	if ($count)
	{
		$alert_msg = 'E-mail verzonden naar ' . $count . ' ';
		$alert_msg .= $count > 1 ? 'accounts' : 'account';
		$alert_msg .= '<br>';
		$alert_msg .= implode('<br>', $alert_msg_users);

		$app['alert']->success($alert_msg);
	}
	else
	{
		$app['alert']->warning('Geen E-mails verzonden.');
	}

	if (count($sel_ary))
	{
		$missing_users = '';

		foreach ($sel_ary as $warning_user_id => $dummy)
		{
			$missing_users .= link_user($warning_user_id, $app['tschema']) . '<br>';
		}

		$alert_warning = 'Naar volgende gebruikers werd geen
			E-mail verzonden wegens ontbreken van E-mail adres: <br>' .
			$missing_users;

		$app['alert']->warning($alert_warning);
	}

	if ($bulk_mail_submit && $count)
	{
		$template_vars = [];

		foreach ($map_template_vars as $key => $trans)
		{
			$template_vars[$key] = '{{ ' . $key . ' }}';
		}

		$replace = $app['protocol'] . $app['groups']->get_host($app['tschema']) . '/users.php?';

		$out = str_replace('./users.php?', $replace, $alert_msg);
		$out .= '<br><br>';

		if (isset($alert_warning))
		{
			$out .= str_replace('./users.php?', $replace, $alert_warning);
			$out .= '<br><br>';
		}

		$out .= '<hr><br>';

		$template = $app['twig']->createTemplate($out . $bulk_mail_content);
		$html = $template->render($template_vars);

		$app['queue.mail']->queue([
			'schema'	=> $app['tschema'],
			'to' 		=> $app['mail_addr_user']->get($app['s_id'], $app['tschema']),
			'subject' 	=> 'kopie: ' . $bulk_mail_subject,
			'html' 		=> $html,
		], 8000);

		$app['monolog']->debug('#bulk mail',
			['schema' => $app['tschema']]);

		cancel();
	}
}

/**
 * Change password.
 */

if ($pw)
{
	$s_owner = !$app['s_guest']
		&& $app['s_group_self']
		&& $pw == $app['s_id']
		&& $pw;

	if (!$app['s_admin'] && !$s_owner)
	{
		$app['alert']->error('Je hebt onvoldoende rechten om het
			paswoord aan te passen voor deze gebruiker.');
		cancel($pw);
	}

	if($submit)
	{
		$password = trim($_POST['password']);

		if (empty($password) || ($password == ''))
		{
			$errors[] = 'Vul paswoord in!';
		}

		if (!$app['s_admin'] && $app['password_strength']->get($password) < 50)
		{
			$errors[] = 'Te zwak paswoord.';
		}

		if ($error_token = $app['form_token']->get_error())
		{
			$errors[] = $error_token;
		}

		if (empty($errors))
		{
			$update = [
				'password'	=> hash('sha512', $password),
				'mdate'		=> gmdate('Y-m-d H:i:s'),
			];

			if ($app['db']->update($app['tschema'] . '.users',
				$update,
				['id' => $pw]))
			{
				$app['user_cache']->clear($pw, $app['tschema']);
				$user = $app['user_cache']->get($pw, $app['tschema']);
				$app['alert']->success('Paswoord opgeslagen.');

				if (($user['status'] == 1 || $user['status'] == 2) && $_POST['notify'])
				{
					$to = $app['db']->fetchColumn('select c.value
						from ' . $app['tschema'] . '.contact c, ' .
							$app['tschema'] . '.type_contact tc
						where tc.id = c.id_type_contact
							and tc.abbrev = \'mail\'
							and c.id_user = ?', [$pw]);

					if ($to)
					{
						$vars = [
							'group'			=> $app['template_vars']->get($app['tschema']),
							'user'			=> $user,
							'password'		=> $password,
							'url_login'		=> $app['base_url'] . '/login.php?login=' . $user['letscode'],
							'support_url'	=> $app['base_url'] . '/support.php?src=p',
						];

						$app['queue.mail']->queue([
							'schema'	=> $app['tschema'],
							'to' 		=> $app['mail_addr_user']->get($pw, $app['tschema']),
							'reply_to'	=> $app['mail_addr_system']->get_support($app['tschema']),
							'template'	=> 'password_reset',
							'vars'		=> $vars,
						], 8000);

						$app['alert']->success('Notificatie mail verzonden');
					}
					else
					{
						$app['alert']->warning('Geen E-mail adres bekend voor deze gebruiker, stuur het paswoord op een andere manier door!');
					}
				}
				cancel($pw);
			}
			else
			{
				$app['alert']->error('Paswoord niet opgeslagen.');
			}
		}
		else
		{
			$app['alert']->error($errors);
		}

	}

	$user = $app['user_cache']->get($pw, $app['tschema']);

	$app['assets']->add([
		'generate_password.js',
	]);

	$h1 = 'Paswoord aanpassen';
	$h1 .= $s_owner ? '' : ' voor ' . link_user($user, $app['tschema']);
	$fa = 'key';

	include __DIR__ . '/include/header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post">';

	echo '<div class="form-group">';
	echo '<label for="password" class="control-label">';
	echo 'Paswoord</label>';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon">';
	echo '<span class="fa fa-key"></span></span>';
	echo '<input type="text" class="form-control" ';
	echo 'id="password" name="password" ';
	echo 'value="';
	echo $password;
	echo '" required>';
	echo '<span class="input-group-btn">';
	echo '<button class="btn btn-default" type="button" ';
	echo 'id="generate">Genereer</button>';
	echo '</span>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="notify" class="control-label">';
	echo '<input type="checkbox" name="notify" id="notify"';
	echo $user['status'] == 1 || $user['status'] == 2 ? ' checked="checked"' : ' readonly';
	echo '>';
	echo ' Verzend notificatie E-mail met nieuw paswoord. ';
	echo 'Dit is enkel mogelijk wanneer de Status ';
	echo 'actief is en E-mail adres ingesteld.';
	echo '</label>';
	echo '</div>';

	echo aphp('users', ['id' => $pw], 'Annuleren', 'btn btn-default');
	echo '&nbsp;';
	echo '<input type="submit" value="Opslaan" name="zend" ';
	echo 'class="btn btn-primary">';
	echo $app['form_token']->get_hidden_input();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 * delete a user.
 */

if ($del)
{
	if (!$app['s_admin'])
	{
		$app['alert']->error('Je hebt onvoldoende rechten
			om een gebruiker te verwijderen.');
		cancel($del);
	}

	if ($app['s_id'] == $del)
	{
		$app['alert']->error('Je kan jezelf niet verwijderen.');
		cancel($del);
	}

	if ($app['db']->fetchColumn('select id
		from ' . $app['tschema'] . '.transactions
		where id_to = ? or id_from = ?', [$del, $del]))
	{
		$app['alert']->error('Een gebruiker met transacties
			kan niet worden verwijderd.');
		cancel($del);
	}

	$user = $app['user_cache']->get($del, $app['tschema']);

	if (!$user)
	{
		$app['alert']->error('De gebruiker bestaat niet.');
		cancel();
	}

	if ($submit)
	{
		if ($error_token = $app['form_token']->get_error())
		{
			$app['alert']->error($error_token);
			cancel($del);
		}

		$verify = isset($_POST['verify']) ? true : false;

		if (!$verify)
		{
			$app['alert']->error('Het controle nazichts-vakje
				is niet aangevinkt.');
			cancel($del);
		}

		$usr = $user['letscode'] . ' ' . $user['name'] . ' [id:' . $del . ']';
		$msgs = '';
		$st = $app['db']->prepare('select id, content,
				id_category, msg_type
			from ' . $app['tschema'] . '.messages
			where id_user = ?');

		$st->bindValue(1, $del);
		$st->execute();

		while ($row = $st->fetch())
		{
			$msgs .= $row['id'] . ': ' . $row['content'] . ', ';
		}
		$msgs = trim($msgs, '\n\r\t ,;:');

		if ($msgs)
		{
			$app['monolog']->info('Delete user ' . $usr .
				', deleted Messages ' . $msgs,
				['schema' => $app['tschema']]);

			$app['db']->delete($app['tschema'] . '.messages',
				['id_user' => $del]);
		}

		// remove orphaned images.

		$rs = $app['db']->prepare('select mp.id, mp."PictureFile"
			from ' . $app['tschema'] . '.msgpictures mp
				left join ' . $app['tschema'] . '.messages m on mp.msgid = m.id
			where m.id is null');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$app['db']->delete($app['tschema'] . '.msgpictures', ['id' => $row['id']]);
		}

		// update counts for each category

		$offer_count = $want_count = [];

		$rs = $app['db']->prepare('select m.id_category, count(m.*)
			from ' . $app['tschema'] . '.messages m, ' .
				$app['tschema'] . '.users u
			where  m.id_user = u.id
				and u.status IN (1, 2, 3)
				and msg_type = 1
			group by m.id_category');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$offer_count[$row['id_category']] = $row['count'];
		}

		$rs = $app['db']->prepare('select m.id_category, count(m.*)
			from ' . $app['tschema'] . '.messages m, ' .
				$app['tschema'] . '.users u
			where m.id_user = u.id
				and u.status IN (1, 2, 3)
				and msg_type = 0
			group by m.id_category');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$want_count[$row['id_category']] = $row['count'];
		}

		$all_cat = $app['db']->fetchAll('select id,
				stat_msgs_offers, stat_msgs_wanted
			from ' . $app['tschema'] . '.categories
			where id_parent is not null');

		foreach ($all_cat as $val)
		{
			$offers = $val['stat_msgs_offers'];
			$wants = $val['stat_msgs_wanted'];
			$cat_id = $val['id'];

			$want_count[$cat_id] = $want_count[$cat_id] ?? 0;
			$offer_count[$cat_id] = $offer_count[$cat_id] ?? 0;

			if ($want_count[$cat_id] == $wants && $offer_count[$cat_id] == $offers)
			{
				continue;
			}

			$stats = [
				'stat_msgs_offers'	=> $offer_count[$cat_id] ?? 0,
				'stat_msgs_wanted'	=> $want_count[$cat_id] ?? 0,
			];

			$app['db']->update($app['tschema'] . '.categories',
				$stats,
				['id' => $cat_id]);
		}

		//delete contacts
		$app['db']->delete($app['tschema'] . '.contact',
			['id_user' => $del]);

		//delete fullname access record.
		$app['xdb']->del('user_fullname_access', $del, $app['tschema']);

		//finally, the user
		$app['db']->delete($app['tschema'] . '.users',
			['id' => $del]);
		$app['predis']->expire($app['tschema'] . '_user_' . $del, 0);

		$app['alert']->success('De gebruiker is verwijderd.');

		switch($user['status'])
		{
			case 0:
				delete_thumbprint('inactive');
				break;
			case 1:
			case 2:
				delete_thumbprint('active');
				break;
			case 5:
				delete_thumbprint('im');
				break;
			case 6:
				delete_thumbprint('ip');
				break;
			case 7:
				delete_thumbprint('extern');
				break;
			default:
				break;
		}

		$app['interlets_groups']->clear_cache($app['s_schema']);

		cancel();
	}

	$h1 = 'Gebruiker ';
	$h1 .= link_user($del, $app['tschema']);
	$h1 .= ' verwijderen?';
	$fa = 'user';

	include __DIR__ . '/include/header.php';

	echo '<p><font color="red">Alle Gegevens, Vraag en aanbod, ';
	echo 'Contacten en Afbeeldingen van ';
	echo $user['letscode'] . ' ' . $user['name'];
	echo ' worden verwijderd.</font></p>';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post"">';

	echo '<div class="form-group">';
	echo '<label for="id_verify">';
	echo '<input type="checkbox" name="verify"';
	echo ' value="1" id="id_verify"> ';
	echo ' Ik ben wis en waarachtig zeker dat ';
	echo 'ik deze gebruiker wil verwijderen.';
	echo '</label>';
	echo '</div>';

	echo aphp('users', ['id' => $del], 'Annuleren', 'btn btn-default');
	echo '&nbsp;';
	echo '<input type="submit" value="Verwijderen" ';
	echo 'name="zend" class="btn btn-danger">';
	echo $app['form_token']->get_hidden_input();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 * Edit or add a user
 */

if ($add || $edit)
{
	if ($add && !$app['s_admin'])
	{
		$app['alert']->error('Je hebt geen rechten om
			een gebruiker toe te voegen.');
		cancel();
	}

	$s_owner =  !$app['s_guest']
		&& $app['s_group_self']
		&& $edit
		&& $app['s_id']
		&& $edit == $app['s_id'];

	if ($edit && !$app['s_admin'] && !$s_owner)
	{
		$app['alert']->error('Je hebt geen rechten om
			deze gebruiker aan te passen.');
		cancel($edit);
	}

	if ($app['s_admin'])
	{
		$username_edit = $fullname_edit = true;
	}
	else if ($s_owner)
	{
		$username_edit = $app['config']->get('users_can_edit_username', $app['tschema']);
		$fullname_edit = $app['config']->get('users_can_edit_fullname', $app['tschema']);
	}
	else
	{
		$username_edit = $fullname_edit = false;
	}

	if ($submit)
	{
		$user = [
			'postcode'		=> trim($_POST['postcode']),
			'birthday'		=> trim($_POST['birthday']) ?: null,
			'hobbies'		=> trim($_POST['hobbies']),
			'comments'		=> trim($_POST['comments']),
			'cron_saldo'	=> isset($_POST['cron_saldo']) ? 1 : 0,
			'lang'			=> 'nl'
		];

		if ($app['s_admin'])
		{
			// hack eLAS compatibility (in eLAND limits can be null)
			$minlimit = trim($_POST['minlimit']);
			$maxlimit = trim($_POST['maxlimit']);

			$minlimit = $minlimit === '' ? -999999999 : $minlimit;
			$maxlimit = $maxlimit === '' ? 999999999 : $maxlimit;

			$user += [
				'letscode'		=> trim($_POST['letscode']),
				'accountrole'	=> $_POST['accountrole'],
				'status'		=> $_POST['status'],
				'admincomment'	=> trim($_POST['admincomment']),
				'minlimit'		=> $minlimit,
				'maxlimit'		=> $maxlimit,
				'presharedkey'	=> trim($_POST['presharedkey']),
			];

			$contact = $_POST['contact'];
			$notify = $_POST['notify'];
			$password = trim($_POST['password']);

			$mail_unique_check_sql = 'select count(c.value)
					from ' . $app['tschema'] . '.contact c, ' .
						$app['tschema'] . '.type_contact tc, ' .
						$app['tschema'] . '.users u
					where c.id_type_contact = tc.id
						and tc.abbrev = \'mail\'
						and c.value = ?
						and c.id_user = u.id
						and u.status in (1, 2)';

			if ($edit)
			{
				$mail_unique_check_sql .= ' and u.id <> ?';
			}

			$mailadr = false;

			$st = $app['db']->prepare($mail_unique_check_sql);

			foreach ($contact as $key => $c)
			{
				$contact[$key]['flag_public'] = $app['access_control']->get_post_value('contact_access_' . $key);

				if ($c['value'])
				{
					$contact_post_error = $app['access_control']->get_post_error('contact_access_' . $key);

					if ($contact_post_error)
					{
						$errors[] = $contact_post_error;
					}
				}
			}

			foreach ($contact as $key => $c)
			{
				if ($c['abbrev'] == 'mail')
				{
					$mailadr = trim($c['value']);

					if ($mailadr)
					{
						if (!filter_var($mailadr, FILTER_VALIDATE_EMAIL))
						{
							$errors[] =  $mailadr . ' is geen geldig email adres.';
						}

						$st->bindValue(1, $mailadr);

						if ($edit)
						{
							$st->bindValue(2, $edit);
						}

						$st->execute();

						$row = $st->fetch();

						$warning = 'Omdat deze gebruikers niet meer een uniek E-mail adres hebben zullen zij ';
						$warning .= 'niet meer zelf hun paswoord kunnnen resetten of kunnen inloggen met ';
						$warning .= 'E-mail adres. Zie ' . aphp('status', [], 'Status');

						$warning_2 = '';

						if ($row['count'] == 1)
						{
							$warning_2 .= 'Waarschuwing: E-mail adres ' . $mailadr;
							$warning_2 .= ' bestaat al onder de actieve gebruikers. ';
						}
						else if ($row['count'] > 1)
						{
							$warning_2 .= 'Waarschuwing: E-mail adres ' . $mailadr;
							$warning_2 .= ' bestaat al ' . $row['count'];
							$warning_2 .= ' maal onder de actieve gebruikers. ';
						}

						if ($warning_2)
						{
							$app['alert']->warning($warning_2 . $warning);
						}
					}
				}
			}

			if ($user['status'] == 1 || $user['status'] == 2)
			{
				if (!$mailadr)
				{
					$err = 'Waarschuwing: Geen E-mail adres ingevuld. ';
					$err .= 'De gebruiker kan geen berichten en notificaties ';
					$err .= 'ontvangen en zijn/haar paswoord niet resetten.';
					$app['alert']->warning($err);
				}
			}

			$letscode_sql = 'select letscode
				from ' . $app['tschema'] . '.users
				where letscode = ?';
			$letscode_sql_params = [$user['letscode']];
		}

		if ($username_edit)
		{
			$user['login'] = $user['name'] = trim($_POST['name']);
		}

		if ($fullname_edit)
		{
			$user['fullname'] = trim($_POST['fullname']);
		}

		$fullname_access = $app['access_control']->get_post_value('fullname_access');

		$name_sql = 'select name
			from ' . $app['tschema'] . '.users
			where name = ?';
		$name_sql_params = [$user['name']];

		$fullname_sql = 'select fullname
			from ' . $app['tschema'] . '.users
			where fullname = ?';
		$fullname_sql_params = [$user['fullname']];

		if ($edit)
		{
			$letscode_sql .= ' and id <> ?';
			$letscode_sql_params[] = $edit;
			$name_sql .= 'and id <> ?';
			$name_sql_params[] = $edit;
			$fullname_sql .= 'and id <> ?';
			$fullname_sql_params[] = $edit;

			$user_prefetch = $app['user_cache']->get($edit, $app['tschema']);
		}

		$fullname_access_error = $app['access_control']->get_post_error('fullname_access');

		if ($fullname_access_error)
		{
			$errors[] = $fullname_access_error;
		}

		if ($username_edit)
		{
			if (!$user['name'])
			{
				$errors[] = 'Vul gebruikersnaam in!';
			}
			else if ($app['db']->fetchColumn($name_sql, $name_sql_params))
			{
				$errors[] = 'Deze gebruikersnaam is al in gebruik!';
			}
			else if (strlen($user['name']) > 50)
			{
				$errors[] = 'De gebruikersnaam mag maximaal 50 tekens lang zijn.';
			}
		}

		if ($fullname_edit)
		{
			if (!$user['fullname'])
			{
				$errors[] = 'Vul de Volledige Naam in!';
			}

			if ($app['db']->fetchColumn($fullname_sql, $fullname_sql_params))
			{
				$errors[] = 'Deze Volledige Naam is al in gebruik!';
			}

			if (strlen($user['fullname']) > 100)
			{
				$errors[] = 'De Volledige Naam mag maximaal 100 tekens lang zijn.';
			}
		}

		if ($app['s_admin'])
		{
			if (!$user['letscode'])
			{
				$errors[] = 'Vul een Account Code in!';
			}
			else if ($app['db']->fetchColumn($letscode_sql, $letscode_sql_params))
			{
				$errors[] = 'De Account Code bestaat al!';
			}
			else if (strlen($user['letscode']) > 20)
			{
				$errors[] = 'De Account Code mag maximaal
					20 tekens lang zijn.';
			}

			if (!preg_match("/^[A-Za-z0-9-]+$/", $user['letscode']))
			{
				$errors[] = 'De Account Code kan enkel uit
					letters, cijfers en koppeltekens bestaan.';
			}

			if (filter_var($user['minlimit'], FILTER_VALIDATE_INT) === false)
			{
				$errors[] = 'Geef getal of niets op voor de
					Minimum Account Limiet.';
			}

			if (filter_var($user['maxlimit'], FILTER_VALIDATE_INT) === false)
			{
				$errors[] = 'Geef getal of niets op voor de
					Maximum Account Limiet.';
			}

			if (strlen($user['presharedkey']) > 80)
			{
				$errors[] = 'De Preshared Key mag maximaal
					80 tekens lang zijn.';
			}
		}

		if ($user['birthday'])
		{
			$user['birthday'] = $app['date_format']->reverse($user['birthday'], $app['tschema']);

			if ($user['birthday'] === '')
			{
				$errors[] = 'Fout in formaat geboortedag.';
				$user['birthday'] = '';
			}
		}

		if (strlen($user['comments']) > 100)
		{
			$errors[] = 'Het veld Commentaar mag maximaal
				100 tekens lang zijn.';
		}

		if (strlen($user['postcode']) > 6)
		{
			$errors[] = 'De postcode mag maximaal 6 tekens lang zijn.';
		}

		if (strlen($user['hobbies']) > 500)
		{
			$errors[] = 'Het veld hobbies en interesses mag
				maximaal 500 tekens lang zijn.';
		}

		if ($app['s_admin'] && !$user_prefetch['adate'] && $user['status'] == 1)
		{
			if (!$password)
			{
				$errors[] = 'Gelieve een Paswoord in te vullen.';
			}
			else if (!$app['password_strength']->get($password))
			{
				$errors[] = 'Het Paswoord is niet sterk genoeg.';
			}
		}

		if ($error_token = $app['form_token']->get_error())
		{
			$errors[] = $error_token;
		}

		if (!count($errors))
		{
			$contact_types = [];

			$rs = $app['db']->prepare('select abbrev, id
				from ' . $app['tschema'] . '.type_contact');

			$rs->execute();

			while ($row = $rs->fetch())
			{
				$contact_types[$row['abbrev']] = $row['id'];
			}

			if ($add)
			{
				$user['creator'] = $app['s_master'] ? 0 : $app['s_id'];

				$user['cdate'] = gmdate('Y-m-d H:i:s');

				if ($user['status'] == 1)
				{
					$user['adate'] = gmdate('Y-m-d H:i:s');
					$user['password'] = hash('sha512', $password);
				}
				else
				{
					$user['password'] = hash('sha512', sha1(microtime()));
				}

				if ($app['db']->insert($app['tschema'] . '.users', $user))
				{
					$id = $app['db']->lastInsertId($app['tschema'] . '.users_id_seq');

					$fullname_access_role = $app['access_control']->get_role($fullname_access);

					$app['xdb']->set('user_fullname_access', $id, [
						'fullname_access' => $fullname_access_role,
					], $app['tschema']);

					$app['alert']->success('Gebruiker opgeslagen.');

					$app['user_cache']->clear($id, $app['tschema']);
					$user = $app['user_cache']->get($id, $app['tschema']);

					foreach ($contact as $value)
					{
						if (!$value['value'])
						{
							continue;
						}

						if ($value['abbrev'] === 'adr')
						{
							$app['queue.geocode']->cond_queue([
								'adr'		=> $value['value'],
								'uid'		=> $id,
								'schema'	=> $app['tschema'],
							]);
						}

						$insert = [
							'value'				=> trim($value['value']),
							'flag_public'		=> $value['flag_public'],
							'id_type_contact'	=> $contact_types[$value['abbrev']],
							'id_user'			=> $id,
						];

						$app['db']->insert($app['tschema'] . '.contact', $insert);
					}

					if ($user['status'] == 1)
					{
						if ($notify && $mailadr && $user['status'] == 1 && $password)
						{
							$user['mail'] = $mailadr;

							if ($app['config']->get('mailenabled', $app['tschema']))
							{
								send_activation_mail($password, $user);

								$app['alert']->success('Een E-mail met paswoord is
									naar de gebruiker verstuurd.');
							}
							else
							{
								$app['alert']->warning('De E-mail functies zijn uitgeschakeld.
									Geen E-mail met paswoord naar de gebruiker verstuurd.');
							}
						}
						else
						{
							$app['alert']->warning('Geen E-mail met paswoord naar
								de gebruiker verstuurd.');
						}
					}

					if ($user['status'] == 2 | $user['status'] == 1)
					{
						delete_thumbprint('active');
					}

					if ($user['status'] == 7)
					{
						delete_thumbprint('extern');
					}

					$app['interlets_groups']->clear_cache($app['s_schema']);

					cancel($id);
				}
				else
				{
					$app['alert']->error('Gebruiker niet opgeslagen.');
				}
			}
			else if ($edit)
			{
				$user_stored = $app['user_cache']->get($edit, $app['tschema']);

				$user['mdate'] = gmdate('Y-m-d H:i:s');

				if (!$user_stored['adate'] && $user['status'] == 1)
				{
					$user['adate'] = gmdate('Y-m-d H:i:s');

					if ($password)
					{
						$user['password'] = hash('sha512', $password);
					}
				}

				if($app['db']->update($app['tschema'] . '.users', $user, ['id' => $edit]))
				{

					$fullname_access_role = $app['access_control']->get_role($fullname_access);

					$app['xdb']->set('user_fullname_access', $edit, [
						'fullname_access' => $fullname_access_role,
					], $app['tschema']);

					$app['user_cache']->clear($edit, $app['tschema']);
					$user = $app['user_cache']->get($edit, $app['tschema']);

					$app['alert']->success('Gebruiker aangepast.');

					if ($app['s_admin'])
					{
						$stored_contacts = [];

						$rs = $app['db']->prepare('select c.id,
								tc.abbrev, c.value, c.flag_public
							from ' . $app['tschema'] . '.type_contact tc, ' .
								$app['tschema'] . '.contact c
							WHERE tc.id = c.id_type_contact
								AND c.id_user = ?');
						$rs->bindValue(1, $edit);

						$rs->execute();

						while ($row = $rs->fetch())
						{
							$stored_contacts[$row['id']] = $row;
						}

						foreach ($contact as $value)
						{
							$stored_contact = $stored_contacts[$value['id']];

							if (!$value['value'])
							{
								if ($stored_contact)
								{
									$app['db']->delete($app['tschema'] . '.contact',
										['id_user' => $edit, 'id' => $value['id']]);
								}
								continue;
							}

							if ($stored_contact['abbrev'] == $value['abbrev']
								&& $stored_contact['value'] == $value['value']
								&& $stored_contact['flag_public'] == $value['flag_public'])
							{
								continue;
							}

							if ($value['abbrev'] === 'adr')
							{
								$app['queue.geocode']->cond_queue([
									'adr'		=> $value['value'],
									'uid'		=> $edit,
									'schema'	=> $app['tschema'],
								]);
							}

							if (!isset($stored_contact))
							{
								$insert = [
									'id_type_contact'	=> $contact_types[$value['abbrev']],
									'value'				=> trim($value['value']),
									'flag_public'		=> $value['flag_public'],
									'id_user'			=> $edit,
								];
								$app['db']->insert($app['tschema'] . '.contact', $insert);
								continue;
							}

							$contact_update = $value;

							unset($contact_update['id'], $contact_update['abbrev'],
								$contact_update['name'], $contact_update['main_mail']);

							$app['db']->update($app['tschema'] . '.contact',
								$contact_update,
								['id' => $value['id'], 'id_user' => $edit]);
						}


						if ($user['status'] == 1 && !$user_prefetch['adate'])
						{
							if ($notify && !empty($mailadr) && $password)
							{
								if ($app['config']->get('mailenabled', $app['tschema']))
								{
									$user['mail'] = $mailadr;

									send_activation_mail($password, $user);

									$app['alert']->success('E-mail met paswoord
										naar de gebruiker verstuurd.');
								}
								else
								{
									$app['alert']->warning('De E-mail functies zijn uitgeschakeld.
										Geen E-mail met paswoord naar de gebruiker verstuurd.');
								}
							}
							else
							{
								$app['alert']->warning('Geen E-mail met
									paswoord naar de gebruiker verstuurd.');
							}
						}

						if ($user['status'] == 1
							|| $user['status'] == 2
							|| $user_stored['status'] == 1
							|| $user_stored['status'] == 2)
						{
							delete_thumbprint('active');
						}

						if ($user['status'] == 7
							|| $user_stored['status'] == 7)
						{
							delete_thumbprint('extern');
						}

						$app['interlets_groups']->clear_cache($app['s_schema']);
					}
					cancel($edit);
				}
				else
				{
					$app['alert']->error('Gebruiker niet aangepast.');
				}
			}
		}
		else
		{
			$app['alert']->error($errors);

			if ($edit)
			{
				$user['adate'] = $user_prefetch['adate'];
			}

			$user['minlimit'] = $user['minlimit'] === -999999999 ? '' : $user['minlimit'];
			$user['maxlimit'] = $user['maxlimit'] === 999999999 ? '' : $user['maxlimit'];
		}
	}
	else
	{
		if ($edit)
		{
			$user = $app['user_cache']->get($edit, $app['tschema']);
			$fullname_access = $user['fullname_access'];
		}

		if ($app['s_admin'])
		{
			$contact = $app['db']->fetchAll('select name, abbrev,
				\'\' as value, 0 as id
				from ' . $app['tschema'] . '.type_contact
				where abbrev in (\'mail\', \'adr\', \'tel\', \'gsm\')');
		}

		if ($edit && $app['s_admin'])
		{
			$contact_keys = [];

			foreach ($contact as $key => $c)
			{
				$contact_keys[$c['abbrev']] = $key;
			}

			$st = $app['db']->prepare('select tc.abbrev, c.value, tc.name, c.flag_public, c.id
				from ' . $app['tschema'] . '.type_contact tc, ' .
					$app['tschema'] . '.contact c
				where tc.id = c.id_type_contact
					and c.id_user = ?');

			$st->bindValue(1, $edit);
			$st->execute();

			while ($row = $st->fetch())
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
		else if ($app['s_admin'])
		{
			$user = [
				'minlimit'		=> $app['config']->get('preset_minlimit', $app['tschema']),
				'maxlimit'		=> $app['config']->get('preset_maxlimit', $app['tschema']),
				'accountrole'	=> 'user',
				'status'		=> '1',
				'cron_saldo'	=> 1,
			];

			if ($intersystem_code)
			{
				if ($group = $app['db']->fetchAssoc('select *
					from ' . $app['tschema'] . '.letsgroups
					where localletscode = ?
						and apimethod <> \'internal\'', [$intersystem_code]))
				{
					$user['name'] = $user['fullname'] = $group['groupname'];

					if ($group['url'] && ($remote_schema = $app['groups']->get_schema($group['url'])))
					{
						$group['domain'] = strtolower(parse_url($group['url'], PHP_URL_HOST));

						if ($app['groups']->get_schema($group['domain']))
						{
							$remote_schema = $app['groups']->get_schema($group['domain']);

							$admin_mail = $app['config']->get('admin', $remote_schema);

							foreach ($contact as $k => $c)
							{
								if ($c['abbrev'] == 'mail')
								{
									$contact[$k]['value'] = $admin_mail;
									break;
								}
							}

							// name from source is preferable
							$user['name'] = $user['fullname'] = $app['config']->get('systemname', $remote_schema);
						}
					}
				}

				$user['cron_saldo'] = 0;
				$user['status'] = '7';
				$user['accountrole'] = 'interlets';
				$user['letscode'] = $intersystem_code;
			}
			else
			{
				$user['cron_saldo'] = 1;
				$user['status'] = '1';
				$user['accountrole'] = 'user';
			}
		}
	}

	if ($edit)
	{
		$edit_user_cached = $app['user_cache']->get($edit, $app['tschema']);
	}

	array_walk($user, function(&$value, $key){ $value = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')); });
	array_walk($contact, function(&$value, $key){ $value['value'] = trim(htmlspecialchars($value['value'], ENT_QUOTES, 'UTF-8')); });

	$app['assets']->add([
		'datepicker',
		'generate_password.js',
		'generate_password_onload.js',
		'user_edit.js',
		'access_input_cache.js',
		'typeahead',
		'typeahead.js',
	]);

	$h1 = 'Gebruiker ';
	$h1 .= $edit ? 'aanpassen: ' . link_user($user, $app['tschema']) : 'toevoegen';
	$h1 = ($s_owner && !$app['s_admin'] && $edit) ? 'Je profiel aanpassen' : $h1;
	$fa = 'user';

	include __DIR__ . '/include/header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post">';

	if ($app['s_admin'])
	{
		echo '<div class="form-group">';
		echo '<label for="letscode" class="control-label">';
		echo 'Account Code';
		echo '</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-user"></span></span>';
		echo '<input type="text" class="form-control" ';
		echo 'id="letscode" name="letscode" ';
		echo 'value="';
		echo $user['letscode'] ?? '';
		echo '" required maxlength="20" ';
		echo 'data-typeahead="';
		echo $app['typeahead']->get([['account_codes', [
			'schema'	=> $app['tschema'],
		]]]);
		echo '" ';
		echo 'data-typeahead-render="';
		echo htmlspecialchars(json_encode([
			'exists_check'	=> 10,
			'exists_omit'	=> $edit_user_cached['letscode'] ?? '',
		]));
		echo '">';
		echo '</div>';
		echo '<span class="help-block hidden exists_query_results">';
		echo 'Reeds gebruikt: ';
		echo '<span class="query_results">';
		echo '</span>';
		echo '</span>';
		echo '<span class="help-block hidden exists_msg">';
		echo 'Deze Account Code bestaat al!';
		echo '</span>';
		echo '</div>';
	}

	if ($username_edit)
	{
		echo '<div class="form-group">';
		echo '<label for="name" class="control-label">';
		echo 'Gebruikersnaam</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-user"></span></span>';
		echo '<input type="text" class="form-control" ';
		echo 'id="name" name="name" ';
		echo 'value="';
		echo $user['name'] ?? '';
		echo '" required maxlength="50" ';
		echo 'data-typeahead="';
		echo $app['typeahead']->get([['usernames', [
			'schema'	=> $app['tschema'],
		]]]);
		echo '" ';
		echo 'data-typeahead-render="';
		echo htmlspecialchars(json_encode([
			'exists_check'	=> 10,
			'exists_omit'	=> $edit_user_cached['name'] ?? '',
		]));
		echo '">';
		echo '</div>';
		echo '<span class="help-block hidden exists_query_results">';
		echo 'Reeds gebruikt: ';
		echo '<span class="query_results">';
		echo '</span>';
		echo '</span>';
		echo '<span id="username_exists" ';
		echo 'class="help-block hidden exists_msg">';
		echo 'Deze Gebruikersnaam bestaat reeds!</span>';
		echo '</div>';
	}

	if ($fullname_edit)
	{
		echo '<div class="form-group">';
		echo '<label for="fullname" class="control-label">';
		echo 'Volledige Naam</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-user"></span></span>';
		echo '<input type="text" class="form-control" ';
		echo 'id="fullname" name="fullname" ';
		echo 'value="';
		echo $user['fullname'] ?? '';
		echo '" maxlength="100">';
		echo '</div>';
		echo '<p>';
		echo 'Voornaam en Achternaam';
		echo '</p>';
		echo '</div>';
	}

	if (!isset($fullname_access))
	{
		$fullname_access = $add && !$intersystem_code ? false : 'admin';
	}

	echo $app['access_control']->get_radio_buttons(
		'users_fullname',
		$fullname_access,
		false,
		'fullname_access',
		'xs',
		'Zichtbaarheid Volledige Naam'
	);

	echo '<div class="form-group">';
	echo '<label for="postcode" class="control-label">';
	echo 'Postcode</label>';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon">';
	echo '<span class="fa fa-map-marker"></span></span>';
	echo '<input type="text" class="form-control" ';
	echo 'id="postcode" name="postcode" ';
	echo 'value="';
	echo $user['postcode'] ?? '';
	echo '" ';
	echo 'required maxlength="6" ';
	echo 'data-typeahead="';
	echo $app['typeahead']->get([['postcodes', [
		'schema'	=> $app['tschema'],
	]]]);
	echo '">';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="birthday" class="control-label">';
	echo 'Geboortedatum</label>';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon">';
	echo '<span class="fa fa-calendar"></span></span>';
	echo '<input type="text" class="form-control" ';
	echo 'id="birthday" name="birthday" ';
	echo 'value="';

	if (isset($user['birthday']) && !empty($user['birtday']))
	{
		echo $app['date_format']->get($user['birthday'], 'day', $app['tschema']);
	}

	echo '" ';
	echo 'data-provide="datepicker" ';
	echo 'data-date-format="';
	echo $app['date_format']->datepicker_format($app['tschema']);
	echo '" ';
	echo 'data-date-default-view="2" ';
	echo 'data-date-end-date="';
	echo $app['date_format']->get('', 'day', $app['tschema']);
	echo '" ';
	echo 'data-date-language="nl" ';
	echo 'data-date-start-view="2" ';
	echo 'data-date-today-highlight="true" ';
	echo 'data-date-autoclose="true" ';
	echo 'data-date-immediate-updates="true" ';
	echo 'data-date-orientation="bottom" ';
	echo 'placeholder="';
	echo $app['date_format']->datepicker_placeholder($app['tschema']);
	echo '">';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="hobbies" class="control-label">';
	echo 'Hobbies, interesses</label>';
	echo '<textarea name="hobbies" id="hobbies" ';
	echo 'class="form-control" maxlength="500">';
	echo $user['hobbies'] ?? '';
	echo '</textarea>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="comments" class="control-label">Commentaar</label>';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon">';
	echo '<span class="fa fa-comment-o"></span></span>';
	echo '<input type="text" class="form-control" ';
	echo 'id="comments" name="comments" ';
	echo 'value="';
	echo $user['comments'] ?? '';
	echo '">';
	echo '</div>';
	echo '</div>';

	if ($app['s_admin'])
	{
		echo '<div class="form-group">';
		echo '<label for="accountrole" class="control-label">';
		echo 'Rechten / Rol</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-hand-paper-o"></span></span>';
		echo '<select id="accountrole" name="accountrole" ';
		echo 'class="form-control">';
		echo get_select_options(cnst::ROLE_ARY, $user['accountrole']);
		echo '</select>';
		echo '</div>';
		echo '</div>';

		echo '<div class="pan-sub" id="presharedkey_panel">';
		echo '<div class="form-group" id="presharedkey_formgroup">';
		echo '<label for="presharedkey" class="control-label">';
		echo 'Preshared Key</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-key"></span></span>';
		echo '<input type="text" class="form-control" ';
		echo 'id="presharedkey" name="presharedkey" ';
		echo 'value="';
		echo $user['presharedkey'] ?? '';
		echo '" maxlength="80">';
		echo '</div>';
		echo '<p>Vul dit enkel in voor een interSysteem Account ';
		echo 'van een Systeem op een eLAS-server.</p>';
		echo '</div>';
		echo '</div>';

		echo '<div class="form-group">';
		echo '<label for="status" class="control-label">';
		echo 'Status</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-star-o"></span></span>';
		echo '<select id="status" name="status" class="form-control">';
		echo get_select_options(cnst::STATUS_ARY, $user['status']);
		echo '</select>';
		echo '</div>';
		echo '</div>';

		if (empty($user['adate']) && $app['s_admin'])
		{
			echo '<div id="activate" class="bg-success pan-sub">';

			echo '<div class="form-group">';
			echo '<label for="password" class="control-label">';
			echo 'Paswoord</label>';
			echo '<div class="input-group">';
			echo '<span class="input-group-addon">';
			echo '<span class="fa fa-key"></span></span>';
			echo '<input type="text" class="form-control" ';
			echo 'id="password" name="password" ';
			echo 'value="';
			echo $password ?? '';
			echo '" required>';
			echo '<span class="input-group-btn">';
			echo '<button class="btn btn-default" ';
			echo 'type="button" id="generate">';
			echo 'Genereer</button>';
			echo '</span>';
			echo '</div>';
			echo '</div>';

			echo '<div class="form-group">';
			echo '<label for="notify" class="control-label">';
			echo '<input type="checkbox" name="notify" id="notify"';
			echo ' checked="checked"';
			echo '> ';
			echo 'Verstuur een E-mail met het ';
			echo 'paswoord naar de gebruiker. ';
			echo 'Dit kan enkel wanneer het account ';
			echo 'de status actief heeft en ';
			echo 'een E-mail adres is ingesteld.';
			echo '</label>';
			echo '</div>';

			echo '</div>';
		}

		echo '<div class="form-group">';
		echo '<label for="admincomment" class="control-label">';
		echo 'Commentaar van de admin</label>';
		echo '<textarea name="admincomment" id="admincomment" ';
		echo 'class="form-control" maxlength="200">';
		echo $user['admincomment'] ?? '';
		echo '</textarea>';
		echo '</div>';

		echo '<div class="pan-sub">';

		echo '<h2>Limieten&nbsp;';

		if ($user['minlimit'] === '' && $user['maxlimit'] === '')
		{
			echo '<button class="btn btn-default" ';
			echo 'title="Limieten instellen" data-toggle="collapse" ';
			echo 'data-target="#limits_pan" type="button">';
			echo 'Instellen</button>';
		}

		echo '</h2>';

		echo '<div id="limits_pan"';

		if ($user['minlimit'] === '' && $user['maxlimit'] === '')
		{
			echo ' class="collapse"';
		}

		echo '>';

		echo '<div class="form-group">';
		echo '<label for="minlimit" class="control-label">';
		echo 'Minimum Account Limiet</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-arrow-down"></span> ';
		echo $app['config']->get('currency', $app['tschema']);
		echo '</span>';
		echo '<input type="number" class="form-control" ';
		echo 'id="minlimit" name="minlimit" ';
		echo 'value="';
		echo $user['minlimit'] ?? '';
		echo '">';
		echo '</div>';
		echo '<p>Vul enkel in wanneer je een individueel ';
		echo 'afwijkende minimum limiet wil instellen ';
		echo 'voor dit account. Als dit veld leeg is, ';
		echo 'dan is de algemeen geldende ';
		echo aphp('config',
			['active_tab' => 'balance'],
			'Minimum Systeemslimiet');
		echo ' ';
		echo 'van toepassing. ';

		if ($app['config']->get('minlimit', $app['tschema']) === '')
		{
			echo 'Er is momenteel <strong>geen</strong> algemeen ';
			echo 'geledende Minimum Systeemslimiet ingesteld. ';
		}
		else
		{
			echo 'De algemeen geldende ';
			echo 'Minimum Systeemslimiet bedraagt <strong>';
			echo $app['config']->get('minlimit', $app['tschema']);
			echo ' ';
			echo $app['config']->get('currency', $app['tschema']);
			echo '</strong>. ';
		}

		echo 'Dit veld wordt bij aanmaak van een ';
		echo 'gebruiker vooraf ingevuld met de "';
		echo aphp(
			'config',
			['active_tab' => 'balance'],
			'Preset Individuele Minimum Account Limiet'
		);
		echo '" ';
		echo 'die gedefiniëerd is in de instellingen.';

		if ($app['config']->get('preset_minlimit', $app['tschema']) !== '')
		{
			echo ' De Preset bedraagt momenteel <strong>';
			echo $app['config']->get('preset_minlimit', $app['tschema']);
			echo '</strong>.';
		}

		echo '</p>';
		echo '</div>';

		echo '<div class="form-group">';
		echo '<label for="maxlimit" class="control-label">';
		echo 'Maximum Account Limiet</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon">';
		echo '<span class="fa fa-arrow-up"></span> ';
		echo $app['config']->get('currency', $app['tschema']);
		echo '</span>';
		echo '<input type="number" class="form-control" ';
		echo 'id="maxlimit" name="maxlimit" ';
		echo 'value="';
		echo $user['maxlimit'] ?? '';
		echo '">';
		echo '</div>';

		echo '<p>Vul enkel in wanneer je een individueel ';
		echo 'afwijkende maximum limiet wil instellen ';
		echo 'voor dit account. Als dit veld leeg is, ';
		echo 'dan is de algemeen geldende ';
		echo aphp(
			'config',
			['active_tab' => 'balance'],
			'Maximum Systeemslimiet'
		);
		echo ' ';
		echo 'van toepassing. ';

		if ($app['config']->get('maxlimit', $app['tschema']) === '')
		{
			echo 'Er is momenteel <strong>geen</strong> algemeen ';
			echo 'geledende Maximum Systeemslimiet ingesteld. ';
		}
		else
		{
			echo 'De algemeen geldende Maximum ';
			echo 'Systeemslimiet bedraagt <strong>';
			echo $app['config']->get('maxlimit', $app['tschema']);
			echo ' ';
			echo $app['config']->get('currency', $app['tschema']);
			echo '</strong>. ';
		}

		echo 'Dit veld wordt bij aanmaak van een gebruiker ';
		echo 'vooraf ingevuld wanneer "';
		echo aphp(
			'config',
			['active_tab' => 'balance'],
			'Preset Individuele Maximum Account Limiet'
		);
		echo '" ';
		echo 'is ingevuld in de instellingen.';

		if ($app['config']->get('preset_maxlimit', $app['tschema']) !== '')
		{
			echo ' De Preset bedraagt momenteel <strong>';
			echo $app['config']->get('preset_maxlimit', $app['tschema']);
			echo '</strong>.';
		}

		echo '</p>';

		echo '</div>';
		echo '</div>';
		echo '</div>';

		$contacts_format = [
			'adr'	=> [
				'fa'		=> 'map-marker',
				'lbl'		=> 'Adres',
				'explain'	=> 'Voorbeeldstraat 23, 4520 Voorbeeldgemeente',
			],
			'gsm'	=> [
				'fa'		=> 'mobile',
				'lbl'		=> 'GSM',
			],
			'tel'	=> [
				'fa'		=> 'phone',
				'lbl'		=> 'Telefoon',
			],
			'mail'	=> [
				'fa'		=> 'envelope-o',
				'lbl'		=> 'E-mail',
				'type'		=> 'email',
				'disabled'	=> true,     // Prevent browser fill-in, removed by js.
			],
			'web'	=> [
				'fa'		=> 'link',
				'lbl'		=> 'Website',
				'type'		=> 'url',
			],
		];

		echo '<div class="bg-warning pan-sub">';
		echo '<h2><i class="fa fa-map-marker"></i> Contacten</h2>';

		echo '<p>Meer contacten kunnen toegevoegd worden ';
		echo 'vanuit de profielpagina met de knop ';
		echo 'Toevoegen bij de contactinfo ';
		echo $add ? 'nadat de gebruiker gecreëerd is' : '';
		echo '.</p>';

		foreach ($contact as $key => $c)
		{
			$name = 'contact[' . $key . '][value]';

			echo '<div class="pan-sab">';

			echo '<div class="form-group">';
			echo '<label for="';
			echo $name;
			echo '" class="control-label">';
			echo $contacts_format[$c['abbrev']]['lbl'] ?? $c['abbrev'];
			echo '</label>';
			echo '<div class="input-group">';
			echo '<span class="input-group-addon">';
			echo '<i class="fa fa-';
			echo $contacts_format[$c['abbrev']]['fa'] ?? 'question-mark';
			echo '"></i>';
			echo '</span>';
			echo '<input class="form-control" id="';
			echo $name;
			echo '" name="';
			echo $name;
			echo '" ';
			echo 'value="';
			echo $c['value'] ?? '';
			echo '" type="';
			echo $contacts_format[$c['abbrev']]['type'] ?? 'text';
			echo '" ';
			echo isset($contacts_format[$c['abbrev']]['disabled']) ? 'disabled ' : '';
			echo 'data-access="contact_access_' . $key . '">';
			echo '</div>';
			echo '<p>';
			echo $contacts_format[$c['abbrev']]['explain'] ?? '';
			echo '</p>';
			echo '</div>';

			if (!isset($c['flag_public']))
			{
				$c['flag_public'] = false;
			}

			echo $app['access_control']->get_radio_buttons($c['abbrev'], $c['flag_public'], false, 'contact_access_' . $key);

			echo '<input type="hidden" ';
			echo 'name="contact['. $key . '][id]" value="' . $c['id'] . '">';
			echo '<input type="hidden" ';
			echo 'name="contact['. $key . '][name]" value="' . $c['name'] . '">';
			echo '<input type="hidden" ';
			echo 'name="contact['. $key . '][abbrev]" value="' . $c['abbrev'] . '">';

			echo '</div>';
		}

		echo '</div>';
	}

	echo '<div class="form-group">';
	echo '<label for="cron_saldo" class="control-label">';
	echo '<input type="checkbox" name="cron_saldo" id="cron_saldo"';
	echo $user['cron_saldo'] ? ' checked="checked"' : '';
	echo '>	';
	echo 'Periodieke Overzichts E-mail';
	echo '</label>';
	echo '</div>';

	$canc = $edit ? ['id' => $edit] : ['status' => 'active'];
	$btn = $edit ? 'primary' : 'success';
	echo aphp('users', $canc, 'Annuleren', 'btn btn-default') . '&nbsp;';
	echo '<input type="submit" name="zend" ';
	echo 'value="Opslaan" class="btn btn-';
	echo $btn . '">';
	echo $app['form_token']->get_hidden_input();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 * status definitions
 */

$st = [
	'active'	=> [
		'lbl'	=> $app['s_admin'] ? 'Actief' : 'Alle',
		'sql'	=> 'u.status in (1, 2)',
		'st'	=> [1, 2],
	],
	'new'		=> [
		'lbl'	=> 'Instappers',
		'sql'	=> 'u.status = 1 and u.adate > ?',
		'sql_bind'	=> gmdate('Y-m-d H:i:s', $app['new_user_treshold']),
		'cl'	=> 'success',
		'st'	=> 3,
	],
	'leaving'	=> [
		'lbl'	=> 'Uitstappers',
		'sql'	=> 'u.status = 2',
		'cl'	=> 'danger',
		'st'	=> 2,
	],
];

if ($app['s_admin'])
{
	$st = $st + [
		'inactive'	=> [
			'lbl'	=> 'Inactief',
			'sql'	=> 'u.status = 0',
			'cl'	=> 'inactive',
			'st'	=> 0,
		],
		'ip'		=> [
			'lbl'	=> 'Info-pakket',
			'sql'	=> 'u.status = 5',
			'cl'	=> 'warning',
			'st'	=> 5,
		],
		'im'		=> [
			'lbl'	=> 'Info-moment',
			'sql'	=> 'u.status = 6',
			'cl'	=> 'info',
			'st'	=> 6
		],
		'extern'	=> [
			'lbl'	=> 'Extern',
			'sql'	=> 'u.status = 7',
			'cl'	=> 'extern',
			'st'	=> 7,
		],
		'all'		=> [
			'lbl'	=> 'Alle',
			'sql'	=> '1 = 1',
		],
	];
}

$st_class_ary = [
	0 => 'inactive',
	2 => 'danger',
	3 => 'success',
	5 => 'warning',
	6 => 'info',
	7 => 'extern',
];

/*
 * Show a user
 */

if ($id)
{
	$s_owner = !$app['s_guest']
		&& $app['s_group_self']
		&& $app['s_id'] == $id
		&& $id;

	$user_mail_cc = $app['is_http_post'] ? $user_mail_cc : 1;

	$user = $app['user_cache']->get($id, $app['tschema']);

	if (!$app['s_admin'] && !in_array($user['status'], [1, 2]))
	{
		$app['alert']->error('Je hebt geen toegang tot deze gebruiker.');
		cancel();
	}

	if ($app['s_admin'])
	{
		$count_transactions = $app['db']->fetchColumn('select count(*)
			from ' . $app['tschema'] . '.transactions
			where id_from = ?
				or id_to = ?', [$id, $id]);
	}

	$mail_to = $app['mail_addr_user']->get($user['id'], $app['tschema']);
	$mail_from = $app['s_schema']
		&& !$app['s_master']
		&& !$app['s_elas_guest']
			? $app['mail_addr_user']->get($app['s_id'], $app['s_schema'])
			: [];

	$sql_bind = [$user['letscode']];

	if ($link && isset($st[$link]))
	{
		$and_status = isset($st[$link]['sql'])
			? ' and ' . $st[$link]['sql']
			: '';

		if (isset($st[$link]['sql_bind']))
		{
			$sql_bind[] = $st[$link]['sql_bind'];
		}
	}
	else
	{
		$and_status = $app['s_admin'] ? '' : ' and u.status in (1, 2) ';
	}

	$next = $app['db']->fetchColumn('select id
		from ' . $app['tschema'] . '.users u
		where u.letscode > ?
		' . $and_status . '
		order by u.letscode asc
		limit 1', $sql_bind);

	$prev = $app['db']->fetchColumn('select id
		from ' . $app['tschema'] . '.users u
		where u.letscode < ?
		' . $and_status . '
		order by u.letscode desc
		limit 1', $sql_bind);

	$intersystem_missing = false;

	if ($app['s_admin'] && $user['accountrole'] === 'interlets'
		&& $app['config']->get('interlets_en', $app['tschema'])
		&& $app['config']->get('template_lets', $app['tschema']))
	{
		$intersystem_id = $app['db']->fetchColumn('select id
			from ' . $app['tschema'] . '.letsgroups
			where localletscode = ?', [$user['letscode']]);

		if (!$intersystem_id)
		{
			$intersystem_missing = true;
		}
	}
	else
	{
		$intersystem_id = false;
	}

	$app['assets']->add([
		'leaflet',
		'jqplot',
		'user.js',
		'plot_user_transactions.js',
	]);

	if ($app['s_admin'] || $s_owner)
	{
		$app['assets']->add([
			'fileupload',
			'user_img.js',
		]);
	}

	if ($app['s_admin'] || $s_owner)
	{
		$title = $app['s_admin'] ? 'Gebruiker' : 'Mijn gegevens';

		$top_buttons .= aphp(
			'users',
			['edit' => $id],
			'Aanpassen',
			'btn btn-primary',
			$title . ' aanpassen',
			'pencil',
			true);

		$top_buttons .= aphp(
			'users',
			['pw' => $id],
			'Paswoord aanpassen',
			'btn btn-info',
			'Paswoord aanpassen',
			'key',
			true);
	}

	if ($app['s_admin'] && !$count_transactions && !$s_owner)
	{
		$top_buttons .= aphp('users',
			['del' => $id],
			'Verwijderen',
			'btn btn-danger',
			'Gebruiker verwijderen',
			'times',
			true);
	}

	if ($app['s_admin']
		|| (!$s_owner && $user['status'] !== 7
			&& !($app['s_guest'] && $app['s_group_self'])))
	{
		$tus = ['add' => 1, 'tuid' => $id];

		if (!$app['s_group_self'])
		{
			$tus['tus'] = $app['tschema'];
		}

		$top_buttons .= aphp('transactions', $tus, 'Transactie',
			'btn btn-warning', 'Transactie naar ' . link_user($user, $app['tschema'], false),
			'exchange', true, false, $app['s_schema']);
	}

	$top_buttons_right = '<span class="btn-group" role="group">';

	$prev_url = $next_url = '';

	if ($prev)
	{
		$param_prev = ['id' => $prev];

		if ($link)
		{
			$param_prev['link'] = $link;
		}

		$prev_url .= generate_url('users', $param_prev);
	}

	if ($next)
	{
		$param_next = ['id' => $next];

		if ($link)
		{
			$param_next['link'] = $link;
		}

		$next_url = generate_url('users', $param_next);
	}

	$top_buttons_right .= btn_item_nav($prev_url, false, false);
	$top_buttons_right .= btn_item_nav($next_url, true, true);
	$top_buttons_right .= aphp(
		'users',
		['status' => $link ? $link : 'active'],
		'', 'btn btn-default', 'Lijst', 'users');
	$top_buttons_right .= '</span>';

	$status = $user['status'];
	$status = ($app['new_user_treshold'] < strtotime($user['adate']) && $status == 1) ? 3 : $status;

	$h_status_ary = cnst::STATUS_ARY;
	$h_status_ary[3] = 'Instapper';

	$h1 = $s_owner && !$app['s_admin'] ? 'Mijn gegevens: ' : '';
	$h1 .= link_user($user, $app['tschema']);

	if ($status != 1)
	{
		$h1 .= ' <small><span class="text-' . $st_class_ary[$status] . '">';
		$h1 .= $h_status_ary[$status];
		$h1 .= '</span></small>';
	}

	if ($app['s_admin'])
	{
		if ($intersystem_missing)
		{
			$h1 .= ' <span class="label label-warning label-sm">';
			$h1 .= '<i class="fa fa-exclamation-triangle"></i> ';
			$h1 .= 'De interSysteem-verbinding ontbreekt</span>';
		}
		else if ($intersystem_id)
		{
			$h1 .= ' ' . aphp('intersystem',
				['id' => $intersystem_id],
				'Gekoppeld interSysteem',
				'btn btn-default',
				'Gekoppelde interSysteem');
		}
	}

	$fa = 'user';

	include __DIR__ . '/include/header.php';

	echo '<div class="row">';
	echo '<div class="col-md-6">';

	echo '<div class="panel panel-default">';
	echo '<div class="panel-body text-center ';
	echo 'center-block" id="img_user">';

	$show_img = $user['PictureFile'] ? true : false;

	$user_img = $show_img ? '' : ' style="display:none;"';
	$no_user_img = $show_img ? ' style="display:none;"' : '';

	echo '<img id="user_img"';
	echo $user_img;
	echo ' class="img-rounded img-responsive center-block" ';
	echo 'src="';

	if ($user['PictureFile'])
	{
		echo $app['s3_url'] . $user['PictureFile'];
	}
	else
	{
		echo $app['rootpath'] . 'gfx/1.gif';
	}

	echo '" ';
	echo 'data-bucket-url="' . $app['s3_url'] . '"></img>';

	echo '<div id="no_user_img"';
	echo $no_user_img;
	echo '>';
	echo '<i class="fa fa-user fa-5x text-muted"></i>';
	echo '<br>Geen profielfoto</div>';

	echo '</div>';

	if ($app['s_admin'] || $s_owner)
	{
		$attr = ['id'	=> 'btn_remove'];
		if (!$user['PictureFile'])
		{
			$attr['style'] = 'display:none;';
		}

		echo '<div class="panel-footer">';
		echo '<span class="btn btn-success fileinput-button">';
		echo '<i class="fa fa-plus" id="img_plus"></i> Foto opladen';
		echo '<input id="fileupload" type="file" name="image" ';
		echo 'data-url="';
		echo generate_url('users', ['img' => 1, 'id' => $id]);
		echo '" ';
		echo 'data-data-type="json" data-auto-upload="true" ';
		echo 'data-accept-file-types="/(\.|\/)(jpe?g)$/i" ';
		echo 'data-max-file-size="999000" data-image-max-width="400" ';
		echo 'data-image-crop="true" ';
		echo 'data-image-max-height="400"></span>&nbsp;';

		echo aphp(
			'users',
			['img_del' => 1, 'id' => $id],
			'Foto verwijderen',
			'btn btn-danger',
			false,
			'times',
			false,
			$attr);

		echo '<p class="text-warning">';
		echo 'Je foto moet in het jpg/jpeg formaat zijn. ';
		echo 'Je kan ook een foto hierheen verslepen.</p>';
		echo '</div>';
	}

	echo '</div></div>';

	echo '<div class="col-md-6">';

	echo '<div class="panel panel-default printview">';
	echo '<div class="panel-heading">';
	echo '<dl>';

	$fullname_access = $user['fullname_access'] ?: 'admin';

	echo '<dt>';
	echo 'Volledige naam';
	echo '</dt>';

	if ($app['s_admin']
		|| $s_owner
		|| $app['access_control']->is_visible($fullname_access))
	{
		echo get_dd($user['fullname'] ?? '');
	}
	else
	{
		echo '<dd>';
		echo '<span class="btn btn-default btn-xs">';
		echo 'verborgen</span>';
		echo '</dd>';
	}

	if ($app['s_admin'])
	{
		echo '<dt>';
		echo 'Zichtbaarheid Volledige Naam';
		echo '</dt>';
		echo '<dd>';
		echo $app['access_control']->get_label($fullname_access);
		echo '</dd>';
	}

	echo '<dt>';
	echo 'Postcode';
	echo '</dt>';
	echo get_dd($user['postcode'] ?? '');

	if ($app['s_admin'] || $s_owner)
	{
		echo '<dt>';
		echo 'Geboortedatum';
		echo '</dt>';
		if (isset($user['birthday']))
		{
			echo $app['date_format']->get($user['birthday'], 'day', $app['tschema']);
		}
		else
		{
			echo '<dd><i class="fa fa-times"></i></dd>';
		}
	}

	echo '<dt>';
	echo 'Hobbies / Interesses';
	echo '</dt>';
	echo get_dd($user['hobbies'] ?? '');

	echo '<dt>';
	echo 'Commentaar';
	echo '</dt>';
	echo get_dd($user['comments'] ?? '');

	if ($app['s_admin'])
	{
		echo '<dt>';
		echo 'Tijdstip aanmaak';
		echo '</dt>';

		if (isset($user['cdate']))
		{
			echo get_dd($app['date_format']->get($user['cdate'], 'min', $app['tschema']));
		}
		else
		{
			echo '<dd><i class="fa fa-times"></i></dd>';
		}

		echo '<dt>';
		echo 'Tijdstip activering';
		echo '</dt>';

		if (isset($user['adate']))
		{
			echo get_dd($app['date_format']->get($user['adate'], 'min', $app['tschema']));
		}
		else
		{
			echo '<dd><i class="fa fa-times"></i></dd>';
		}

		echo '<dt>';
		echo 'Laatste login';
		echo '</dt>';

		if (isset($user['lastlogin']))
		{
			echo get_dd($app['date_format']->get($user['lastlogin'], 'min', $app['tschema']));
		}
		else
		{
			echo '<dd><i class="fa fa-times"></i></dd>';
		}

		echo '<dt>';
		echo 'Rechten / rol';
		echo '</dt>';
		echo get_dd(cnst::ROLE_ARY[$user['accountrole']]);

		echo '<dt>';
		echo 'Status';
		echo '</dt>';
		echo get_dd(cnst::STATUS_ARY[$user['status']]);

		echo '<dt>';
		echo 'Commentaar van de admin';
		echo '</dt>';
		echo get_dd($user['admincomment'] ?? '');
	}

	echo '<dt>Saldo</dt>';
	echo '<dd>';
	echo '<span class="label label-info">';
	echo $user['saldo'];
	echo'</span>&nbsp;';
	echo $app['config']->get('currency', $app['tschema']);
	echo '</dd>';

	if ($user['minlimit'] !== '')
	{
		echo '<dt>Minimum limiet</dt>';
		echo '<dd>';
		echo '<span class="label label-danger">';
		echo $user['minlimit'];
		echo '</span>&nbsp;';
		echo $app['config']->get('currency', $app['tschema']);
		echo '</dd>';
	}

	if ($user['maxlimit'] !== '')
	{
		echo '<dt>Maximum limiet</dt>';
		echo '<dd>';
		echo '<span class="label label-success">';
		echo $user['maxlimit'];
		echo '</span>&nbsp;';
		echo $app['config']->get('currency', $app['tschema']);
		echo '</dd>';
	}

	if ($app['s_admin'] || $s_owner)
	{
		echo '<dt>';
		echo 'Periodieke Overzichts E-mail';
		echo '</dt>';
		echo $user['cron_saldo'] ? 'Aan' : 'Uit';
		echo '</dl>';
	}

	echo '</div></div></div></div>';

	echo '<div id="contacts" ';
	echo 'data-url="' . $app['rootpath'];
	echo 'contacts.php?inline=1&uid=' . $id;
	echo '&';
	echo http_build_query(get_session_query_param());
	echo '"></div>';

	// response form

	if ($app['s_elas_guest'])
	{
		$placeholder = 'Als eLAS gast kan je niet het E-mail formulier gebruiken.';
	}
	else if ($s_owner)
	{
		$placeholder = 'Je kan geen E-mail berichten naar jezelf verzenden.';
	}
	else if (!count($mail_to))
	{
		$placeholder = 'Er is geen E-mail adres bekend van deze gebruiker.';
	}
	else if (!count($mail_from))
	{
		$placeholder = 'Om het E-mail formulier te gebruiken moet een E-mail adres ingesteld zijn voor je eigen Account.';
	}
	else
	{
		$placeholder = '';
	}

	$disabled = !$app['s_schema']
		|| !count($mail_to)
		|| !count($mail_from)
		|| $s_owner;

	echo '<h3><i class="fa fa-envelop-o"></i> ';
	echo 'Stuur een bericht naar ';
	echo  link_user($id, $app['tschema']);
	echo '</h3>';
	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post"">';

	echo '<div class="form-group">';
	echo '<textarea name="user_mail_content" rows="6" placeholder="';
	echo $placeholder . '" ';
	echo 'class="form-control" required';
	echo $disabled ? ' disabled' : '';
	echo '>';
	echo $user_mail_content ?? '';
	echo '</textarea>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="user_mail_cc" class="control-label">';
	echo '<input type="checkbox" name="user_mail_cc" ';
	echo 'id="user_mail_cc" value="1"';
	echo $user_mail_cc ? ' checked="checked"' : '';
	echo '> Stuur een kopie naar mijzelf';
	echo '</label>';
	echo '</div>';

	echo '<input type="submit" name="user_mail_submit" ';
	echo 'value="Versturen" class="btn btn-default"';
	echo $disabled ? ' disabled' : '';
	echo '>';

	echo '</form>';

	echo '</div>';
	echo '</div>';

	//

	echo '<div class="row">';
	echo '<div class="col-md-12">';

	echo '<h3>Saldo: <span class="label label-info">';
	echo $user['saldo'];
	echo '</span> ';
	echo $app['config']->get('currency', $app['tschema']);
	echo '</h3>';
	echo '</div></div>';

	echo '<div class="row print-hide">';
	echo '<div class="col-md-6">';
	echo '<div id="chartdiv" data-height="480px" data-width="960px" ';
	echo 'data-user-id="';
	echo $id;
	echo '" ';
	echo 'data-days="';
	echo $tdays;
	echo '"></div>';
	echo '</div>';
	echo '<div class="col-md-6">';
	echo '<div id="donutdiv" data-height="480px" ';
	echo 'data-width="960px"></div>';
	echo '<h4>Interacties laatste jaar</h4>';
	echo '</div>';
	echo '</div>';

	if ($user['status'] == 1 || $user['status'] == 2)
	{
		echo '<div id="messages" ';
		echo 'data-url="';
		echo $app['rootpath'];
		echo 'messages.php?inline=1&f[uid]=';
		echo $id;
		echo '&';
		echo http_build_query(get_session_query_param());
		echo '" class="print-hide"></div>';
	}

	echo '<div id="transactions" ';
	echo 'data-url="';
	echo $app['rootpath'];
	echo 'transactions.php?inline=1&f[uid]=';
	echo $id;
	echo '&';
	echo http_build_query(get_session_query_param());
	echo '" class="print-hide"></div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/*
 * List all users
 */

if (!$app['p_view'])
{
	cancel();
}

$v_list = $app['p_view'] === 'list';
$v_tiles = $app['p_view'] === 'tiles';
$v_map = $app['p_view'] === 'map';

$sql_bind = [];
$params = [];

if (!isset($st[$status]))
{
	cancel();
}

if (isset($st[$status]['sql_bind']))
{
	$sql_bind[] = $st[$status]['sql_bind'];
}

$params = [
	'status'	=> $status,
];

$ref_geo = [];

if ($v_list)
{
	$type_contact = $app['db']->fetchAll('select id, abbrev, name
		from ' . $app['tschema'] . '.type_contact');

	$columns = [
		'u'		=> [
			'letscode'		=> 'Code',
			'name'			=> 'Naam',
			'fullname'		=> 'Volledige naam',
			'postcode'		=> 'Postcode',
			'accountrole'	=> 'Rol',
			'saldo'			=> 'Saldo',
			'saldo_date'	=> 'Saldo op ',
			'minlimit'		=> 'Min',
			'maxlimit'		=> 'Max',
			'comments'		=> 'Commentaar',
			'hobbies'		=> 'Hobbies/interesses',
		],
	];

	if ($app['s_admin'])
	{
		$columns['u'] += [
			'admincomment'	=> 'Admin commentaar',
			'cron_saldo'	=> 'Periodieke Overzichts E-mail',
			'cdate'			=> 'Gecreëerd',
			'mdate'			=> 'Aangepast',
			'adate'			=> 'Geactiveerd',
			'lastlogin'		=> 'Laatst ingelogd',
		];
	}

	foreach ($type_contact as $tc)
	{
		$columns['c'][$tc['abbrev']] = $tc['name'];
	}

	if (!$app['s_elas_guest'])
	{
		$columns['d'] = [
			'distance'	=> 'Afstand',
		];
	}

	$columns['m'] = [
		'wants'		=> 'Vraag',
		'offers'	=> 'Aanbod',
		'total'		=> 'Vraag en aanbod',
	];

	$message_type_filter = [
		'wants'		=> ['want' => 'on'],
		'offers'	=> ['offer' => 'on'],
		'total'		=> '',
	];

	$columns['a'] = [
		'trans'		=> [
			'in'	=> 'Transacties in',
			'out'	=> 'Transacties uit',
			'total'	=> 'Transacties totaal',
		],
		'amount'	=> [
			'in'	=> $app['config']->get('currency', $app['tschema']) . ' in',
			'out'	=> $app['config']->get('currency', $app['tschema']) . ' uit',
			'total'	=> $app['config']->get('currency', $app['tschema']) . ' totaal',
		],
	];

	$columns['p'] = [
		'c'	=> [
			'adr_split'	=> '.',
		],
		'a'	=> [
			'days'	=> '.',
			'code'	=> '.',
		],
		'u'	=> [
			'saldo_date'	=> '.',
		],
	];

	$session_users_columns_key = 'users_columns_' . $app['s_accountrole'];
	$session_users_columns_key .= $app['s_elas_guest'] ? '_elas' : '';

	if (isset($_GET['sh']))
	{
		$show_columns = $_GET['sh'] ?? [];

		$show_columns = array_intersect_key_recursive($show_columns, $columns);

		$app['session']->set($session_users_columns_key, $show_columns);
	}
	else
	{
		if ($app['s_admin'] || $app['s_guest'])
		{
			$preset_columns = [
				'u'	=> [
					'letscode'	=> 1,
					'name'		=> 1,
					'postcode'	=> 1,
					'saldo'		=> 1,
				],
			];
		}
		else
		{
			$preset_columns = [
				'u' => [
					'letscode'	=> 1,
					'name'		=> 1,
					'postcode'	=> 1,
					'saldo'		=> 1,
				],
				'c'	=> [
					'gsm'	=> 1,
					'tel'	=> 1,
					'adr'	=> 1,
				],
				'd'	=> [
					'distance'	=> 1,
				],
			];
		}

		if ($app['s_elas_guest'])
		{
			unset($columns['d']['distance']);
		}

		$show_columns = $app['session']->get($session_users_columns_key) ?? $preset_columns;
	}

	$adr_split = $show_columns['p']['c']['adr_split'] ?? '';
	$activity_days = $show_columns['p']['a']['days'] ?? 365;
	$activity_days = $activity_days < 1 ? 365 : $activity_days;
	$activity_filter_code = $show_columns['p']['a']['code'] ?? '';
	$saldo_date = $show_columns['p']['u']['saldo_date'] ?? '';
	$saldo_date = trim($saldo_date);

	$users = $app['db']->fetchAll('select u.*
		from ' . $app['tschema'] . '.users u
		where ' . $st[$status]['sql'] . '
		order by u.letscode asc', $sql_bind);

// hack eLAS compatibility (in eLAND limits can be null)

	if (isset($show_columns['u']['minlimit']) || isset($show_columns['u']['maxlimit']))
	{
		foreach ($users as &$user)
		{
			$user['minlimit'] = $user['minlimit'] === -999999999 ? '' : $user['minlimit'];
			$user['maxlimit'] = $user['maxlimit'] === 999999999 ? '' : $user['maxlimit'];
		}
	}

	if (isset($show_columns['u']['fullname']))
	{
		foreach ($users as &$user)
		{
			$user['fullname_access'] = $app['xdb']->get(
				'user_fullname_access',
				$user['id'],
				$app['tschema']
			)['data']['fullname_access'] ?? 'admin';

			error_log($user['fullname_access']);
		}
	}

	if (isset($show_columns['u']['saldo_date']))
	{
		if ($saldo_date)
		{
			$saldo_date_rev = $app['date_format']->reverse($saldo_date, 'min', $app['tschema']);
		}

		if ($saldo_date_rev === '' || $saldo_date == '')
		{
			$saldo_date = $app['date_format']->get('', 'day', $app['tschema']);

			array_walk($users, function(&$user, $user_id){
				$user['saldo_date'] = $user['saldo'];
			});
		}
		else
		{
			$in = $out = [];
			$datetime = new \DateTime($saldo_date_rev);

			$rs = $app['db']->prepare('select id_to, sum(amount)
				from ' . $app['tschema'] . '.transactions
				where date <= ?
				group by id_to');

			$rs->bindValue(1, $datetime, 'datetime');

			$rs->execute();

			while($row = $rs->fetch())
			{
				$in[$row['id_to']] = $row['sum'];
			}

			$rs = $app['db']->prepare('select id_from, sum(amount)
				from ' . $app['tschema'] . '.transactions
				where date <= ?
				group by id_from');
			$rs->bindValue(1, $datetime, 'datetime');

			$rs->execute();

			while($row = $rs->fetch())
			{
				$out[$row['id_from']] = $row['sum'];
			}

			array_walk($users, function(&$user) use ($out, $in){
				$user['saldo_date'] = 0;
				$user['saldo_date'] += $in[$user['id']] ?? 0;
				$user['saldo_date'] -= $out[$user['id']] ?? 0;
			});
		}
	}

	if (isset($show_columns['c']) || (isset($show_columns['d']) && !$app['s_master']))
	{
		$c_ary = $app['db']->fetchAll('select tc.abbrev,
				c.id_user, c.value, c.flag_public
			from ' . $app['tschema'] . '.contact c, ' .
				$app['tschema'] . '.type_contact tc, ' .
				$app['tschema'] . '.users u
			where tc.id = c.id_type_contact ' .
				(isset($show_columns['c']) ? '' : 'and tc.abbrev = \'adr\' ') .
				'and c.id_user = u.id
				and ' . $st[$status]['sql'], $sql_bind);

		$contacts = [];

		foreach ($c_ary as $c)
		{
			$contacts[$c['id_user']][$c['abbrev']][] = [$c['value'], $c['flag_public']];
		}
	}

	if (isset($show_columns['d']) && !$app['s_master'])
	{
		if (($app['s_guest'] && $app['s_schema'] && !$app['s_elas_guest'])
			|| !isset($contacts[$app['s_id']]['adr']))
		{
			$my_adr = $app['db']->fetchColumn('select c.value
				from ' . $app['s_schema'] . '.contact c, ' .
					$app['s_schema'] . '.type_contact tc
				where c.id_user = ?
					and c.id_type_contact = tc.id
					and tc.abbrev = \'adr\'', [$app['s_id']]);
		}
		else if (!$app['s_guest'])
		{
			$my_adr = trim($contacts[$app['s_id']]['adr'][0][0]);
		}

		if (isset($my_adr))
		{
			$ref_geo = $app['cache']->get('geo_' . $my_adr);
		}
	}

	if (isset($show_columns['m']))
	{
		$msgs_count = [];

		if (isset($show_columns['m']['offers']))
		{
			$ary = $app['db']->fetchAll('select count(m.id), m.id_user
				from ' . $app['tschema'] . '.messages m, ' .
					$app['tschema'] . '.users u
				where msg_type = 1
					and m.id_user = u.id
					and ' . $st[$status]['sql'] . '
				group by m.id_user', $sql_bind);

			foreach ($ary as $a)
			{
				$msgs_count[$a['id_user']]['offers'] = $a['count'];
			}
		}

		if (isset($show_columns['m']['wants']))
		{
			$ary = $app['db']->fetchAll('select count(m.id), m.id_user
				from ' . $app['tschema'] . '.messages m, ' .
					$app['tschema'] . '.users u
				where msg_type = 0
					and m.id_user = u.id
					and ' . $st[$status]['sql'] . '
				group by m.id_user', $sql_bind);

			foreach ($ary as $a)
			{
				$msgs_count[$a['id_user']]['wants'] = $a['count'];
			}
		}

		if (isset($show_columns['m']['total']))
		{
			$ary = $app['db']->fetchAll('select count(m.id), m.id_user
				from ' . $app['tschema'] . '.messages m, ' .
					$app['tschema'] . '.users u
				where m.id_user = u.id
					and ' . $st[$status]['sql'] . '
				group by m.id_user', $sql_bind);

			foreach ($ary as $a)
			{
				$msgs_count[$a['id_user']]['total'] = $a['count'];
			}
		}
	}

	if (isset($show_columns['a']))
	{
		$activity = [];

		$ts = gmdate('Y-m-d H:i:s', time() - ($activity_days * 86400));
		$sql_bind = [$ts];

		$activity_filter_code = trim($activity_filter_code);

		if ($activity_filter_code)
		{
			[$code_only_activity_filter_code] = explode(' ', $activity_filter_code);
			$and = ' and u.letscode <> ? ';
			$sql_bind[] = trim($code_only_activity_filter_code);
		}
		else
		{
			$and = ' and 1 = 1 ';
		}

		$in_ary = $app['db']->fetchAll('select sum(t.amount),
				count(t.id), t.id_to
			from ' . $app['tschema'] . '.transactions t, ' .
				$app['tschema'] . '.users u
			where t.id_from = u.id
				and t.cdate > ?' . $and . '
			group by t.id_to', $sql_bind);

		$out_ary = $app['db']->fetchAll('select sum(t.amount),
				count(t.id), t.id_from
			from ' . $app['tschema'] . '.transactions t, ' .
				$app['tschema'] . '.users u
			where t.id_to = u.id
				and t.cdate > ?' . $and . '
			group by t.id_from', $sql_bind);

		foreach ($in_ary as $in)
		{
			if (!isset($activity[$in['id_to']]))
			{
				$activity[$in['id_to']] = [
					'trans'	=> ['total' => 0],
					'amount' => ['total' => 0],
				];
			}

			$activity[$in['id_to']]['trans']['in'] = $in['count'];
			$activity[$in['id_to']]['amount']['in'] = $in['sum'];
			$activity[$in['id_to']]['trans']['total'] += $in['count'];
			$activity[$in['id_to']]['amount']['total'] += $in['sum'];
		}

		foreach ($out_ary as $out)
		{
			if (!isset($activity[$out['id_from']]))
			{
				$activity[$out['id_from']] = [
					'trans'	=> ['total' => 0],
					'amount' => ['total' => 0],
				];
			}

			$activity[$out['id_from']]['trans']['out'] = $out['count'];
			$activity[$out['id_from']]['amount']['out'] = $out['sum'];
			$activity[$out['id_from']]['trans']['total'] += $out['count'];
			$activity[$out['id_from']]['amount']['total'] += $out['sum'];
		}
	}
}
else
{
	$users = $app['db']->fetchAll('select u.*
		from ' . $app['tschema'] . '.users u
		where ' . $st[$status]['sql'] . '
		order by u.letscode asc', $sql_bind);

	if ($v_list || $v_map)
	{
		$c_ary = $app['db']->fetchAll('select tc.abbrev,
			c.id_user, c.value, c.flag_public, c.id
			from ' . $app['tschema'] . '.contact c, ' .
				$app['tschema'] . '.type_contact tc
			where tc.id = c.id_type_contact
				and tc.abbrev in (\'mail\', \'tel\', \'gsm\', \'adr\')');

		$contacts = [];

		foreach ($c_ary as $c)
		{
			$contacts[$c['id_user']][$c['abbrev']][] = [
				$c['value'],
				$c['flag_public'],
				$c['id'],
			];
		}

		if (!$app['s_master'])
		{
			if ($app['s_guest'] && $app['s_schema'] && !$app['s_elas_guest'])
			{
				$my_adr = $app['db']->fetchColumn('select c.value
					from ' . $app['s_schema'] . '.contact c, ' . $app['s_schema'] . '.type_contact tc
					where c.id_user = ?
						and c.id_type_contact = tc.id
						and tc.abbrev = \'adr\'', [$app['s_id']]);
			}
			else if (!$app['s_guest'])
			{
				$my_adr = trim($contacts[$app['s_id']]['adr'][0][0]);
			}

			if (isset($my_adr))
			{
				$ref_geo = $app['cache']->get('geo_' . $my_adr);
			}
		}
	}
}

if ($app['s_admin'])
{
	$csv_en = $v_list;

	$top_buttons .= aphp(
		'users',
		['add' => 1],
		'Toevoegen',
		'btn btn-success',
		'Gebruiker toevoegen',
		'plus',
		true
	);

	if ($v_list)
	{
		$top_buttons .= '<a href="#actions" class="btn btn-info" ';
		$top_buttons .= 'title="Bulk acties"><i class="fa fa-envelope-o"></i>';
		$top_buttons .= '<span class="hidden-xs hidden-sm"> Bulk acties</span></a>';
	}

	$h1 = 'Gebruikers';
}
else
{
	$h1 = 'Leden';
}

$top_buttons_right = '';

if ($v_list)
{
	$top_buttons_right .= '<button class="btn btn-default" title="Weergave kolommen" ';
	$top_buttons_right .= 'data-toggle="collapse" data-target="#columns_show"';
	$top_buttons_right .= '><i class="fa fa-columns"></i></button>&nbsp;';
}

$top_buttons_right .= '<span class="btn-group" role="group">';

$active = $v_list ? ' active' : '';
$v_params = $params;
$v_params['view'] = 'list';
$top_buttons_right .= aphp('users', $v_params, '', 'btn btn-default' . $active, 'Lijst', 'align-justify');

$active = $v_tiles ? ' active' : '';
$v_params['view'] = 'tiles';
$top_buttons_right .= aphp('users', $v_params, '', 'btn btn-default' . $active, 'Tegels met foto\'s', 'th');

$active = $v_map ? ' active' : '';
$v_params['view'] = 'map';
unset($v_params['status']);
$top_buttons_right .= aphp('users', $v_params, '', 'btn btn-default' . $active, 'Kaart', 'map-marker');

$top_buttons_right .= '</span>';

$fa = 'users';

if ($v_list)
{
	$app['assets']->add([
		'calc_sum.js',
		'users_distance.js',
		'datepicker',
		'typeahead',
		'typeahead.js',
	]);

	if ($app['s_admin'])
	{
		$app['assets']->add([
			'summernote',
			'table_sel.js',
			'rich_edit.js',
		]);
	}
}
else if ($v_tiles)
{
	$app['assets']->add([
		'isotope',
		'users_tiles.js',
	]);
}
else if ($v_map)
{
	$app['assets']->add([
		'leaflet',
		'users_map.js',
	]);
}

include __DIR__ . '/include/header.php';

if ($v_map)
{
	$lat_add = $lng_add = 0;
	$data_users = $not_geocoded_ary = $not_present_ary = [];
	$hidden_count = $not_geocoded_count = $not_present_count = 0;

	foreach ($users as $user)
	{
		$adr = $contacts[$user['id']]['adr'][0] ?? false;

		if ($adr)
		{
			if ($adr[1] >= $app['s_access_level'])
			{
				$geo = $app['cache']->get('geo_' . $adr[0]);

				if ($geo)
				{
					$data_users[$user['id']] = [
						'name'		=> $user['name'],
						'letscode'	=> $user['letscode'],
						'lat'		=> $geo['lat'],
						'lng'		=> $geo['lng'],
					];

					$lat_add += $geo['lat'];
					$lng_add += $geo['lng'];

					continue;
				}
				else
				{
					$not_geocoded_count++;
					$not_geocoded_ary[] = [
						'uid'	=> $user['id'],
						'adr'	=> $adr[0],
						'id'	=> $adr[2],
					];
				}
			}
			else
			{
				$hidden_count++;
			}
		}
		else
		{
			$not_present_count++;
			$not_present_ary[] = $user['id'];
		}
	}

	$shown_count = count($data_users);
	$not_shown_count = $hidden_count + $not_present_count + $not_geocoded_count;
	$total_count = $shown_count + $not_shown_count;

	if (!count($ref_geo) && $shown_count)
	{
		$ref_geo['lat'] = $lat_add / $shown_count;
		$ref_geo['lng'] = $lng_add / $shown_count;
	}

	$data_users = json_encode($data_users);

	echo '<div class="row">';
	echo '<div class="col-md-12">';
	echo '<div class="users_map" id="map" ';
	echo 'data-users="';
	echo htmlspecialchars($data_users);
	echo '" ';
	echo 'data-lat="';
	echo $ref_geo['lat'] ?? '';
	echo '" ';
	echo 'data-lng="';
	echo $ref_geo['lng'] ?? '';
	echo '" ';
	echo 'data-token="' . $app['mapbox_token'] . '" ';
	echo 'data-session-param="';
	echo http_build_query(get_session_query_param());
	echo '"></div>';
	echo '</div>';
	echo '</div>';

	echo '<div class="panel panel-default">';
	echo '<div class="panel-heading">';
	echo '<p>';

	echo 'In dit kaartje wordt van elke gebruiker slechts het eerste ';
	echo 'adres in de contacten getoond. ';

	echo '</p>';

	if ($not_shown_count > 0)
	{
		echo '<p>';
		echo 'Van in totaal ' . $total_count;
		echo ' gebruikers worden ';
		echo $not_shown_count;
		echo ' adressen niet getoond wegens: ';
		echo '<ul>';

		if ($hidden_count)
		{
			echo '<li>';
			echo '<strong>';
			echo $hidden_count;
			echo '</strong> ';
			echo 'verborgen adres';
			echo '</li>';
		}

		if ($not_present_count)
		{
			echo '<li>';
			echo '<strong>';
			echo $not_present_count;
			echo '</strong> ';
			echo 'geen adres gekend';
			echo '</li>';
		}

		if ($not_geocoded_count)
		{
			echo '<li>';
			echo '<strong>';
			echo $not_geocoded_count;
			echo '</strong> ';
			echo 'coordinaten niet gekend.';
			echo '</li>';
		}

		echo '</ul>';
		echo '</p>';

		if ($not_geocoded_count)
		{
			echo '<h4>';
			echo 'Coördinaten niet gekend';
			echo '</h4>';
			echo '<p>';
			echo 'Wanneer een adres aangepast is of net toegevoegd, ';
			echo 'duurt het enige tijd eer de coordinaten zijn ';
			echo 'opgezocht door de software ';
			echo '(maximum één dag). ';
			echo 'Het kan ook dat bepaalde adressen niet vertaalbaar zijn door ';
			echo 'de "geocoding service".';
			echo '</p>';

			if ($app['s_admin'])
			{
				echo '<p>';
				echo 'Hieronder de adressen die nog niet ';
				echo 'vertaald zijn in coördinaten: ';
				echo '<ul>';
				foreach($not_geocoded_ary as $not_geocoded)
				{
					echo '<li>';
					echo aphp('contacts',
						['edit' => $not_geocoded['id'], 'uid' => $not_geocoded['uid']],
						$not_geocoded['adr']);
					echo ' gebruiker: ';
					echo link_user($not_geocoded['uid'], $app['tschema']);
					echo '</li>';
				}
				echo '</ul>';
				echo '</p>';
			}
		}

		if ($app['s_admin'] && $not_present_count)
		{
			echo '<h4>';
			echo 'Gebruikers zonder adres';
			echo '</h4>';

			echo '<p>';
			echo '<ul>';

			foreach ($not_present_ary as $not_present_addres_uid)
			{
				echo '<li>';
				echo link_user($not_present_addres_uid, $app['tschema']);
				echo '</li>';
			}

			echo '</ul>';
			echo '</p>';
		}
	}

	echo '</div>';
	echo '</div>';
}

if ($v_list || $v_tiles)
{
	echo '<form method="get" action="';
	echo generate_url('users', $params);
	echo '">';

	$params_plus = array_merge($params, get_session_query_param());

	foreach ($params_plus as $k => $v)
	{
		echo '<input type="hidden" name="' . $k . '" value="' . $v . '">';
	}
}

if ($v_list)
{
	echo '<div class="panel panel-info collapse" ';
	echo 'id="columns_show">';
	echo '<div class="panel-heading">';
	echo '<h2>Weergave kolommen</h2>';

	echo '<div class="row">';

	foreach ($columns as $group => $ary)
	{
		if ($group === 'p')
		{
			continue;
		}

		if ($group === 'm' || $group === 'c')
		{
			echo '</div>';
		}

		if ($group === 'u' || $group === 'c' || $group === 'm')
		{
			echo '<div class="col-md-4">';
		}

		if ($group === 'c')
		{
			echo '<h3>Contacten</h3>';
		}
		else if ($group === 'd')
		{
			echo '<h3>Afstand</h3>';
			echo '<p>Tussen eigen adres en adres van gebruiiker. ';
			echo 'De kolom wordt niet getoond wanneer het eigen adres ';
			echo 'niet ingesteld is.</p>';
		}
		else if ($group === 'a')
		{
			echo '<h3>Transacties/activiteit</h3>';

			echo '<div class="form-group">';
			echo '<label for="p_activity_days" ';
			echo 'class="control-label">';
			echo 'In periode';
			echo '</label>';
			echo '<div class="input-group">';
			echo '<span class="input-group-addon">';
			echo 'dagen';
			echo '</span>';
			echo '<input type="number" ';
			echo 'id="p_activity_days" ';
			echo 'name="sh[p][a][days]" ';
			echo 'value="';
			echo $activity_days;
			echo '" ';
			echo 'size="4" min="1" class="form-control">';
			echo '</div>';
			echo '</div>';

			$typeahead_ary = [];

			if ($app['s_guest'])
			{
				$typeahead_status_ary = ['active'];
			}
			else if ($app['s_user'])
			{
				$typeahead_status_ary = ['active', 'extern'];
			}
			else if ($app['s_admin'])
			{
				$typeahead_status_ary = ['active', 'extern',
					'inactive', 'im', 'ip'];
			}

			foreach ($typeahead_status_ary as $t_stat)
			{
				$typeahead_ary[] = [
					'accounts', [
						'status'	=> $t_stat,
						'schema'	=> $app['tschema'],
					],
				];
			}

			$typeahead = $app['typeahead']->get($typeahead_ary);

			echo '<div class="form-group">';
			echo '<label for="p_activity_filter_letscode" ';
			echo 'class="control-label">';
			echo 'Exclusief tegenpartij';
			echo '</label>';
			echo '<div class="input-group">';
			echo '<span class="input-group-addon">';
			echo '<i class="fa fa-user"></i>';
			echo '</span>';
			echo '<input type="text" ';
			echo 'name="sh[p][a][code]" ';
			echo 'id="p_activity_filter_code" ';
			echo 'value="';
			echo $activity_filter_code;
			echo '" ';
			echo 'placeholder="Account Code" ';
			echo 'class="form-control" ';
			echo 'data-newuserdays="';
			echo $app['config']->get('newuserdays', $app['tschema']);
			echo '" ';
			echo 'data-typeahead="';
			echo $typeahead;
			echo '">';
			echo '</div>';
			echo '</div>';

			foreach ($ary as $a_type => $a_ary)
			{
				foreach($a_ary as $key => $lbl)
				{
					$checkbox_id = 'id_' . $group . '_' . $a_type . '_' . $key;

					echo '<div class="checkbox">';
					echo '<label for="';
					echo $checkbox_id;
					echo '">';
					echo '<input type="checkbox" ';
					echo 'id="';
					echo $checkbox_id;
					echo '" ';
					echo 'name="sh[' . $group . '][' . $a_type . '][' . $key . ']" ';
					echo 'value="1"';
					echo isset($show_columns[$group][$a_type][$key]) ? ' checked="checked"' : '';
					echo '> ' . $lbl;
					echo '</label>';
					echo '</div>';
				}
			}

			echo '</div>';

			continue;
		}
		else if ($group === 'm')
		{
			echo '<h3>Vraag en aanbod</h3>';
		}

		foreach ($ary as $key => $lbl)
		{
			$checkbox_id = 'id_' . $group . '_' . $key;

			echo '<div class="checkbox">';
			echo '<label for="';
			echo $checkbox_id;
			echo '">';
			echo '<input type="checkbox" name="sh[';
			echo $group . '][' . $key . ']" ';
			echo 'id="';
			echo $checkbox_id;
			echo '" ';
			echo 'value="1"';
			echo isset($show_columns[$group][$key]) ? ' checked="checked"' : '';
			echo '> ';
			echo $lbl;

			if ($key === 'adr')
			{
				echo ', split door teken: ';
				echo '<input type="text" ';
				echo 'name="sh[p][c][adr_split]" ';
				echo 'size="1" value="';
				echo $adr_split;
				echo '">';
			}

			if ($key === 'saldo_date')
			{
				echo '<div class="input-group">';
				echo '<span class="input-group-addon">';
				echo '<i class="fa fa-calendar"></i>';
				echo '</span>';
				echo '<input type="text" ';
				echo 'class="form-control" ';
				echo 'name="sh[p][u][saldo_date]" ';
				echo 'data-provide="datepicker" ';
				echo 'data-date-format="';
				echo $app['date_format']->datepicker_format($app['tschema']);
				echo '" ';
				echo 'data-date-language="nl" ';
				echo 'data-date-today-highlight="true" ';
				echo 'data-date-autoclose="true" ';
				echo 'data-date-enable-on-readonly="false" ';
				echo 'data-date-end-date="0d" ';
				echo 'data-date-orientation="bottom" ';
				echo 'placeholder="';
				echo $app['date_format']->datepicker_placeholder($app['tschema']);
				echo '" ';
				echo 'value="';
				echo $saldo_date;
				echo '">';
				echo '</div>';

				$columns['u']['saldo_date'] = 'Saldo op ' . $saldo_date;
			}

			echo '</label>';
			echo '</div>';
		}
	}

	echo '</div>';
	echo '<div class="row">';
	echo '<div class="col-md-12">';
	echo '<input type="submit" name="show" ';
	echo 'class="btn btn-default" ';
	echo 'value="Pas weergave kolommen aan">';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
}

if ($v_list || $v_tiles)
{
	echo '<br>';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<div class="row">';
	echo '<div class="col-xs-12">';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon">';
	echo '<i class="fa fa-search"></i>';
	echo '</span>';
	echo '<input type="text" class="form-control" ';
	echo 'id="q" name="q" value="' . $q . '" ';
	echo 'placeholder="Zoeken">';
	echo '</div>';
	echo '</div>';
	echo '</div>';

	echo '</div>';
	echo '</div>';

	echo '</form>';

	echo '<div class="pull-right hidden-xs hidden-sm print-hide">';
	echo 'Totaal: <span id="total"></span>';
	echo '</div>';

	echo '<ul class="nav nav-tabs" id="nav-tabs">';

	$nav_params = $params;

	foreach ($st as $k => $tab)
	{
		$nav_params['status'] = $k;
		echo '<li';
		echo $status === $k ? ' class="active"' : '';
		echo '>';
		$class = (isset($tab['cl'])) ? 'bg-' . $tab['cl'] : false;
		echo aphp('users', $nav_params, $tab['lbl'], $class) . '</li>';
	}

	echo '</ul>';
}

if ($v_list)
{
	echo '<div class="panel panel-success printview">';
	echo '<div class="table-responsive">';

	echo '<table class="table table-bordered table-striped table-hover footable csv" ';
	echo 'data-filtering="true" data-filter-delay="0" ';
	echo 'data-filter="#q" data-filter-min="1" data-cascade="true" ';
	echo 'data-empty="Er zijn geen ';
	echo $app['s_admin'] ? 'gebruikers' : 'leden';
	echo ' volgens de selectiecriteria" ';
	echo 'data-sorting="true" ';
	echo 'data-filter-placeholder="Zoeken" ';
	echo 'data-filter-position="left"';

	if (count($ref_geo))
	{
		echo ' data-lat="' . $ref_geo['lat'] . '" ';
		echo 'data-lng="' . $ref_geo['lng'] . '"';
	}

	echo '>';
	echo '<thead>';

	echo '<tr>';

	$numeric_keys = [
		'saldo'			=> true,
		'saldo_date'	=> true,
	];

	$date_keys = [
		'cdate'			=> true,
		'mdate'			=> true,
		'adate'			=> true,
		'lastlogin'		=> true,
	];

	$link_user_keys = [
		'letscode'		=> true,
		'name'			=> true,
	];

	foreach ($show_columns as $group => $ary)
	{
		if ($group === 'p')
		{
			continue;
		}
		else if ($group === 'a')
		{
			foreach ($ary as $a_key => $a_ary)
			{
				foreach ($a_ary as $key => $one)
				{
					echo '<th data-type="numeric">';
					echo $columns[$group][$a_key][$key];
					echo '</th>';
				}
			}

			continue;
		}
		else if ($group === 'd')
		{
			if (count($ref_geo))
			{
				foreach($ary as $key => $one)
				{
					echo '<th>';
					echo $columns[$group][$key];
					echo '</th>';
				}
			}

			continue;
		}
		else if ($group === 'c')
		{
			$tpl = '<th data-hide="tablet, phone" data-sort-ignore="true">%1$s</th>';

			foreach ($ary as $key => $one)
			{
				if ($key == 'adr' && $adr_split != '')
				{
					echo sprintf($tpl, 'Adres (1)');
					echo sprintf($tpl, 'Adres (2)');
					continue;
				}

				echo sprintf($tpl, $columns[$group][$key]);
			}

			continue;
		}
		else if ($group === 'u')
		{
			foreach ($ary as $key => $one)
			{
				$data_type =  isset($numeric_keys[$key]) ? ' data-type="numeric"' : '';
				$data_sort_initial = $key === 'letscode' ? ' data-sort-initial="true"' : '';

				echo '<th' . $data_type . $data_sort_initial . '>';
				echo $columns[$group][$key];
				echo '</th>';
			}

			continue;
		}
		else if ($group === 'm')
		{
			foreach ($ary as $key => $one)
			{
				echo '<th data-type="numeric">';
				echo $columns[$group][$key];
				echo '</th>';
			}

			continue;
		}
	}

	echo '</tr>';

	echo '</thead>';
	echo '<tbody>';

	$checkbox = '<input type="checkbox" name="sel_%1$s" value="1"%2$s>&nbsp;';

	$can_link = $app['s_admin'];

	foreach($users as $u)
	{
		if (($app['s_user'] || $app['s_guest'])
			&& ($u['status'] === 1 || $u['status'] === 2))
		{
			$can_link = true;
		}

		$id = $u['id'];

		$row_stat = ($u['status'] == 1 && $app['new_user_treshold'] < strtotime($u['adate'])) ? 3 : $u['status'];

		$class = isset($st_class_ary[$row_stat]) ? ' class="' . $st_class_ary[$row_stat] . '"' : '';

		$first = true;

		echo '<tr' . $class . ' data-balance="' . $u['saldo'] . '">';

		if (isset($show_columns['u']))
		{
			foreach ($show_columns['u'] as $key => $one)
			{
				echo '<td';
				echo isset($date_keys[$key]) ? ' data-value="' . $u[$key] . '"' : '';
				echo '>';

				echo $app['s_admin'] && $first ? sprintf($checkbox, $id, isset($selected_users[$id]) ? ' checked="checked"' : '') : '';
				$first = false;

				if (isset($link_user_keys[$key]))
				{
					echo link_user($u, $app['tschema'], $can_link, false, $key);
				}
				else if (isset($date_keys[$key]))
				{
					if ($u[$key])
					{
						echo $app['date_format']->get($u[$key], 'day', $app['tschema']);
					}
					else
					{
						echo '&nbsp;';
					}
				}
				else if ($key === 'fullname')
				{
					if ($app['s_admin'] || $u['fullname_access'] === 'interlets')
					{
						echo link_user($u, $app['tschema'], $can_link, false, 'fullname');
					}
					else if ($app['s_user'] && $u['fullname_access'] !== 'admin')
					{
						echo link_user($u, $app['tschema'], $can_link, false, 'fullname');
					}
					else
					{
						echo '<span class="btn btn-default btn-xs">';
						echo 'verborgen</span>';
					}
				}
				else
				{
					echo htmlspecialchars($u[$key]);
				}

				echo '</td>';
			}
		}

		if (isset($show_columns['c']))
		{
			foreach ($show_columns['c'] as $key => $one)
			{
				echo '<td>';

				if ($key == 'adr' && $adr_split != '')
				{
					if (!isset($contacts[$id][$key]))
					{
						echo '&nbsp;</td><td>&nbsp;</td>';
						continue;
					}

					[$adr_1, $adr_2] = explode(trim($adr_split), $contacts[$id]['adr'][0][0]);

					echo get_contacts_str([[$adr_1, $contacts[$id]['adr'][0][1]]], 'adr');
					echo '</td><td>';
					echo get_contacts_str([[$adr_2, $contacts[$id]['adr'][0][1]]], 'adr');
				}
				else if (isset($contacts[$id][$key]))
				{
					echo get_contacts_str($contacts[$id][$key], $key);
				}
				else
				{
					echo '&nbsp;';
				}

				echo '</td>';
			}
		}

		if (isset($show_columns['d']) && count($ref_geo))
		{
			echo '<td data-value="5000"';

			$adr_ary = $contacts[$id]['adr'][0] ?? [];

			if (isset($adr_ary[1]))
			{
				if ($adr_ary[1] >= $app['s_access_level'])
				{
					if (count($adr_ary) && $adr_ary[0])
					{
						$geo = $app['cache']->get('geo_' . $adr_ary[0]);

						if ($geo)
						{
							echo ' data-lat="' . $geo['lat'] . '" data-lng="' . $geo['lng'] . '"';
						}
					}

					echo '><i class="fa fa-times"></i>';
				}
				else
				{
					echo '><span class="btn btn-default btn-xs">verborgen</span>';
				}
			}
			else
			{
				echo '><i class="fa fa-times"></i>';
			}

			echo '</td>';
		}

		if (isset($show_columns['m']))
		{
			foreach($show_columns['m'] as $key => $one)
			{
				echo '<td>';

				if (isset($msgs_count[$id][$key]))
				{
					echo aphp('messages', [
						'f'	=> [
							'uid' 	=> $id,
							'type' 	=> $message_type_filter[$key],
						],
					], $msgs_count[$id][$key]);
				}

				echo '</td>';
			}
		}

		if (isset($show_columns['a']))
		{
			$from_date = $app['date_format']->get_from_unix(time() - ($activity_days * 86400), 'day', $app['tschema']);

			foreach($show_columns['a'] as $a_key => $a_ary)
			{
				foreach ($a_ary as $key => $one)
				{
					echo '<td>';

					if (isset($activity[$id][$a_key][$key]))
					{
						if (isset($code_only_activity_filter_code))
						{
							echo $activity[$id][$a_key][$key];
						}
						else
						{
							echo aphp('transactions', [
								'f' => [
									'fcode'	=> $key === 'in' ? '' : $u['letscode'],
									'tcode'	=> $key === 'out' ? '' : $u['letscode'],
									'andor'	=> $key === 'total' ? 'or' : 'and',
									'fdate' => $from_date,
								],
							], $activity[$id][$a_key][$key]);
						}
					}

					echo '</td>';
				}
			}
		}

		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div></div>';

	echo '<div class="row"><div class="col-md-12">';
	echo '<p><span class="pull-right">Totaal saldo: <span id="sum"></span> ';
	echo $app['config']->get('currency', $app['tschema']);
	echo '</span></p>';
	echo '</div></div>';

	if ($app['s_admin'] & isset($show_columns['u']))
	{
		$bulk_mail_cc = $app['is_http_post'] ? $bulk_mail_cc : true;

		$inp =  '<div class="form-group">';
		$inp .=  '<label for="%5$s" class="control-label">%2$s</label>';
		$inp .= '<div class="input-group">';
		$inp .= '<span class="input-group-addon">';
		$inp .= '<span class="fa fa-%6$s"></span></span>';
		$inp .=  '<input type="%3$s" id="%5$s" name="%1$s" %4$s>';
		$inp .=  '</div>';
		$inp .=  '</div>';

		$checkbox = '<div class="form-group">';
		$checkbox .= '<label for="%5$s" class="control-label">';
		$checkbox .= '<input type="%3$s" id="%5$s" name="%1$s" %4$s>';
		$checkbox .= ' %2$s</label></div>';

		$acc_sel = '<div class="form-group">';
		$acc_sel .= '<label for="%1$s" class="control-label">';
		$acc_sel .= '%2$s</label>';
		$acc_sel .= '<div class="input-group">';
		$acc_sel .= '<span class="input-group-addon">';
		$acc_sel .= '<span class="fa fa-%4$s"></span></span>';
		$acc_sel .= '<select name="%1$s" id="%1$s" class="form-control">';
		$acc_sel .= '%3$s';
		$acc_sel .= '</select>';
		$acc_sel .= '</div>';
		$acc_sel .= '</div>';

		echo '<div class="panel panel-default" id="actions">';
		echo '<div class="panel-heading">';

		echo '<span class="btn btn-default" id="invert_selection">';
		echo 'Selectie omkeren</span>&nbsp;';
		echo '<span class="btn btn-default" id="select_all">';
		echo 'Selecteer alle</span>&nbsp;';
		echo '<span class="btn btn-default" id="deselect_all">';
		echo 'De-selecteer alle</span>';

		echo '</div>';
		echo '</div>';

		echo '<h3>Bulk acties met geselecteerde gebruikers</h3>';
		echo '<div class="panel panel-info">';
		echo '<div class="panel-heading">';

		echo '<ul class="nav nav-tabs" role="tablist">';

		echo '<li class="active">';
		echo '<a href="#mail_tab" data-toggle="tab">Mail</a></li>';
		echo '<li class="dropdown">';

		echo '<a class="dropdown-toggle" data-toggle="dropdown" href="#">Veld aanpassen';
		echo '<span class="caret"></span></a>';
		echo '<ul class="dropdown-menu">';

		foreach ($edit_fields_tabs as $k => $t)
		{
			echo '<li>';
			echo '<a href="#' . $k . '_tab" data-toggle="tab">';
			echo $t['lbl'];
			echo '</a></li>';
		}

		echo '</ul>';
		echo '</li>';
		echo '</ul>';

		echo '<div class="tab-content">';

		echo '<div role="tabpanel" class="tab-pane active" id="mail_tab">';
		echo '<h3>E-Mail verzenden naar geselecteerde gebruikers</h3>';

		echo '<form method="post">';

		echo '<div class="form-group">';
		echo '<input type="text" class="form-control" id="bulk_mail_subject" name="bulk_mail_subject" ';
		echo 'placeholder="Onderwerp" ';
		echo 'value="';
		echo $bulk_mail_subject ?? '';
		echo '" required>';
		echo '</div>';

		echo '<div class="form-group">';
		echo '<textarea name="bulk_mail_content" ';
		echo 'class="form-control rich-edit" ';
		echo 'id="bulk_mail_content" rows="8" ';
		echo 'data-template-vars="';
		echo implode(',', array_keys($map_template_vars));
		echo '" ';
		echo 'required>';
		echo $bulk_mail_content ?? '';
		echo '</textarea>';
		echo '</div>';

		echo '<div class="form-group">';
		echo '<label for="bulk_mail_cc" class="control-label">';
		echo '<input type="checkbox" name="bulk_mail_cc" ';
		echo 'id="bulk_mail_cc"';
		echo $bulk_mail_cc ? ' checked="checked"' : '';
		echo ' value="1" > ';
		echo 'Stuur een kopie met verzendinfo naar mijzelf';
		echo '</label>';
		echo '</div>';

		echo '<div class="form-group">';
		echo '<label for="verify_mail" class="control-label">';
		echo '<input type="checkbox" name="verify_mail" ';
		echo 'id="verify_mail" ';
		echo 'value="1" required> ';
		echo 'Ik heb mijn bericht nagelezen en nagekeken dat de juiste gebruikers geselecteerd zijn.';
		echo '</label>';
		echo '</div>';

		echo '<input type="submit" value="Zend test E-mail naar jezelf" name="bulk_mail_test" class="btn btn-default">&nbsp;';
		echo '<input type="submit" value="Verzend" name="bulk_mail_submit" class="btn btn-default">';

		echo $app['form_token']->get_hidden_input();
		echo '</form>';
		echo '</div>';

		foreach($edit_fields_tabs as $k => $t)
		{
			echo '<div role="tabpanel" class="tab-pane" id="';
			echo $k;
			echo '_tab"';
			echo isset($t['access_control']) ? ' data-access-control="true"' : '';
			echo '>';
			echo '<h3>Veld aanpassen: ' . $t['lbl'] . '</h3>';

			echo '<form method="post">';

			if (isset($t['options']))
			{
				$options = $t['options'];
				echo sprintf($acc_sel,
					$k,
					$t['lbl'],
					get_select_options($options, 0),
					$t['fa']);
			}
			else if (isset($t['type']) && $t['type'] == 'checkbox')
			{
				echo sprintf($checkbox, $k, $t['lbl'], $t['type'], 'value="1"', $k);
			}
			else if (isset($t['access_control']))
			{
				echo $app['access_control']->get_radio_buttons();
			}
			else
			{
				echo sprintf($inp, $k, $t['lbl'], $t['type'], 'class="form-control"', $k, $t['fa']);
			}

			echo '<div class="form-group">';
			echo '<label for="verify_' . $k . '" class="control-label">';
			echo '<input type="checkbox" name="verify_' . $k . '" ';
			echo 'id="verify_' . $k . '" ';
			echo 'value="1" required> ';
			echo 'Ik heb nagekeken dat de juiste gebruikers geselecteerd zijn en veld en ingevulde waarde nagekeken.';
			echo '</label>';
			echo '</div>';

			echo '<input type="hidden" value="' . $k . '" name="bulk_field">';
			echo '<input type="submit" value="Veld aanpassen" name="' . $k . '_bulk_submit" class="btn btn-primary">';
			echo $app['form_token']->get_hidden_input();
			echo '</form>';

			echo '</div>';
		}

		echo '<div class="clearfix"></div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
}
else if ($v_tiles)
{
	echo '<p>';
	echo '<span class="btn-group sort-by" role="group">';
	echo '<button class="btn btn-default active" data-sort-by="letscode">';
	echo 'Account Code ';
	echo '<i class="fa fa-sort-asc"></i></button>';
	echo '<button class="btn btn-default" data-sort-by="name">';
	echo 'Naam ';
	echo '<i class="fa fa-sort"></i></button>';
	echo '<button class="btn btn-default" data-sort-by="postcode">';
	echo 'Postcode ';
	echo '<i class="fa fa-sort"></i></button>';
	echo '</span>';
	echo '</p>';

	echo '<div class="row tiles">';

	foreach ($users as $u)
	{
		$row_stat = ($u['status'] == 1 && $app['new_user_treshold'] < strtotime($u['adate'])) ? 3 : $u['status'];
		$class = $st_class_ary[$row_stat] ?? false;
		$class = $class ? ' class="bg-' . $class . '"' : '';

		$url = generate_url('users', ['id' => $u['id'], 'link' => $status]);

		echo '<div class="col-xs-4 col-md-3 col-lg-2 tile">';
		echo '<div' . $class . '>';
		echo '<div class="thumbnail text-center">';
		echo '<a href="' . $url . '">';

		if (isset($u['PictureFile']) && $u['PictureFile'] != '')
		{
			echo '<img src="';
			echo $app['s3_url'] . $u['PictureFile'];
			echo '" class="img-rounded">';
		}
		else
		{
			echo '<div><i class="fa fa-user fa-5x text-muted"></i></div>';
		}
		echo '</a>';

		echo '<div class="caption">';

		echo '<a href="' . $url . '">';
		echo '<span class="letscode">' . $u['letscode'] . '</span> ';
		echo '<span class="name">' . $u['name'] . '</span>';
		echo '</a>';
		echo '<br><span class="postcode">' . $u['postcode'] . '</span>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	echo '</div>';
}

include __DIR__ . '/include/footer.php';

function get_contacts_str(array $contacts, string $abbrev):string
{
	global $app;

	$ret = '';

	if (count($contacts))
	{
		end($contacts);
		$end = key($contacts);

		$tpl = '%1$s';

		if ($abbrev === 'mail')
		{
			$tpl = '<a href="mailto:%1$s">%1$s</a>';
		}
		else if ($abbrev === 'web')
		{
			$tpl = '<a href="%1$s">%1$s</a>';
		}

		foreach ($contacts as $key => $contact)
		{
			if ($contact[1] >= $app['s_access_level'])
			{
				$ret .= sprintf($tpl, htmlspecialchars($contact[0], ENT_QUOTES));

				if ($key === $end)
				{
					break;
				}

				$ret .= ',<br>';

				continue;
			}

			$ret .= '<span class="btn btn-default btn-xs">';
			$ret .= 'verborgen</span>';
			$ret .= '<br>';
		}
	}
	else
	{
		$ret .= '&nbsp;';
	}

	return $ret;
}

function cancel(int $id = 0):void
{
	$params = [];

	if ($id)
	{
		$params['id'] = $id;
	}
	else
	{
		$params['status'] = 'active';
	}

	header('Location: ' . generate_url('users', $params));
	exit;
}

function get_dd(string $str):string
{
	$out =  '<dd>';
	$out .=  $str ? htmlspecialchars($str, ENT_QUOTES) : '<span class="fa fa-times"></span>';
	$out .=  '</dd>';
	return $out;
}

function send_activation_mail(string $password, array $user):void
{
	global $app;

	$vars = [
		'group'			=> $app['template_vars']->get($app['tschema']),
		'user'			=> link_user($user, $app['tschema'], false),
		'user_mail'		=> $user['mail'],
	];

	$app['queue.mail']->queue([
		'schema'	=> $app['tschema'],
		'to' 		=> $app['mail_addr_system']->get_admin($app['tschema']),
		'vars'		=> $vars,
		'template'	=> 'admin_user_activation',
	], 5000);

	$vars = [
		'group'			=> $app['template_vars']->get($app['tschema']),
		'user'			=> $user,
		'password'		=> $password,
		'url_login'		=> $app['base_url'] . '/login.php?login=' . $user['letscode'],
		'support_url'	=> $app['base_url'] . '/support.php?src=p',
	];

	$app['queue.mail']->queue([
		'schema'	=> $app['tschema'],
		'to' 		=> $app['mail_addr_user']->get($user['id'], $app['tschema']),
		'reply_to' 	=> $app['mail_addr_system']->get_support($app['tschema']),
		'template'	=> 'user_activation',
		'vars'		=> $vars,
	], 5000);
}

function delete_thumbprint(string $status):void
{
	global $app;

	$app['typeahead']->delete_thumbprint('accounts', [
		'schema'	=> $app['tschema'],
		'status'	=> $status,
	]);

	if ($status !== 'active')
	{
		return;
	}

	foreach ($app['intersystem_ary']['eland'] as $remote_schema => $h)
	{
		$app['typeahead']->delete_thumbprint('eland_intersystem_accounts', [
			'schema'		=> $app['tschema'],
			'remote_schema'	=> $remote_schema,
		]);
	}
}
